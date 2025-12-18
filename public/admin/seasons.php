<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

require_login();

$userId = current_user_id();
$clubId = (int)($_GET['club_id'] ?? 0);
$message = '';
$messageType = '';

if (!$clubId) {
  http_response_code(400);
  exit('Club ID required');
}

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
$stmt->execute([$clubId]);
$club = $stmt->fetch();

if (!$club) {
  http_response_code(404);
  exit('Club not found');
}

$stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
$stmt->execute([$clubId, $userId]);
$adminRow = $stmt->fetch();

if (!$adminRow) {
  http_response_code(403);
  exit('Admin access required');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'create_season') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $scoringType = $_POST['scoring_type'] ?? 'total_points';
    $bestNCount = (int)($_POST['best_n_count'] ?? 5);
    
    if ($name && $startDate && $endDate) {
      $stmt = $pdo->prepare("
        INSERT INTO competition_seasons (club_id, name, description, start_date, end_date, scoring_type, best_n_count)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $name, $description ?: null, $startDate, $endDate, $scoringType, $bestNCount]);
      $message = 'Season created successfully';
      $messageType = 'success';
    }
  } elseif ($action === 'delete_season') {
    $seasonId = (int)($_POST['season_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM competition_seasons WHERE id = ? AND club_id = ?");
    $stmt->execute([$seasonId, $clubId]);
    $message = 'Season deleted';
    $messageType = 'info';
  } elseif ($action === 'toggle_active') {
    $seasonId = (int)($_POST['season_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE competition_seasons SET is_active = NOT is_active WHERE id = ? AND club_id = ?");
    $stmt->execute([$seasonId, $clubId]);
    $message = 'Season status updated';
    $messageType = 'success';
  } elseif ($action === 'add_competition') {
    $seasonId = (int)($_POST['season_id'] ?? 0);
    $competitionId = (int)($_POST['competition_id'] ?? 0);
    
    if ($seasonId && $competitionId) {
      $stmt = $pdo->prepare("UPDATE competitions SET season_id = ? WHERE id = ? AND club_id = ?");
      $stmt->execute([$seasonId, $competitionId, $clubId]);
      $message = 'Competition added to season';
      $messageType = 'success';
    }
  } elseif ($action === 'remove_competition') {
    $competitionId = (int)($_POST['competition_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE competitions SET season_id = NULL WHERE id = ? AND club_id = ?");
    $stmt->execute([$competitionId, $clubId]);
    $message = 'Competition removed from season';
    $messageType = 'info';
  } elseif ($action === 'recalculate_standings') {
    $seasonId = (int)($_POST['season_id'] ?? 0);
    
    $stmt = $pdo->prepare("DELETE FROM season_standings WHERE season_id = ?");
    $stmt->execute([$seasonId]);
    
    $stmt = $pdo->prepare("
      SELECT cr.user_id,
             COUNT(DISTINCT cr.competition_id) as competitions_entered,
             SUM(CASE WHEN cr.position = 1 THEN 1 ELSE 0 END) as wins,
             SUM(CASE WHEN cr.position <= 3 THEN 1 ELSE 0 END) as podiums,
             SUM(cr.total_weight_kg) as total_weight,
             SUM(cr.points_earned) as total_points
      FROM competition_results cr
      JOIN competitions c ON cr.competition_id = c.id
      WHERE c.season_id = ? AND c.club_id = ?
      GROUP BY cr.user_id
    ");
    $stmt->execute([$seasonId, $clubId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $r) {
      $stmt = $pdo->prepare("
        INSERT INTO season_standings (season_id, user_id, total_points, total_weight_kg, competitions_entered, wins, podiums)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([
        $seasonId,
        $r['user_id'],
        $r['total_points'] ?? 0,
        $r['total_weight'] ?? 0,
        $r['competitions_entered'],
        $r['wins'],
        $r['podiums']
      ]);
    }
    
    $message = 'Standings recalculated';
    $messageType = 'success';
  }
}

$stmt = $pdo->prepare("SELECT * FROM competition_seasons WHERE club_id = ? ORDER BY start_date DESC");
$stmt->execute([$clubId]);
$seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM competitions WHERE club_id = ? AND season_id IS NULL ORDER BY competition_date DESC");
$stmt->execute([$clubId]);
$unassignedCompetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scoringTypes = [
  'total_points' => 'Total Points (sum all competition points)',
  'total_weight' => 'Total Weight (sum all weights)',
  'best_n' => 'Best N Results (top results count)',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Competition Seasons - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/club.php?slug=<?= e($club['slug']) ?>">View Club</a>
      <a class="btn btn-outline-light btn-sm" href="/public/admin/competitions.php?club_id=<?= $clubId ?>">Competitions</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="mb-1">Competition Seasons</h1>
      <p class="text-muted mb-0"><?= e($club['name']) ?></p>
    </div>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="row">
    <div class="col-lg-5 mb-4">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Create New Season</h5>
        </div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="action" value="create_season">
            
            <div class="mb-3">
              <label class="form-label">Season Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" required placeholder="e.g. 2025 Spring League">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
            
            <div class="row mb-3">
              <div class="col-6">
                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="start_date" required>
              </div>
              <div class="col-6">
                <label class="form-label">End Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="end_date" required>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Scoring Method</label>
              <select class="form-select" name="scoring_type">
                <?php foreach ($scoringTypes as $value => $label): ?>
                  <option value="<?= $value ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Best N Count</label>
              <input type="number" class="form-control" name="best_n_count" value="5" min="1" max="20">
              <div class="form-text">For "Best N Results" scoring only</div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Create Season</button>
          </form>
        </div>
      </div>
    </div>
    
    <div class="col-lg-7 mb-4">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Seasons</h5>
        </div>
        <div class="card-body">
          <?php if (empty($seasons)): ?>
            <p class="text-muted text-center mb-0">No seasons created yet.</p>
          <?php else: ?>
            <?php foreach ($seasons as $season): ?>
              <?php
              $stmt = $pdo->prepare("SELECT COUNT(*) FROM competitions WHERE season_id = ?");
              $stmt->execute([$season['id']]);
              $compCount = (int)$stmt->fetchColumn();
              
              $stmt = $pdo->prepare("
                SELECT ss.*, u.name as angler_name
                FROM season_standings ss
                JOIN users u ON ss.user_id = u.id
                WHERE ss.season_id = ?
                ORDER BY ss.total_points DESC, ss.total_weight_kg DESC
                LIMIT 5
              ");
              $stmt->execute([$season['id']]);
              $standings = $stmt->fetchAll(PDO::FETCH_ASSOC);
              ?>
              <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center <?= $season['is_active'] ? 'bg-success text-white' : 'bg-light' ?>">
                  <div>
                    <strong><?= e($season['name']) ?></strong>
                    <?php if (!$season['is_active']): ?>
                      <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                  </div>
                  <div>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="season_id" value="<?= $season['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= $season['is_active'] ? 'btn-light' : 'btn-success' ?>">
                        <?= $season['is_active'] ? 'Deactivate' : 'Activate' ?>
                      </button>
                    </form>
                  </div>
                </div>
                <div class="card-body">
                  <p class="small text-muted mb-2">
                    <?= date('j M Y', strtotime($season['start_date'])) ?> - <?= date('j M Y', strtotime($season['end_date'])) ?>
                    &bull; <?= $compCount ?> competition<?= $compCount !== 1 ? 's' : '' ?>
                    &bull; <?= e($scoringTypes[$season['scoring_type']]) ?>
                  </p>
                  
                  <?php if (!empty($standings)): ?>
                    <h6>Current Standings</h6>
                    <table class="table table-sm mb-2">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Angler</th>
                          <th>Points</th>
                          <th>Comps</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($standings as $i => $s): ?>
                          <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($s['angler_name']) ?></td>
                            <td><?= number_format((float)$s['total_points'], 1) ?></td>
                            <td><?= $s['competitions_entered'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  <?php endif; ?>
                  
                  <div class="d-flex gap-2">
                    <form method="post">
                      <input type="hidden" name="action" value="recalculate_standings">
                      <input type="hidden" name="season_id" value="<?= $season['id'] ?>">
                      <button type="submit" class="btn btn-outline-primary btn-sm">Recalculate Standings</button>
                    </form>
                    <a href="/public/leaderboard.php?season_id=<?= $season['id'] ?>" class="btn btn-outline-secondary btn-sm">View Full Leaderboard</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      
      <?php if (!empty($unassignedCompetitions) && !empty($seasons)): ?>
        <div class="card mt-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Add Competitions to Season</h5>
          </div>
          <div class="card-body">
            <form method="post" class="row g-2 align-items-end">
              <input type="hidden" name="action" value="add_competition">
              <div class="col-md-5">
                <label class="form-label">Competition</label>
                <select class="form-select" name="competition_id" required>
                  <option value="">Select...</option>
                  <?php foreach ($unassignedCompetitions as $comp): ?>
                    <option value="<?= $comp['id'] ?>"><?= e($comp['title']) ?> (<?= date('j M Y', strtotime($comp['competition_date'])) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-5">
                <label class="form-label">Season</label>
                <select class="form-select" name="season_id" required>
                  <option value="">Select...</option>
                  <?php foreach ($seasons as $season): ?>
                    <option value="<?= $season['id'] ?>"><?= e($season['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Add</button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
