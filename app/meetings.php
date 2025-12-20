<?php
declare(strict_types=1);

function can_manage_meetings(PDO $pdo, int $clubId, ?int $userId = null): bool {
  if ($userId === null) {
    $userId = current_user_id();
  }
  if (!$userId) {
    return false;
  }
  
  $stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$clubId, $userId]);
  $adminRole = $stmt->fetchColumn();
  
  if ($adminRole === 'owner' || $adminRole === 'admin') {
    return true;
  }
  
  $stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
  $stmt->execute([$clubId, $userId]);
  $committeeRole = $stmt->fetchColumn();
  
  return in_array($committeeRole, ['chairperson', 'secretary'], true);
}

function can_view_decisions(PDO $pdo, int $clubId, ?int $userId = null): bool {
  if ($userId === null) {
    $userId = current_user_id();
  }
  if (!$userId) {
    return false;
  }
  
  $stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$clubId, $userId]);
  if ($stmt->fetchColumn()) {
    return true;
  }
  
  $stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
  $stmt->execute([$clubId, $userId]);
  $committeeRole = $stmt->fetchColumn();
  
  return in_array($committeeRole, ['chairperson', 'secretary', 'treasurer', 'pro'], true);
}

function can_view_minutes(PDO $pdo, int $clubId, ?int $userId = null): bool {
  if ($userId === null) {
    $userId = current_user_id();
  }
  if (!$userId) {
    return false;
  }
  
  $stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$clubId, $userId]);
  if ($stmt->fetchColumn()) {
    return true;
  }
  
  $stmt = $pdo->prepare("SELECT id FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
  $stmt->execute([$clubId, $userId]);
  return (bool)$stmt->fetchColumn();
}

