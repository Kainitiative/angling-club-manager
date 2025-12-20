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

if (!$club) {
  header('Location: /public/dashboard.php');
  exit;
}

if (!can_manage_meetings($pdo, $clubId)) {
  header('Location: /public/club.php?slug=' . urlencode($club['slug']));
  exit;
}

$userId = current_user_id();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'create_meeting') {
    $title = trim($_POST['title'] ?? '');
    $meetingDate = $_POST['meeting_date'] ?? '';
    $meetingTime = $_POST['meeting_time'] ?: null;
    $location = trim($_POST['location'] ?? '') ?: null;
    $meetingType = $_POST['meeting_type'] ?? 'committee';
    
    if ($title && $meetingDate) {
      $stmt = $pdo->prepare("
        INSERT INTO meetings (club_id, title, meeting_date, meeting_time, location, meeting_type, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $title, $meetingDate, $meetingTime, $location, $meetingType, $userId]);
      $message = 'Meeting created successfully.';
      $messageType = 'success';
    } else {
      $message = 'Please provide a title and date.';
      $messageType = 'danger';
    }
  } elseif ($action === 'update_status') {
    $meetingId = (int)($_POST['meeting_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($meetingId && in_array($status, ['scheduled', 'completed', 'cancelled'])) {
      $stmt = $pdo->prepare("UPDATE meetings SET status = ? WHERE id = ? AND club_id = ?");
      $stmt->execute([$status, $meetingId, $clubId]);
      $message = 'Meeting status updated.';
      $messageType = 'success';
    }
  } elseif ($action === 'delete_meeting') {
    $meetingId = (int)($_POST['meeting_id'] ?? 0);
    
    if ($meetingId) {
      $pdo->beginTransaction();
      try {
        $pdo->prepare("DELETE FROM meeting_minutes WHERE meeting_id = ?")->execute([$meetingId]);
        $pdo->prepare("DELETE FROM meeting_decisions WHERE meeting_id = ?")->execute([$meetingId]);
        $pdo->prepare("DELETE FROM meeting_notes WHERE meeting_id = ?")->execute([$meetingId]);
        $pdo->prepare("DELETE FROM meeting_tasks WHERE meeting_id = ?")->execute([$meetingId]);
        $pdo->prepare("DELETE FROM meetings WHERE id = ? AND club_id = ?")->execute([$meetingId, $clubId]);
        $pdo->commit();
        $message = 'Meeting deleted.';
        $messageType = 'success';
      } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Error deleting meeting.';
        $messageType = 'danger';
      }
    }
  }
}

