<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/meetings.php';

require_login();

$meetingId = (int)($_GET['id'] ?? 0);
if (!$meetingId) {
  header('Location: /public/dashboard.php');
  exit;
}

$meeting = get_meeting($pdo, $meetingId);
if (!$meeting) {
  header('Location: /public/dashboard.php');
  exit;
}

$clubId = (int)$meeting['club_id'];

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
$stmt->execute([$clubId]);
$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!can_manage_meetings($pdo, $clubId)) {
  header('Location: /public/club.php?slug=' . urlencode($club['slug']));
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
  
  if ($action === 'save_minutes') {
    $content = trim($_POST['content'] ?? '');
    $attendees = trim($_POST['attendees'] ?? '') ?: null;
    $apologies = trim($_POST['apologies'] ?? '') ?: null;
    
    $existingMinutes = get_meeting_minutes($pdo, $meetingId);
    
    if ($existingMinutes) {
      $stmt = $pdo->prepare("
        UPDATE meeting_minutes SET content = ?, attendees = ?, apologies = ?, updated_at = NOW()
        WHERE meeting_id = ?
      ");
      $stmt->execute([$content, $attendees, $apologies, $meetingId]);
    } else {
      $stmt = $pdo->prepare("
        INSERT INTO meeting_minutes (meeting_id, club_id, content, attendees, apologies, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$meetingId, $clubId, $content, $attendees, $apologies, $userId]);
    }
    $message = 'Minutes saved.';
    $messageType = 'success';
    
  } elseif ($action === 'add_decision') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '') ?: null;
    $proposedBy = trim($_POST['proposed_by'] ?? '') ?: null;
    $secondedBy = trim($_POST['seconded_by'] ?? '') ?: null;
    $status = $_POST['decision_status'] ?? 'approved';
    
    if ($title) {
      $stmt = $pdo->prepare("
        INSERT INTO meeting_decisions (meeting_id, club_id, title, description, decision_date, proposed_by, seconded_by, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$meetingId, $clubId, $title, $description, $meeting['meeting_date'], $proposedBy, $secondedBy, $status, $userId]);
      $message = 'Decision recorded.';
      $messageType = 'success';
    }
    
  } elseif ($action === 'delete_decision') {
    $decisionId = (int)($_POST['decision_id'] ?? 0);
    if ($decisionId) {
      $stmt = $pdo->prepare("DELETE FROM meeting_decisions WHERE id = ? AND club_id = ?");
      $stmt->execute([$decisionId, $clubId]);
      $message = 'Decision deleted.';
      $messageType = 'success';
    }
    
  } elseif ($action === 'add_task') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '') ?: null;
    $assignedTo = ((int)($_POST['assigned_to'] ?? 0)) ?: null;
    $dueDate = $_POST['due_date'] ?: null;
    $priority = $_POST['priority'] ?? 'medium';
    
    if ($title) {
      $stmt = $pdo->prepare("
        INSERT INTO meeting_tasks (meeting_id, club_id, title, description, assigned_to, assigned_by, due_date, priority)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$meetingId, $clubId, $title, $description, $assignedTo, $userId, $dueDate, $priority]);
      
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
      $message = 'Task status updated.';
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
    
  } elseif ($action === 'add_note') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if ($title && $content) {
      $stmt = $pdo->prepare("
        INSERT INTO meeting_notes (meeting_id, club_id, title, content, created_by)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->execute([$meetingId, $clubId, $title, $content, $userId]);
      $message = 'Note added.';
      $messageType = 'success';
    }
    
  } elseif ($action === 'delete_note') {
    $noteId = (int)($_POST['note_id'] ?? 0);
    if ($noteId) {
      $stmt = $pdo->prepare("DELETE FROM meeting_notes WHERE id = ? AND club_id = ?");
      $stmt->execute([$noteId, $clubId]);
      $message = 'Note deleted.';
      $messageType = 'success';
    }
  }
}

