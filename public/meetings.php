<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/meetings.php';

require_login();

$slug = $_GET['slug'] ?? '';
if (!$slug) {
  header('Location: /public/dashboard.php');
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE slug = ?");
$stmt->execute([$slug]);
$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
  header('Location: /public/dashboard.php');
  exit;
}

$clubId = (int)$club['id'];
$userId = current_user_id();

if (!can_view_minutes($pdo, $clubId)) {
  header('Location: /public/club.php?slug=' . urlencode($slug));
  exit;
}

$canViewDecisions = can_view_decisions($pdo, $clubId);
$canManage = can_manage_meetings($pdo, $clubId);

$stmt = $pdo->prepare("
  SELECT m.*, 
         (SELECT id FROM meeting_minutes mm WHERE mm.meeting_id = m.id) as has_minutes
  FROM meetings m
  WHERE m.club_id = ? AND m.status = 'completed'
  ORDER BY m.meeting_date DESC
");
$stmt->execute([$clubId]);
$meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedMeetingId = (int)($_GET['meeting_id'] ?? 0);
$selectedMeeting = null;
$minutes = null;
$decisions = [];

if ($selectedMeetingId) {
  $selectedMeeting = get_meeting($pdo, $selectedMeetingId);
  if ($selectedMeeting && (int)$selectedMeeting['club_id'] === $clubId) {
    $minutes = get_meeting_minutes($pdo, $selectedMeetingId);
    if ($canViewDecisions) {
      $decisions = get_meeting_decisions($pdo, $selectedMeetingId);
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meeting Minutes - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --primary-dark: #1e3a5f; --primary: #2d5a87; }
    .navbar-custom { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); }
    .meeting-list-item { border-left: 3px solid transparent; transition: all 0.2s; }
    .meeting-list-item:hover { background: #f8f9fa; border-left-color: var(--primary); }
    .meeting-list-item.active { background: #e9ecef; border-left-color: var(--primary); }
    .minutes-content { white-space: pre-wrap; font-family: inherit; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark navbar-custom">
  <div class="container">
    <a class="navbar-brand" href="/public/club.php?slug=<?= e($slug) ?>">
      <i class="bi bi-arrow-left"></i> <?= e($club['name']) ?>
    </a>
    <?php if ($canManage): ?>
      <a href="/public/admin/meetings.php?club_id=<?= $clubId ?>" class="btn btn-outline-light btn-sm">
        Manage Meetings
      </a>
    <?php endif; ?>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4">Meeting Minutes</h2>
  
  <div class="row">
    <div class="col-md-4">
      <div class="card">
        <div class="card-header bg-white">
          <h6 class="mb-0">Completed Meetings</h6>
        </div>
        <div class="list-group list-group-flush">
          <?php if (empty($meetings)): ?>
            <div class="list-group-item text-center text-muted py-4">
              No completed meetings with minutes yet.
            </div>
          <?php else: ?>
            <?php foreach ($meetings as $meeting): ?>
              <a href="?slug=<?= e($slug) ?>&meeting_id=<?= $meeting['id'] ?>" 
                 class="list-group-item list-group-item-action meeting-list-item <?= $selectedMeetingId === (int)$meeting['id'] ? 'active' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <strong><?= e($meeting['title']) ?></strong><br>
                    <small class="text-muted"><?= date('j M Y', strtotime($meeting['meeting_date'])) ?></small>
                  </div>
                  <?php if ($meeting['has_minutes']): ?>
                    <span class="badge bg-success"><i class="bi bi-file-text"></i></span>
                  <?php endif; ?>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="col-md-8">
      <?php if ($selectedMeeting && $minutes): ?>
        <div class="card mb-4">
          <div class="card-header bg-white">
            <h5 class="mb-1"><?= e($selectedMeeting['title']) ?></h5>
            <small class="text-muted">
              <?= date('l, j F Y', strtotime($selectedMeeting['meeting_date'])) ?>
              <?php if ($selectedMeeting['meeting_time']): ?>
                at <?= date('g:i A', strtotime($selectedMeeting['meeting_time'])) ?>
              <?php endif; ?>
              <?php if ($selectedMeeting['location']): ?>
                &bull; <?= e($selectedMeeting['location']) ?>
              <?php endif; ?>
            </small>
          </div>
          <div class="card-body">
            <?php if ($minutes['attendees']): ?>
              <p><strong>Attendees:</strong> <?= e($minutes['attendees']) ?></p>
            <?php endif; ?>
            <?php if ($minutes['apologies']): ?>
              <p><strong>Apologies:</strong> <?= e($minutes['apologies']) ?></p>
            <?php endif; ?>
            
            <hr>
            
            <div class="minutes-content"><?= nl2br(e($minutes['content'])) ?></div>
          </div>
        </div>
        
        <?php if ($canViewDecisions && !empty($decisions)): ?>
          <div class="card">
            <div class="card-header bg-white">
              <h6 class="mb-0">Decisions Made</h6>
            </div>
            <div class="card-body">
              <?php foreach ($decisions as $decision): ?>
                <div class="border-bottom pb-3 mb-3">
                  <div class="d-flex justify-content-between">
                    <strong><?= e($decision['title']) ?></strong>
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
                  <?php if ($decision['description']): ?>
                    <p class="text-muted small mb-0 mt-1"><?= nl2br(e($decision['description'])) ?></p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        
      <?php elseif ($selectedMeeting): ?>
        <div class="card">
          <div class="card-body text-center py-5">
            <i class="bi bi-file-text fs-1 text-muted"></i>
            <h5 class="mt-3">No minutes available</h5>
            <p class="text-muted">Minutes for this meeting haven't been recorded yet.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="card">
          <div class="card-body text-center py-5">
            <i class="bi bi-arrow-left-circle fs-1 text-muted"></i>
            <h5 class="mt-3">Select a meeting</h5>
            <p class="text-muted">Choose a meeting from the list to view its minutes.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
