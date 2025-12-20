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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add_note') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if ($title && $content) {
      $stmt = $pdo->prepare("
        INSERT INTO meeting_notes (club_id, title, content, created_by)
        VALUES (?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $title, $content, $userId]);
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

$notes = get_club_notes($pdo, $clubId, 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notes - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --primary-dark: #1e3a5f; --primary: #2d5a87; }
    .navbar-custom { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); }
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
      <h2 class="mb-1">Internal Notes</h2>
      <p class="text-muted mb-0">Private notes for committee use</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
      <i class="bi bi-plus-lg"></i> Add Note
    </button>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <?php if (empty($notes)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-journal-text fs-1 text-muted"></i>
        <h5 class="mt-3">No notes yet</h5>
        <p class="text-muted">Add private notes for internal committee reference.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($notes as $note): ?>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0"><?= e($note['title']) ?></h5>
                <form method="post" onsubmit="return confirm('Delete this note?');">
                  <input type="hidden" name="action" value="delete_note">
                  <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </div>
              <?php if ($note['meeting_title']): ?>
                <span class="badge bg-info mb-2"><?= e($note['meeting_title']) ?></span>
              <?php endif; ?>
              <p class="card-text"><?= nl2br(e($note['content'])) ?></p>
            </div>
            <div class="card-footer bg-transparent">
              <small class="text-muted">
                By <?= e($note['created_by_name']) ?> on <?= date('j M Y', strtotime($note['created_at'])) ?>
              </small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
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
            <textarea name="content" class="form-control" rows="6" required></textarea>
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
