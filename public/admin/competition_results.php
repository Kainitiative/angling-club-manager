<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

require_login();

$userId = current_user_id();
$competitionId = (int)($_GET['competition_id'] ?? 0);
$message = '';
$messageType = '';

if (!$competitionId) {
  http_response_code(400);
  exit('Competition ID required');
}

$stmt = $pdo->prepare("
  SELECT comp.*, c.name as club_name, c.slug as club_slug, c.id as club_id
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

$stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
$stmt->execute([$competition['club_id'], $userId]);
$adminRow = $stmt->fetch();

if (!$adminRow) {
  http_response_code(403);
  exit('You are not an admin of this club');
}

$today = date('Y-m-d');
$isPastCompetition = $competition['competition_date'] <= $today;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isPastCompetition) {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add_result') {
    $competitorName = trim($_POST['competitor_name'] ?? '');
    $fishCount = (int)($_POST['fish_count'] ?? 0);
    $totalWeight = (float)($_POST['total_weight'] ?? 0);
    $totalScore = (float)($_POST['total_score'] ?? 0);
    $position = trim($_POST['position'] ?? '') !== '' ? (int)$_POST['position'] : null;
    $notes = trim($_POST['notes'] ?? '');
    
    if ($competitorName === '') {
      $message = 'Competitor name is required.';
      $messageType = 'danger';
    } else {
      try {
        $stmt = $pdo->prepare("
          INSERT INTO competition_results (competition_id, competitor_name, fish_count, total_weight, total_score, position, notes)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
          $competitionId,
          $competitorName,
          $fishCount,
          $totalWeight,
          $totalScore,
          $position,
          $notes ?: null
        ]);
        $message = 'Result added successfully.';
        $messageType = 'success';
      } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || $e->getCode() == 23000) {
          $message = 'This competitor already has a result recorded.';
          $messageType = 'warning';
        } else {
          throw $e;
        }
      }
    }
  }
  
  if ($action === 'delete_result' && isset($_POST['result_id'])) {
    $resultId = (int)$_POST['result_id'];
    $stmt = $pdo->prepare("DELETE FROM competition_results WHERE id = ? AND competition_id = ?");
    $stmt->execute([$resultId, $competitionId]);
    $message = 'Result deleted.';
    $messageType = 'info';
  }
  
  if ($action === 'update_result' && isset($_POST['result_id'])) {
    $resultId = (int)$_POST['result_id'];
    $competitorName = trim($_POST['competitor_name'] ?? '');
    $fishCount = (int)($_POST['fish_count'] ?? 0);
    $totalWeight = (float)($_POST['total_weight'] ?? 0);
    $totalScore = (float)($_POST['total_score'] ?? 0);
    $position = trim($_POST['position'] ?? '') !== '' ? (int)$_POST['position'] : null;
    $notes = trim($_POST['notes'] ?? '');
    
    if ($competitorName !== '') {
      $stmt = $pdo->prepare("
        UPDATE competition_results 
        SET competitor_name = ?, fish_count = ?, total_weight = ?, total_score = ?, position = ?, notes = ?
        WHERE id = ? AND competition_id = ?
      ");
      $stmt->execute([
        $competitorName,
        $fishCount,
        $totalWeight,
        $totalScore,
        $position,
        $notes ?: null,
        $resultId,
        $competitionId
      ]);
      $message = 'Result updated.';
      $messageType = 'success';
    }
  }
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
    .result-row:nth-child(1) { background: linear-gradient(90deg, #ffd700 0%, #fff 30%); }
    .result-row:nth-child(2) { background: linear-gradient(90deg, #c0c0c0 0%, #fff 30%); }
    .result-row:nth-child(3) { background: linear-gradient(90deg, #cd7f32 0%, #fff 30%); }
    .position-badge { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; }
    .position-1 { background: #ffd700; color: #000; }
    .position-2 { background: #c0c0c0; color: #000; }
    .position-3 { background: #cd7f32; color: #fff; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Ireland</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/admin/competitions.php?club_id=<?= $competition['club_id'] ?>">Back to Competitions</a>
      <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="card mb-4">
    <div class="card-body">
      <h1 class="h3 mb-1"><?= e($competition['title']) ?></h1>
      <p class="text-muted mb-2">
        <?= e($competition['venue_name']) ?>
        <?php if ($competition['town']): ?>
          &bull; <?= e($competition['town']) ?>
        <?php endif; ?>
      </p>
      <p class="mb-0">
        <strong><?= date('l, j F Y', strtotime($competition['competition_date'])) ?></strong>
        <?php if ($competition['start_time']): ?>
          at <?= date('g:i A', strtotime($competition['start_time'])) ?>
        <?php endif; ?>
      </p>
      <p class="text-muted small mb-0">
        Hosted by <a href="/public/club.php?slug=<?= e($competition['club_slug']) ?>"><?= e($competition['club_name']) ?></a>
      </p>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (!$isPastCompetition): ?>
    <div class="alert alert-warning">
      Results can only be added after the competition date (<?= date('j F Y', strtotime($competition['competition_date'])) ?>).
    </div>
  <?php endif; ?>

  <div class="row">
    <?php if ($isPastCompetition): ?>
    <div class="col-lg-4 mb-4">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Add Result</h5>
        </div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="action" value="add_result">
            
            <div class="mb-3">
              <label class="form-label">Competitor Name <span class="text-danger">*</span></label>
              <input type="text" name="competitor_name" class="form-control" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Position</label>
              <input type="number" name="position" class="form-control" min="1" placeholder="e.g. 1, 2, 3...">
            </div>
            
            <div class="row">
              <div class="col-6 mb-3">
                <label class="form-label">Fish Caught</label>
                <input type="number" name="fish_count" class="form-control" min="0" value="0">
              </div>
              <div class="col-6 mb-3">
                <label class="form-label">Total Weight (kg)</label>
                <input type="number" name="total_weight" class="form-control" min="0" step="0.01" value="0">
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Total Score</label>
              <input type="number" name="total_score" class="form-control" min="0" step="0.01" value="0">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Add Result</button>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <div class="<?= $isPastCompetition ? 'col-lg-8' : 'col-12' ?>">
      <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Results</h5>
          <span class="badge bg-primary"><?= count($results) ?> Competitors</span>
        </div>
        <div class="card-body p-0">
          <?php if (empty($results)): ?>
            <div class="p-4 text-center text-muted">
              No results recorded yet.
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 60px;">Pos</th>
                    <th>Competitor</th>
                    <th class="text-center">Fish</th>
                    <th class="text-center">Weight (kg)</th>
                    <th class="text-center">Score</th>
                    <?php if ($isPastCompetition): ?>
                      <th style="width: 100px;"></th>
                    <?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($results as $idx => $result): ?>
                    <tr class="result-row">
                      <td>
                        <?php if ($result['position']): ?>
                          <?php if ($result['position'] <= 3): ?>
                            <span class="position-badge position-<?= $result['position'] ?>"><?= $result['position'] ?></span>
                          <?php else: ?>
                            <span class="text-muted"><?= $result['position'] ?></span>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="text-muted"><?= $idx + 1 ?></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <strong><?= e($result['competitor_name']) ?></strong>
                        <?php if ($result['notes']): ?>
                          <br><small class="text-muted"><?= e($result['notes']) ?></small>
                        <?php endif; ?>
                      </td>
                      <td class="text-center"><?= $result['fish_count'] ?></td>
                      <td class="text-center"><?= number_format((float)$result['total_weight'], 2) ?></td>
                      <td class="text-center"><strong><?= number_format((float)$result['total_score'], 2) ?></strong></td>
                      <?php if ($isPastCompetition): ?>
                        <td>
                          <form method="post" class="d-inline" onsubmit="return confirm('Delete this result?');">
                            <input type="hidden" name="action" value="delete_result">
                            <input type="hidden" name="result_id" value="<?= $result['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                          </form>
                        </td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
