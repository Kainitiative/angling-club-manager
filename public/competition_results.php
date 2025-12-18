<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$competitionId = (int)($_GET['id'] ?? 0);

if (!$competitionId) {
  http_response_code(400);
  exit('Competition ID required');
}

$stmt = $pdo->prepare("
  SELECT comp.*, c.name as club_name, c.slug as club_slug, c.id as club_id, c.is_public as club_is_public
  FROM competitions comp
  JOIN clubs c ON comp.club_id = c.id
  WHERE comp.id = ?
");
$stmt->execute([$competitionId]);
$competition = $stmt->fetch();

if (!$competition) {
  http_response_code(404);
  exit('Competition not found');
}

$userId = current_user_id();
$isLoggedIn = (bool)$userId;
$isAdmin = false;
$isMember = false;

if ($userId) {
  $stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$competition['club_id'], $userId]);
  $isAdmin = (bool)$stmt->fetch();
  
  $stmt = $pdo->prepare("SELECT membership_status FROM club_members WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$competition['club_id'], $userId]);
  $memberRow = $stmt->fetch();
  $isMember = $memberRow && $memberRow['membership_status'] === 'active';
}

if ($competition['visibility'] === 'private' && !$isAdmin && !$isMember) {
  http_response_code(403);
  exit('This competition is private. Only club members can view results.');
}

$stmt = $pdo->prepare("
  SELECT * FROM competition_results 
  WHERE competition_id = ? 
  ORDER BY position ASC, total_score DESC, fish_count DESC
");
$stmt->execute([$competitionId]);
$results = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Results - <?= e($competition['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .results-header {
      background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
      color: white;
      padding: 40px 0;
    }
    .result-row:nth-child(1) td { background: linear-gradient(90deg, rgba(255,215,0,0.3) 0%, transparent 50%); }
    .result-row:nth-child(2) td { background: linear-gradient(90deg, rgba(192,192,192,0.3) 0%, transparent 50%); }
    .result-row:nth-child(3) td { background: linear-gradient(90deg, rgba(205,127,50,0.3) 0%, transparent 50%); }
    .position-badge { 
      width: 36px; 
      height: 36px; 
      border-radius: 50%; 
      display: inline-flex; 
      align-items: center; 
      justify-content: center; 
      font-weight: bold;
      font-size: 1rem;
    }
    .position-1 { background: #ffd700; color: #000; }
    .position-2 { background: #c0c0c0; color: #000; }
    .position-3 { background: #cd7f32; color: #fff; }
    .position-other { background: #e9ecef; color: #495057; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <?php if ($isLoggedIn): ?>
        <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
      <?php else: ?>
        <a class="btn btn-outline-light btn-sm" href="/">Log In</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<section class="results-header">
  <div class="container">
    <h1 class="display-6 fw-bold mb-2"><?= e($competition['title']) ?></h1>
    <p class="lead mb-1 opacity-75">
      <?= e($competition['venue_name']) ?>
      <?php if ($competition['town']): ?>
        &bull; <?= e($competition['town']) ?>
      <?php endif; ?>
    </p>
    <p class="mb-0">
      <?= date('l, j F Y', strtotime($competition['competition_date'])) ?>
      <?php if ($competition['start_time']): ?>
        at <?= date('g:i A', strtotime($competition['start_time'])) ?>
      <?php endif; ?>
    </p>
    <p class="mt-2 mb-0">
      <a href="/public/club.php?slug=<?= e($competition['club_slug']) ?>" class="text-white opacity-75">
        Hosted by <?= e($competition['club_name']) ?>
      </a>
    </p>
  </div>
</section>

<div class="container py-4">
  <div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Competition Results</h5>
      <span class="badge bg-primary"><?= count($results) ?> Competitor<?= count($results) !== 1 ? 's' : '' ?></span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($results)): ?>
        <div class="p-5 text-center text-muted">
          <p class="mb-0">No results have been recorded for this competition yet.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 70px;" class="text-center">Position</th>
                <th>Competitor</th>
                <th class="text-center">Fish Caught</th>
                <th class="text-center">Weight (kg)</th>
                <th class="text-center">Score</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $idx => $result): ?>
                <tr class="result-row">
                  <td class="text-center align-middle">
                    <?php 
                      $pos = $result['position'] ?: ($idx + 1);
                      $posClass = $pos <= 3 ? "position-{$pos}" : 'position-other';
                    ?>
                    <span class="position-badge <?= $posClass ?>"><?= $pos ?></span>
                  </td>
                  <td class="align-middle">
                    <strong><?= e($result['competitor_name']) ?></strong>
                    <?php if ($result['notes']): ?>
                      <br><small class="text-muted"><?= e($result['notes']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td class="text-center align-middle"><?= $result['fish_count'] ?></td>
                  <td class="text-center align-middle"><?= number_format((float)$result['total_weight'], 2) ?></td>
                  <td class="text-center align-middle"><strong><?= number_format((float)$result['total_score'], 2) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <div class="text-center mt-4">
    <a href="/public/club.php?slug=<?= e($competition['club_slug']) ?>" class="btn btn-outline-primary">
      Back to <?= e($competition['club_name']) ?>
    </a>
  </div>
</div>

<footer class="bg-dark text-white py-4 mt-5">
  <div class="container text-center">
    <p class="mb-0">Powered by Angling Club Manager</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