$meetings = get_meetings($pdo, $clubId, 50, 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM meetings WHERE club_id = ?");
$stmt->execute([$clubId]);
$totalMeetings = (int)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meetings - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --primary-dark: #1e3a5f; --primary: #2d5a87; }
    .navbar-custom { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); }
    .meeting-card { border-left: 4px solid var(--primary); transition: all 0.2s; }
    .meeting-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .meeting-card.completed { border-left-color: #198754; }
    .meeting-card.cancelled { border-left-color: #dc3545; opacity: 0.7; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark navbar-custom">
  <div class="container">
    <a class="navbar-brand" href="/public/club.php?slug=<?= e($club['slug']) ?>"><?= e($club['name']) ?></a>
    <div class="d-flex gap-2">
      <a href="/public/admin/members.php?club_id=<?= $clubId ?>" class="btn btn-outline-light btn-sm">Members</a>
      <a href="/public/dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-1">Meetings</h2>
      <p class="text-muted mb-0"><?= $totalMeetings ?> meeting<?= $totalMeetings !== 1 ? 's' : '' ?> recorded</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMeetingModal">
      <i class="bi bi-plus-lg"></i> New Meeting
    </button>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="row mb-4">
    <div class="col-md-3">
      <a href="/public/admin/decisions.php?club_id=<?= $clubId ?>" class="card text-decoration-none h-100">
        <div class="card-body text-center">
          <i class="bi bi-clipboard-check fs-2 text-primary"></i>
          <h6 class="mt-2 mb-0">Decisions</h6>
        </div>
      </a>
    </div>
    <div class="col-md-3">
      <a href="/public/admin/tasks.php?club_id=<?= $clubId ?>" class="card text-decoration-none h-100">
        <div class="card-body text-center">
          <i class="bi bi-list-task fs-2 text-warning"></i>
          <h6 class="mt-2 mb-0">Tasks</h6>
        </div>
      </a>
    </div>
    <div class="col-md-3">
      <a href="/public/admin/notes.php?club_id=<?= $clubId ?>" class="card text-decoration-none h-100">
        <div class="card-body text-center">
          <i class="bi bi-journal-text fs-2 text-info"></i>
          <h6 class="mt-2 mb-0">Notes</h6>
        </div>
      </a>
    </div>
    <div class="col-md-3">
      <a href="/public/meetings.php?slug=<?= e($club['slug']) ?>" class="card text-decoration-none h-100">
        <div class="card-body text-center">
          <i class="bi bi-eye fs-2 text-secondary"></i>
          <h6 class="mt-2 mb-0">Member View</h6>
        </div>
      </a>
    </div>
  </div>
  
  <?php if (empty($meetings)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-calendar-event fs-1 text-muted"></i>
        <h5 class="mt-3">No meetings yet</h5>
        <p class="text-muted">Create your first meeting to start recording minutes and decisions.</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMeetingModal">
          Create Meeting
        </button>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($meetings as $meeting): ?>
        <div class="col-md-6">
          <div class="card meeting-card <?= $meeting['status'] ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <h5 class="mb-1">
                    <a href="/public/admin/meeting.php?id=<?= $meeting['id'] ?>" class="text-decoration-none">
                      <?= e($meeting['title']) ?>
                    </a>
                  </h5>
                  <span class="badge bg-secondary"><?= ucfirst($meeting['meeting_type']) ?></span>
                  <?php if ($meeting['status'] === 'completed'): ?>
                    <span class="badge bg-success">Completed</span>
                  <?php elseif ($meeting['status'] === 'cancelled'): ?>
                    <span class="badge bg-danger">Cancelled</span>
                  <?php endif; ?>
                </div>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/public/admin/meeting.php?id=<?= $meeting['id'] ?>">View Details</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form method="post">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="meeting_id" value="<?= $meeting['id'] ?>">
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="dropdown-item">Mark Completed</button>
                      </form>
                    </li>
                    <li>
                      <form method="post">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="meeting_id" value="<?= $meeting['id'] ?>">
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="dropdown-item">Mark Cancelled</button>
                      </form>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form method="post" onsubmit="return confirm('Delete this meeting and all associated data?');">
                        <input type="hidden" name="action" value="delete_meeting">
                        <input type="hidden" name="meeting_id" value="<?= $meeting['id'] ?>">
                        <button type="submit" class="dropdown-item text-danger">Delete</button>
                      </form>
                    </li>
                  </ul>
                </div>
              </div>
              
              <p class="text-muted mb-2">
                <i class="bi bi-calendar3"></i> <?= date('D, j M Y', strtotime($meeting['meeting_date'])) ?>
                <?php if ($meeting['meeting_time']): ?>
                  at <?= date('g:i A', strtotime($meeting['meeting_time'])) ?>
                <?php endif; ?>
              </p>
              
              <?php if ($meeting['location']): ?>
                <p class="text-muted mb-2 small">
                  <i class="bi bi-geo-alt"></i> <?= e($meeting['location']) ?>
                </p>
              <?php endif; ?>
              
              <div class="d-flex gap-3 mt-3 small">
                <?php if ($meeting['has_minutes']): ?>
                  <span class="text-success"><i class="bi bi-file-text"></i> Minutes</span>
                <?php else: ?>
                  <span class="text-muted"><i class="bi bi-file-text"></i> No minutes</span>
                <?php endif; ?>
                <span><i class="bi bi-clipboard-check"></i> <?= $meeting['decision_count'] ?> decisions</span>
                <span><i class="bi bi-list-task"></i> <?= $meeting['task_count'] ?> tasks</span>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="newMeetingModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="create_meeting">
        <div class="modal-header">
          <h5 class="modal-title">New Meeting</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g., Committee Meeting - January">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Date *</label>
              <input type="date" name="meeting_date" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Time</label>
              <input type="time" name="meeting_time" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" placeholder="e.g., Club House, Zoom">
          </div>
          <div class="mb-3">
            <label class="form-label">Meeting Type</label>
            <select name="meeting_type" class="form-select">
              <option value="committee">Committee Meeting</option>
              <option value="agm">AGM (Annual General Meeting)</option>
              <option value="egm">EGM (Extraordinary General Meeting)</option>
              <option value="general">General Meeting</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Meeting</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