function get_meetings(PDO $pdo, int $clubId, int $limit = 20, int $offset = 0): array {
  $limit = (int)$limit;
  $offset = (int)$offset;
  
  $stmt = $pdo->prepare("
    SELECT m.*, u.name as created_by_name,
           (SELECT COUNT(*) FROM meeting_decisions md WHERE md.meeting_id = m.id) as decision_count,
           (SELECT COUNT(*) FROM meeting_tasks mt WHERE mt.meeting_id = m.id) as task_count,
           (SELECT id FROM meeting_minutes mm WHERE mm.meeting_id = m.id) as has_minutes
    FROM meetings m
    LEFT JOIN users u ON m.created_by = u.id
    WHERE m.club_id = ?
    ORDER BY m.meeting_date DESC, m.meeting_time DESC
    LIMIT {$limit} OFFSET {$offset}
  ");
  $stmt->execute([$clubId]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_meeting(PDO $pdo, int $meetingId): ?array {
  $stmt = $pdo->prepare("
    SELECT m.*, u.name as created_by_name
    FROM meetings m
    LEFT JOIN users u ON m.created_by = u.id
    WHERE m.id = ?
  ");
  $stmt->execute([$meetingId]);
  $meeting = $stmt->fetch(PDO::FETCH_ASSOC);
  return $meeting ?: null;
}

function get_meeting_minutes(PDO $pdo, int $meetingId): ?array {
  $stmt = $pdo->prepare("
    SELECT mm.*, u.name as created_by_name
    FROM meeting_minutes mm
    LEFT JOIN users u ON mm.created_by = u.id
    WHERE mm.meeting_id = ?
  ");
  $stmt->execute([$meetingId]);
  $minutes = $stmt->fetch(PDO::FETCH_ASSOC);
  return $minutes ?: null;
}

function get_meeting_decisions(PDO $pdo, int $meetingId): array {
  $stmt = $pdo->prepare("
    SELECT md.*, u.name as created_by_name
    FROM meeting_decisions md
    LEFT JOIN users u ON md.created_by = u.id
    WHERE md.meeting_id = ?
    ORDER BY md.created_at ASC
  ");
  $stmt->execute([$meetingId]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_club_decisions(PDO $pdo, int $clubId, int $limit = 50): array {
  $limit = (int)$limit;
  
  $stmt = $pdo->prepare("
    SELECT md.*, u.name as created_by_name, m.title as meeting_title
    FROM meeting_decisions md
    LEFT JOIN users u ON md.created_by = u.id
    LEFT JOIN meetings m ON md.meeting_id = m.id
    WHERE md.club_id = ?
    ORDER BY md.decision_date DESC, md.created_at DESC
    LIMIT {$limit}
  ");
  $stmt->execute([$clubId]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_meeting_tasks(PDO $pdo, int $meetingId): array {
  $stmt = $pdo->prepare("
    SELECT mt.*, 
           u.name as assigned_to_name,
           ab.name as assigned_by_name
    FROM meeting_tasks mt
    LEFT JOIN users u ON mt.assigned_to = u.id
    LEFT JOIN users ab ON mt.assigned_by = ab.id
    WHERE mt.meeting_id = ?
    ORDER BY mt.due_date ASC, mt.priority DESC
  ");
  $stmt->execute([$meetingId]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_club_tasks(PDO $pdo, int $clubId, ?string $status = null, int $limit = 50): array {
  $limit = (int)$limit;
  
  $sql = "
    SELECT mt.*, 
           u.name as assigned_to_name,
           ab.name as assigned_by_name,
           m.title as meeting_title
    FROM meeting_tasks mt
    LEFT JOIN users u ON mt.assigned_to = u.id
    LEFT JOIN users ab ON mt.assigned_by = ab.id
    LEFT JOIN meetings m ON mt.meeting_id = m.id
    WHERE mt.club_id = ?
  ";
  
  $params = [$clubId];
  
  if ($status) {
    $sql .= " AND mt.status = ?";
    $params[] = $status;
  }
  
  $sql .= " ORDER BY mt.due_date ASC, mt.priority DESC LIMIT {$limit}";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_user_tasks(PDO $pdo, int $userId, ?string $status = null): array {
  $sql = "
    SELECT mt.*, 
           c.name as club_name,
           c.slug as club_slug,
           ab.name as assigned_by_name,
           m.title as meeting_title
    FROM meeting_tasks mt
    JOIN clubs c ON mt.club_id = c.id
    LEFT JOIN users ab ON mt.assigned_by = ab.id
    LEFT JOIN meetings m ON mt.meeting_id = m.id
    WHERE mt.assigned_to = ?
  ";
  
  $params = [$userId];
  
  if ($status) {
    $sql .= " AND mt.status = ?";
    $params[] = $status;
  } else {
    $sql .= " AND mt.status != 'cancelled'";
  }
  
  $sql .= " ORDER BY mt.due_date ASC, mt.priority DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_meeting_notes(PDO $pdo, int $meetingId): array {
  $stmt = $pdo->prepare("
    SELECT mn.*, u.name as created_by_name
    FROM meeting_notes mn
    LEFT JOIN users u ON mn.created_by = u.id
    WHERE mn.meeting_id = ?
    ORDER BY mn.created_at DESC
  ");
  $stmt->execute([$meetingId]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_club_notes(PDO $pdo, int $clubId, int $limit = 50): array {
  $limit = (int)$limit;
  
  $stmt = $pdo->prepare("
    SELECT mn.*, u.name as created_by_name, m.title as meeting_title
    FROM meeting_notes mn
    LEFT JOIN users u ON mn.created_by = u.id
    LEFT JOIN meetings m ON mn.meeting_id = m.id
    WHERE mn.club_id = ?
    ORDER BY mn.created_at DESC
    LIMIT {$limit}
  ");
  $stmt->execute([$clubId]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function create_task_notification(PDO $pdo, int $taskId, int $assignedTo, int $assignedBy, int $clubId): void {
  $stmt = $pdo->prepare("SELECT title FROM meeting_tasks WHERE id = ?");
  $stmt->execute([$taskId]);
  $taskTitle = $stmt->fetchColumn();
  
  $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
  $stmt->execute([$assignedBy]);
  $assignerName = $stmt->fetchColumn();
  
  $stmt = $pdo->prepare("SELECT slug FROM clubs WHERE id = ?");
  $stmt->execute([$clubId]);
  $clubSlug = $stmt->fetchColumn();
  
  create_notification(
    $pdo,
    $assignedTo,
    'task_assigned',
    "You've been assigned a task: " . $taskTitle,
    $assignerName . " assigned you a new task",
    "/public/tasks.php?club_slug=" . $clubSlug
  );
}

function update_task_status(PDO $pdo, int $taskId, string $status, int $userId): bool {
  $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
  
  $stmt = $pdo->prepare("
    UPDATE meeting_tasks 
    SET status = ?, completed_at = ?, updated_at = NOW()
    WHERE id = ?
  ");
  
  return $stmt->execute([$status, $completedAt, $taskId]);
}
