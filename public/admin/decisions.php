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

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'delete_decision') {
    $decisionId = (int)($_POST['decision_id'] ?? 0);
    if ($decisionId) {
      $stmt = $pdo->prepare("DELETE FROM meeting_decisions WHERE id = ? AND club_id = ?");
      $stmt->execute([$decisionId, $clubId]);
      $message = 'Decision deleted.';
      $messageType = 'success';
    }
  }
}

$decisions = get_club_decisions($pdo, $clubId, 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Decisions - <?= e($club['name']) ?></title>
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
      <h2 class="mb-1">Club Decisions</h2>
      <p class="text-muted mb-0"><?= count($decisions) ?> decision<?= count($decisions) !== 1 ? 's' : '' ?> recorded</p>
    </div>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Decisions are visible to committee members only (Chairperson, Secretary, Treasurer, PRO, and Admins).
  </div>
  
  <?php if (empty($decisions)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-clipboard-check fs-1 text-muted"></i>
        <h5 class="mt-3">No decisions yet</h5>
        <p class="text-muted">Record decisions during meetings to keep track of club governance.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Decision</th>
              <th>Date</th>
              <th>Meeting</th>
              <th>Status</th>
              <th>Proposed By</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($decisions as $decision): ?>
              <tr>
                <td>
                  <strong><?= e($decision['title']) ?></strong>
                  <?php if ($decision['description']): ?>
                    <br><small class="text-muted"><?= e(substr($decision['description'], 0, 100)) ?><?= strlen($decision['description']) > 100 ? '...' : '' ?></small>
                  <?php endif; ?>
                </td>
                <td><?= date('j M Y', strtotime($decision['decision_date'])) ?></td>
                <td>
                  <?php if ($decision['meeting_title']): ?>
                    <?= e($decision['meeting_title']) ?>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php 
                    $statusBadge = match($decision['status']) {
                      'approved' => 'success',
                      'rejected' => 'danger',
                      'deferred' => 'warning',
                      default => 'secondary'
                    };
                  ?>
                  <span class="badge bg-<?= $statusBadge ?>"><?= ucfirst($decision['status']) ?></span>
                </td>
                <td><?= e($decision['proposed_by'] ?? '-') ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Delete this decision?');">
                    <input type="hidden" name="action" value="delete_decision">
                    <input type="hidden" name="decision_id" value="<?= $decision['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
