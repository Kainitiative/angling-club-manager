<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/meetings.php';
require_once __DIR__ . '/../app/layout/member_shell.php';

require_login();

$userId = current_user_id();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'update_status') {
    $taskId = (int)($_POST['task_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($taskId && in_array($status, ['pending', 'in_progress', 'completed'])) {
      $stmt = $pdo->prepare("SELECT id FROM meeting_tasks WHERE id = ? AND assigned_to = ?");
      $stmt->execute([$taskId, $userId]);
      
      if ($stmt->fetch()) {
        update_task_status($pdo, $taskId, $status, $userId);
        $message = 'Task updated.';
        $messageType = 'success';
      }
    }
  }
}

$filter = $_GET['filter'] ?? 'active';

if ($filter === 'active') {
  $activeTasks = get_user_tasks($pdo, $userId);
  $tasks = array_filter($activeTasks, fn($t) => !in_array($t['status'], ['completed', 'cancelled']));
} elseif ($filter === 'completed') {
  $tasks = get_user_tasks($pdo, $userId, 'completed');
} else {
  $tasks = get_user_tasks($pdo, $userId);
}

$allTasks = get_user_tasks($pdo, $userId);
$activeTasks = array_filter($allTasks, fn($t) => !in_array($t['status'], ['completed', 'cancelled']));
$completedTasks = array_filter($allTasks, fn($t) => $t['status'] === 'completed');

member_shell_start($pdo, ['title' => 'My Tasks', 'page' => 'tasks', 'section' => 'Personal']);
?>
<style>
  .priority-high { border-left: 4px solid #dc3545; }
  .priority-urgent { border-left: 4px solid #6f42c1; background: #f8f4ff; }
  .priority-medium { border-left: 4px solid #ffc107; }
  .priority-low { border-left: 4px solid #6c757d; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="mb-1">My Tasks</h2>
    <p class="text-muted mb-0"><?= count($activeTasks) ?> active, <?= count($completedTasks) ?> completed</p>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
    <?= e($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="btn-group mb-4">
  <a href="?filter=active" class="btn btn-<?= $filter === 'active' ? 'primary' : 'outline-primary' ?>">Active (<?= count($activeTasks) ?>)</a>
  <a href="?filter=completed" class="btn btn-<?= $filter === 'completed' ? 'primary' : 'outline-primary' ?>">Completed</a>
  <a href="?filter=all" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">All</a>
</div>

<?php if (empty($tasks)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="bi bi-check-circle fs-1 text-success"></i>
      <h5 class="mt-3">No tasks</h5>
      <p class="text-muted">You're all caught up!</p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="list-group list-group-flush">
      <?php foreach ($tasks as $task): ?>
        <div class="list-group-item priority-<?= $task['priority'] ?>">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <h6 class="mb-1">
                <?php if ($task['status'] === 'completed'): ?>
                  <i class="bi bi-check-circle-fill text-success me-1"></i>
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
              </div>
              <?php if ($task['description']): ?>
                <p class="text-muted small mb-1"><?= nl2br(e($task['description'])) ?></p>
              <?php endif; ?>
              <small class="text-muted">
                <a href="/public/club.php?slug=<?= e($task['club_slug']) ?>"><?= e($task['club_name']) ?></a>
                <?php if ($task['meeting_title']): ?>
                  &bull; <?= e($task['meeting_title']) ?>
                <?php endif; ?>
                &bull; Assigned by <?= e($task['assigned_by_name']) ?>
              </small>
            </div>
            <?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                  <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                  <option value="completed">Mark Complete</option>
                </select>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php member_shell_end(); ?>
