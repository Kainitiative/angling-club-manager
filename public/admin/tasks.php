<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/meetings.php';

require_login();

$clubId = (int)($_GET['club_id'] ?? 0);
if (!$clubId) {
  header('Location: /public/dashboard.php');
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
$stmt->execute([$clubId]);
$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club || !can_manage_meetings($pdo, $clubId)) {
  header('Location: /public/dashboard.php');
  exit;
}

$userId = current_user_id();
$message = '';
$messageType = '';

$stmt = $pdo->prepare("
  SELECT cm.user_id, u.name 
  FROM club_members cm 
  JOIN users u ON cm.user_id = u.id 
  WHERE cm.club_id = ? AND cm.membership_status = 'active'
  ORDER BY u.name
");
$stmt->execute([$clubId]);
$clubMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add_task') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '') ?: null;
    $assignedTo = ((int)($_POST['assigned_to'] ?? 0)) ?: null;
    $dueDate = $_POST['due_date'] ?: null;
    $priority = $_POST['priority'] ?? 'medium';
    
    if ($title) {
      $stmt = $pdo->prepare("
        INSERT INTO meeting_tasks (club_id, title, description, assigned_to, assigned_by, due_date, priority)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $title, $description, $assignedTo, $userId, $dueDate, $priority]);
      
      if ($assignedTo) {
        $taskId = (int)$pdo->lastInsertId();
        create_task_notification($pdo, $taskId, $assignedTo, $userId, $clubId);
      }
      
      $message = 'Task created.';
      $messageType = 'success';
    }
  } elseif ($action === 'update_task_status') {
    $taskId = (int)($_POST['task_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($taskId && in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
      update_task_status($pdo, $taskId, $status, $userId);
      $message = 'Task updated.';
      $messageType = 'success';
    }
  } elseif ($action === 'delete_task') {
    $taskId = (int)($_POST['task_id'] ?? 0);
    if ($taskId) {
      $stmt = $pdo->prepare("DELETE FROM meeting_tasks WHERE id = ? AND club_id = ?");
      $stmt->execute([$taskId, $clubId]);
      $message = 'Task deleted.';
      $messageType = 'success';
    }
  }
}

$filter = $_GET['filter'] ?? 'active';
$tasks = get_club_tasks($pdo, $clubId, $filter === 'active' ? null : $filter, 100);

if ($filter === 'active') {
  $tasks = array_filter($tasks, fn($t) => !in_array($t['status'], ['completed', 'cancelled']));
}

$taskCounts = ['pending' => 0, 'in_progress' => 0, 'completed' => 0];
$allTasks = get_club_tasks($pdo, $clubId, null, 500);
foreach ($allTasks as $t) {
  if (isset($taskCounts[$t['status']])) {
    $taskCounts[$t['status']]++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tasks - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --primary-dark: #1e3a5f; --primary: #2d5a87; }
    .navbar-custom { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); }
    .priority-high { border-left: 4px solid #dc3545; }
    .priority-urgent { border-left: 4px solid #6f42c1; background: #f8f4ff; }
    .priority-medium { border-left: 4px solid #ffc107; }
    .priority-low { border-left: 4px solid #6c757d; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark navbar-custom">
  <div class="container">
    <a class="navbar-brand" href="/public/admin/meetings.php?club_id=<?= $clubId ?>">
      <i class="bi bi-arrow-left"></i> <?= e($club['name']) ?> - Meetings
    </a>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-1">All Tasks</h2>
      <p class="text-muted mb-0">
        <?= $taskCounts['pending'] ?> pending, 
        <?= $taskCounts['in_progress'] ?> in progress, 
        <?= $taskCounts['completed'] ?> completed
      </p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
      <i class="bi bi-plus-lg"></i> New Task
    </button>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="btn-group mb-4">
    <a href="?club_id=<?= $clubId ?>&filter=active" class="btn btn-<?= $filter === 'active' ? 'primary' : 'outline-primary' ?>">Active</a>
    <a href="?club_id=<?= $clubId ?>&filter=pending" class="btn btn-<?= $filter === 'pending' ? 'primary' : 'outline-primary' ?>">Pending</a>
    <a href="?club_id=<?= $clubId ?>&filter=in_progress" class="btn btn-<?= $filter === 'in_progress' ? 'primary' : 'outline-primary' ?>">In Progress</a>
    <a href="?club_id=<?= $clubId ?>&filter=completed" class="btn btn-<?= $filter === 'completed' ? 'primary' : 'outline-primary' ?>">Completed</a>
  </div>
  
  <?php if (empty($tasks)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-list-task fs-1 text-muted"></i>
        <h5 class="mt-3">No tasks</h5>
        <p class="text-muted">Create tasks to assign action items to club members.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body p-0">
        <?php foreach ($tasks as $task): ?>
          <div class="border-bottom p-3 priority-<?= $task['priority'] ?>">
            <div class="d-flex justify-content-between align-items-start">
              <div class="flex-grow-1">
                <h6 class="mb-1">
                  <?php if ($task['status'] === 'completed'): ?>
                    <s class="text-muted"><?= e($task['title']) ?></s>
                  <?php else: ?>
                    <?= e($task['title']) ?>
                  <?php endif; ?>
                </h6>
                <div class="mb-2">
                  <?php 
                    $statusBadge = match($task['status']) {
                      'completed' => 'success',
                      'in_progress' => 'primary',
                      'cancelled' => 'secondary',
                      default => 'warning'
                    };
                  ?>
                  <span class="badge bg-<?= $statusBadge ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span>
                  <span class="badge bg-light text-dark border"><?= ucfirst($task['priority']) ?></span>
                  <?php if ($task['due_date']): ?>
                    <?php 
                      $dueDate = strtotime($task['due_date']);
                      $isOverdue = $dueDate < time() && $task['status'] !== 'completed';
                    ?>
                    <span class="badge <?= $isOverdue ? 'bg-danger' : 'bg-light text-dark border' ?>">
                      Due: <?= date('j M Y', $dueDate) ?>
                    </span>
                  <?php endif; ?>
                  <?php if ($task['meeting_title']): ?>
                    <span class="badge bg-info"><?= e($task['meeting_title']) ?></span>
                  <?php endif; ?>
                </div>
                <?php if ($task['description']): ?>
                  <p class="text-muted small mb-1"><?= nl2br(e($task['description'])) ?></p>
                <?php endif; ?>
                <small class="text-muted">
                  <?php if ($task['assigned_to_name']): ?>
                    Assigned to: <strong><?= e($task['assigned_to_name']) ?></strong>
                  <?php else: ?>
                    <em>Unassigned</em>
                  <?php endif; ?>
                  &bull; Created by <?= e($task['assigned_by_name']) ?>
                </small>
              </div>
              <div class="d-flex gap-2">
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="update_task_status">
                  <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                  <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $task['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                  </select>
                </form>
                <form method="post" onsubmit="return confirm('Delete this task?');">
                  <input type="hidden" name="action" value="delete_task">
                  <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="addTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add_task">
        <div class="modal-header">
          <h5 class="modal-title">New Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Task Title *</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Assign To</label>
            <select name="assigned_to" class="form-select">
              <option value="">-- Unassigned --</option>
              <?php foreach ($clubMembers as $member): ?>
                <option value="<?= $member['user_id'] ?>"><?= e($member['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Due Date</label>
              <input type="date" name="due_date" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Priority</label>
              <select name="priority" class="form-select">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Task</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
