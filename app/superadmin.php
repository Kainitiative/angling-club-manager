<?php
declare(strict_types=1);

function is_super_admin(PDO $pdo, ?int $userId = null): bool {
  if ($userId === null) {
    $userId = current_user_id();
  }
  if (!$userId) {
    return false;
  }
  
  $stmt = $pdo->prepare("SELECT is_super_admin FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $row = $stmt->fetch();
  
  return $row && (bool)$row['is_super_admin'];
}

function require_super_admin(PDO $pdo): void {
  if (!is_super_admin($pdo)) {
    http_response_code(403);
    exit('Access denied. Super admin privileges required.');
  }
}

function get_platform_stats(PDO $pdo): array {
  $stats = [];
  
  $stmt = $pdo->query("SELECT COUNT(*) FROM users");
  $stats['total_users'] = (int)$stmt->fetchColumn();
  
  $stmt = $pdo->query("SELECT COUNT(*) FROM clubs");
  $stats['total_clubs'] = (int)$stmt->fetchColumn();
  
  $stmt = $pdo->query("SELECT COUNT(*) FROM club_members WHERE membership_status = 'active'");
  $stats['total_active_members'] = (int)$stmt->fetchColumn();
  
  try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM catch_logs");
    $stats['total_catches'] = (int)$stmt->fetchColumn();
  } catch (PDOException $e) {
    $stats['total_catches'] = 0;
  }
  
  try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM competitions");
    $stats['total_competitions'] = (int)$stmt->fetchColumn();
  } catch (PDOException $e) {
    $stats['total_competitions'] = 0;
  }
  
  try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM club_subscriptions WHERE status = 'trial'");
    $stats['clubs_in_trial'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM club_subscriptions WHERE status = 'active'");
    $stats['clubs_paying'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM club_subscriptions WHERE status IN ('expired', 'cancelled')");
    $stats['clubs_expired'] = (int)$stmt->fetchColumn();
  } catch (PDOException $e) {
    $stats['clubs_in_trial'] = 0;
    $stats['clubs_paying'] = 0;
    $stats['clubs_expired'] = 0;
  }
  
  return $stats;
}

function get_all_clubs_with_subscriptions(PDO $pdo, int $limit = 50, int $offset = 0): array {
  try {
    $stmt = $pdo->prepare("
      SELECT c.*, 
             cs.status as subscription_status,
             cs.trial_started_at,
             cs.trial_ends_at,
             cs.paid_until,
             sp.name as plan_name,
             sp.price_monthly,
             (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.membership_status = 'active') as member_count,
             (SELECT COUNT(*) FROM club_admins ca WHERE ca.club_id = c.id) as admin_count,
             u.name as owner_name,
             u.email as owner_email
      FROM clubs c
      LEFT JOIN club_subscriptions cs ON c.id = cs.club_id
      LEFT JOIN subscription_plans sp ON cs.plan_id = sp.id
      LEFT JOIN club_admins ca ON c.id = ca.club_id AND ca.admin_role = 'owner'
      LEFT JOIN users u ON ca.user_id = u.id
      ORDER BY c.created_at DESC
      LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    $stmt = $pdo->prepare("
      SELECT c.*, 
             NULL as subscription_status,
             NULL as trial_started_at,
             NULL as trial_ends_at,
             NULL as paid_until,
             NULL as plan_name,
             NULL as price_monthly,
             (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.membership_status = 'active') as member_count,
             (SELECT COUNT(*) FROM club_admins ca WHERE ca.club_id = c.id) as admin_count,
             u.name as owner_name,
             u.email as owner_email
      FROM clubs c
      LEFT JOIN club_admins ca ON c.id = ca.club_id AND ca.admin_role = 'owner'
      LEFT JOIN users u ON ca.user_id = u.id
      ORDER BY c.created_at DESC
      LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

function get_all_users(PDO $pdo, int $limit = 50, int $offset = 0): array {
  $stmt = $pdo->prepare("
    SELECT u.*,
           (SELECT c.name FROM clubs c JOIN club_admins ca ON c.id = ca.club_id WHERE ca.user_id = u.id LIMIT 1) as owned_club,
           (SELECT c.name FROM clubs c JOIN club_members cm ON c.id = cm.club_id WHERE cm.user_id = u.id AND cm.membership_status = 'active' LIMIT 1) as member_of_club
    FROM users u
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
  ");
  $stmt->execute([$limit, $offset]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function set_user_super_admin(PDO $pdo, int $userId, bool $isSuperAdmin): bool {
  $stmt = $pdo->prepare("UPDATE users SET is_super_admin = ? WHERE id = ?");
  return $stmt->execute([$isSuperAdmin ? 1 : 0, $userId]);
}

function update_club_subscription_status(PDO $pdo, int $clubId, string $status): bool {
  $stmt = $pdo->prepare("UPDATE club_subscriptions SET status = ?, updated_at = NOW() WHERE club_id = ?");
  return $stmt->execute([$status, $clubId]);
}

function create_club_trial(PDO $pdo, int $clubId, int $trialDays = 90): bool {
  $trialEnds = date('Y-m-d H:i:s', strtotime("+{$trialDays} days"));
  
  $stmt = $pdo->prepare("SELECT id FROM club_subscriptions WHERE club_id = ?");
  $stmt->execute([$clubId]);
  
  if ($stmt->fetch()) {
    $stmt = $pdo->prepare("
      UPDATE club_subscriptions 
      SET status = 'trial', trial_started_at = NOW(), trial_ends_at = ?, updated_at = NOW()
      WHERE club_id = ?
    ");
    return $stmt->execute([$trialEnds, $clubId]);
  } else {
    $stmt = $pdo->prepare("
      INSERT INTO club_subscriptions (club_id, status, trial_started_at, trial_ends_at)
      VALUES (?, 'trial', NOW(), ?)
    ");
    return $stmt->execute([$clubId, $trialEnds]);
  }
}