$minutes = get_meeting_minutes($pdo, $meetingId);
$decisions = get_meeting_decisions($pdo, $meetingId);
$tasks = get_meeting_tasks($pdo, $meetingId);
$notes = get_meeting_notes($pdo, $meetingId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($meeting['title']) ?> - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --primary-dark: #1e3a5f; --primary: #2d5a87; }
    .navbar-custom { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); }
    .nav-pills .nav-link.active { background-color: var(--primary); }
    .priority-high { border-left: 3px solid #dc3545; }
    .priority-urgent { border-left: 3px solid #6f42c1; background: #f8f4ff; }
    .priority-medium { border-left: 3px solid #ffc107; }
    .priority-low { border-left: 3px solid #6c757d; }
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
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h3 class="mb-1"><?= e($meeting['title']) ?></h3>
          <p class="text-muted mb-0">
            <span class="badge bg-secondary"><?= ucfirst($meeting['meeting_type']) ?></span>
            <i class="bi bi-calendar3 ms-2"></i> <?= date('l, j F Y', strtotime($meeting['meeting_date'])) ?>
            <?php if ($meeting['meeting_time']): ?>
              at <?= date('g:i A', strtotime($meeting['meeting_time'])) ?>
            <?php endif; ?>
            <?php if ($meeting['location']): ?>
              <span class="ms-2"><i class="bi bi-geo-alt"></i> <?= e($meeting['location']) ?></span>
            <?php endif; ?>
          </p>
        </div>
        <?php 
          $statusClass = match($meeting['status']) {
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'primary'
          };
        ?>
        <span class="badge bg-<?= $statusClass ?> fs-6"><?= ucfirst($meeting['status']) ?></span>
      </div>
    </div>
  </div>

  <ul class="nav nav-pills mb-4" id="meetingTabs" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#minutes-tab">
        <i class="bi bi-file-text"></i> Minutes
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#decisions-tab">
        <i class="bi bi-clipboard-check"></i> Decisions (<?= count($decisions) ?>)
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tasks-tab">
        <i class="bi bi-list-task"></i> Tasks (<?= count($tasks) ?>)
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notes-tab">
        <i class="bi bi-journal"></i> Notes (<?= count($notes) ?>)
      </button>
    </li>
  </ul>

  <div class="tab-content">
    
    <div class="tab-pane fade show active" id="minutes-tab">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Meeting Minutes</h5>
          <small class="text-muted">Minutes are visible to all club members</small>
        </div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="action" value="save_minutes">
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Attendees</label>
                <textarea name="attendees" class="form-control" rows="2" placeholder="List of attendees..."><?= e($minutes['attendees'] ?? '') ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Apologies</label>
                <textarea name="apologies" class="form-control" rows="2" placeholder="Members who sent apologies..."><?= e($minutes['apologies'] ?? '') ?></textarea>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Minutes Content</label>
              <textarea name="content" class="form-control" rows="15" placeholder="Enter meeting minutes here..."><?= e($minutes['content'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save"></i> Save Minutes
            </button>
            <?php if ($minutes): ?>
              <span class="text-muted ms-3">Last updated: <?= date('j M Y, g:i A', strtotime($minutes['updated_at'])) ?></span>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>
    
    <div class="tab-pane fade" id="decisions-tab">
      <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Decisions</h5>
            <small class="text-muted">Visible to committee members only</small>
          </div>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDecisionModal">
            <i class="bi bi-plus-lg"></i> Add Decision
          </button>
        </div>
        <div class="card-body">
          <?php if (empty($decisions)): ?>
            <p class="text-muted text-center py-3">No decisions recorded for this meeting.</p>
          <?php else: ?>
            <?php foreach ($decisions as $decision): ?>
              <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between">
                  <div>
                    <h6 class="mb-1"><?= e($decision['title']) ?></h6>
                    <?php 
                      $statusBadge = match($decision['status']) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'deferred' => 'warning',
                        default => 'secondary'
                      };
                    ?>
                    <span class="badge bg-<?= $statusBadge ?>"><?= ucfirst($decision['status']) ?></span>
                  </div>
                  <form method="post" onsubmit="return confirm('Delete this decision?');">
                    <input type="hidden" name="action" value="delete_decision">
                    <input type="hidden" name="decision_id" value="<?= $decision['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
                <?php if ($decision['description']): ?>
                  <p class="text-muted small mt-2 mb-1"><?= nl2br(e($decision['description'])) ?></p>
                <?php endif; ?>
                <small class="text-muted">
                  <?php if ($decision['proposed_by']): ?>
                    Proposed by <?= e($decision['proposed_by']) ?>
                  <?php endif; ?>
                  <?php if ($decision['seconded_by']): ?>
                    &bull; Seconded by <?= e($decision['seconded_by']) ?>
                  <?php endif; ?>
                </small>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="tab-pane fade" id="tasks-tab">
      <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Action Items / Tasks</h5>
            <small class="text-muted">Assigned members will receive notifications</small>
          </div>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus-lg"></i> Add Task
          </button>
        </div>
        <div class="card-body">
          <?php if (empty($tasks)): ?>
            <p class="text-muted text-center py-3">No tasks assigned from this meeting.</p>
          <?php else: ?>
            <?php foreach ($tasks as $task): ?>
              <div class="border rounded p-3 mb-3 priority-<?= $task['priority'] ?>">
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
                        $taskStatusBadge = match($task['status']) {
                          'completed' => 'success',
                          'in_progress' => 'primary',
                          'cancelled' => 'secondary',
                          default => 'warning'
                        };
                      ?>
                      <span class="badge bg-<?= $taskStatusBadge ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span>
                      <span class="badge bg-outline-dark text-dark border"><?= ucfirst($task['priority']) ?></span>
                      <?php if ($task['due_date']): ?>
                        <?php 
                          $dueDate = strtotime($task['due_date']);
                          $isOverdue = $dueDate < time() && $task['status'] !== 'completed';
                        ?>
                        <span class="badge <?= $isOverdue ? 'bg-danger' : 'bg-light text-dark border' ?>">
                          Due: <?= date('j M', $dueDate) ?>
                        </span>
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
                    </small>
                  </div>
                  <div class="d-flex gap-1">
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
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="tab-pane fade" id="notes-tab">
      <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Internal Notes</h5>
            <small class="text-muted">Private notes for committee use only</small>
          </div>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addNoteModal">
            <i class="bi bi-plus-lg"></i> Add Note
          </button>
        </div>
        <div class="card-body">
          <?php if (empty($notes)): ?>
            <p class="text-muted text-center py-3">No notes for this meeting.</p>
          <?php else: ?>
            <?php foreach ($notes as $note): ?>
              <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between">
                  <h6 class="mb-1"><?= e($note['title']) ?></h6>
                  <form method="post" onsubmit="return confirm('Delete this note?');">
                    <input type="hidden" name="action" value="delete_note">
                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
                <p class="mb-1"><?= nl2br(e($note['content'])) ?></p>
                <small class="text-muted">Added by <?= e($note['created_by_name']) ?> on <?= date('j M Y', strtotime($note['created_at'])) ?></small>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
  </div>
</div>

<div class="modal fade" id="addDecisionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add_decision">
        <div class="modal-header">
          <h5 class="modal-title">Add Decision</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Decision Title *</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g., Approved new membership fees">
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Details of the decision..."></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Proposed By</label>
              <input type="text" name="proposed_by" class="form-control" placeholder="Name">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Seconded By</label>
              <input type="text" name="seconded_by" class="form-control" placeholder="Name">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="decision_status" class="form-select">
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="deferred">Deferred</option>
              <option value="proposed">Proposed (Pending)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Decision</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add_task">
        <div class="modal-header">
          <h5 class="modal-title">Add Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Task Title *</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g., Update club website with new fees">
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Task details..."></textarea>
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
          <button type="submit" class="btn btn-primary">Add Task</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addNoteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add_note">
        <div class="modal-header">
          <h5 class="modal-title">Add Note</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Content *</label>
            <textarea name="content" class="form-control" rows="5" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Note</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
