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

$stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
$stmt->execute([$clubId, $userId]);
$memberRow = $stmt->fetch();

$isAdmin = (bool)$adminRow;
$isCommittee = $memberRow && $memberRow['committee_role'] !== 'member';

if (!$isAdmin && !$isCommittee) {
  http_response_code(403);
  exit('Committee access required');
}

$stmt = $pdo->prepare("SELECT * FROM competition_seasons WHERE club_id = ? ORDER BY start_date DESC");
$stmt->execute([$clubId]);
$seasons = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'create_leaderboard') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $metricType = $_POST['metric_type'] ?? 'competition_points';
    $timeScope = $_POST['time_scope'] ?? 'this_year';
    $startDate = $_POST['start_date'] ?: null;
    $endDate = $_POST['end_date'] ?: null;
    $seasonId = (int)($_POST['season_id'] ?? 0) ?: null;
    
    if ($name) {
      $stmt = $pdo->prepare("
        INSERT INTO club_leaderboards (club_id, name, description, metric_type, time_scope, start_date, end_date, season_id, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $name, $description ?: null, $metricType, $timeScope, $startDate, $endDate, $seasonId, $userId]);
      
      $leaderboardId = $pdo->lastInsertId();
      recalculateLeaderboard($pdo, (int)$leaderboardId);
      
      $message = 'Leaderboard created successfully';
      $messageType = 'success';
    }
  } elseif ($action === 'delete_leaderboard') {
    $leaderboardId = (int)($_POST['leaderboard_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM club_leaderboards WHERE id = ? AND club_id = ?");
    $stmt->execute([$leaderboardId, $clubId]);
    $message = 'Leaderboard deleted';
    $messageType = 'info';
  } elseif ($action === 'toggle_active') {
    $leaderboardId = (int)($_POST['leaderboard_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE club_leaderboards SET is_active = NOT is_active WHERE id = ? AND club_id = ?");
    $stmt->execute([$leaderboardId, $clubId]);
    $message = 'Leaderboard status updated';
    $messageType = 'success';
  } elseif ($action === 'recalculate') {
    $leaderboardId = (int)($_POST['leaderboard_id'] ?? 0);
    recalculateLeaderboard($pdo, $leaderboardId);
    $message = 'Leaderboard rankings recalculated';
    $messageType = 'success';
  }
}

function recalculateLeaderboard(PDO $pdo, int $leaderboardId): void {
  $stmt = $pdo->prepare("SELECT * FROM club_leaderboards WHERE id = ?");
  $stmt->execute([$leaderboardId]);
  $lb = $stmt->fetch();
  
  if (!$lb) return;
  
  $pdo->prepare("DELETE FROM leaderboard_entries WHERE leaderboard_id = ?")->execute([$leaderboardId]);
  
  $clubId = $lb['club_id'];
  $metricType = $lb['metric_type'];
  $timeScope = $lb['time_scope'];
  
  $dateFilter = '';
  $params = [$clubId];
  $isPostgres = defined('DB_DRIVER') && DB_DRIVER === 'pgsql';
  
  if ($timeScope === 'this_year') {
    $dateFilter = $isPostgres 
      ? "AND EXTRACT(YEAR FROM cl.catch_date) = EXTRACT(YEAR FROM CURRENT_DATE)"
      : "AND YEAR(cl.catch_date) = YEAR(CURDATE())";
  } elseif ($timeScope === 'custom' && $lb['start_date'] && $lb['end_date']) {
    $dateFilter = "AND cl.catch_date BETWEEN ? AND ?";
    $params[] = $lb['start_date'];
    $params[] = $lb['end_date'];
  }
  
  $entries = [];
  
  if ($metricType === 'total_catches') {
    $sql = "
      SELECT cm.user_id, COUNT(cl.id) as score, COUNT(cl.id) as catches_count
      FROM club_members cm
      LEFT JOIN catch_logs cl ON cl.user_id = cm.user_id AND cl.club_id = cm.club_id $dateFilter
      WHERE cm.club_id = ? AND cm.membership_status = 'active'
      GROUP BY cm.user_id
      ORDER BY score DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $entries = $stmt->fetchAll();
    
  } elseif ($metricType === 'total_weight') {
    $sql = "
      SELECT cm.user_id, COALESCE(SUM(cl.weight_kg), 0) as score, COUNT(cl.id) as catches_count
      FROM club_members cm
      LEFT JOIN catch_logs cl ON cl.user_id = cm.user_id AND cl.club_id = cm.club_id $dateFilter
      WHERE cm.club_id = ? AND cm.membership_status = 'active'
      GROUP BY cm.user_id
      ORDER BY score DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $entries = $stmt->fetchAll();
    
  } elseif ($metricType === 'biggest_fish') {
    $sql = "
      SELECT cm.user_id, COALESCE(MAX(cl.weight_kg), 0) as score, COUNT(cl.id) as catches_count
      FROM club_members cm
      LEFT JOIN catch_logs cl ON cl.user_id = cm.user_id AND cl.club_id = cm.club_id $dateFilter
      WHERE cm.club_id = ? AND cm.membership_status = 'active'
      GROUP BY cm.user_id
      ORDER BY score DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $entries = $stmt->fetchAll();
    
  } elseif ($metricType === 'species_count') {
    $sql = "
      SELECT cm.user_id, COUNT(DISTINCT cl.species) as score, COUNT(cl.id) as catches_count
      FROM club_members cm
      LEFT JOIN catch_logs cl ON cl.user_id = cm.user_id AND cl.club_id = cm.club_id $dateFilter
      WHERE cm.club_id = ? AND cm.membership_status = 'active'
      GROUP BY cm.user_id
      ORDER BY score DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $entries = $stmt->fetchAll();
    
  } elseif ($metricType === 'competition_points') {
    if ($lb['season_id']) {
      $sql = "
        SELECT ss.user_id, ss.total_points as score, ss.competitions_entered as competitions_count, 0 as catches_count
        FROM season_standings ss
        WHERE ss.season_id = ?
        ORDER BY ss.total_points DESC
      ";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$lb['season_id']]);
      $entries = $stmt->fetchAll();
    } else {
      $sql = "
        SELECT cm.user_id, COALESCE(SUM(ss.total_points), 0) as score, 
               COALESCE(SUM(ss.competitions_entered), 0) as competitions_count, 0 as catches_count
        FROM club_members cm
        LEFT JOIN season_standings ss ON ss.user_id = cm.user_id
        LEFT JOIN competition_seasons cs ON ss.season_id = cs.id AND cs.club_id = cm.club_id
        WHERE cm.club_id = ? AND cm.membership_status = 'active'
        GROUP BY cm.user_id
        ORDER BY score DESC
      ";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$clubId]);
      $entries = $stmt->fetchAll();
    }
  }
  
  $rank = 1;
  foreach ($entries as $entry) {
    $stmt = $pdo->prepare("
      INSERT INTO leaderboard_entries (leaderboard_id, user_id, score, rank_position, competitions_count, catches_count, calculated_at)
      VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
      $leaderboardId,
      $entry['user_id'],
      $entry['score'],
      $rank,
      $entry['competitions_count'] ?? 0,
      $entry['catches_count'] ?? 0
    ]);
    $rank++;
  }
}

$stmt = $pdo->prepare("
  SELECT cl.*, 
         (SELECT COUNT(*) FROM leaderboard_entries WHERE leaderboard_id = cl.id) as entry_count,
         cs.name as season_name
  FROM club_leaderboards cl
  LEFT JOIN competition_seasons cs ON cl.season_id = cs.id
  WHERE cl.club_id = ?
  ORDER BY cl.display_order, cl.created_at DESC
");
$stmt->execute([$clubId]);
$leaderboards = $stmt->fetchAll();

$metricLabels = [
  'competition_points' => 'Competition Points',
  'total_catches' => 'Total Catches',
  'total_weight' => 'Total Weight (kg)',
  'biggest_fish' => 'Biggest Fish (kg)',
  'species_count' => 'Species Variety'
];

$scopeLabels = [
  'all_time' => 'All Time',
  'this_year' => 'This Year',
  'this_season' => 'Current Season',
  'custom' => 'Custom Dates'
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Club Leaderboards - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --primary-dark: #1e3a5f; --primary: #2d5a87; }
    .navbar-custom { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); }
    .leaderboard-card { transition: transform 0.2s, box-shadow 0.2s; border-left: 4px solid var(--primary); }
    .leaderboard-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .leaderboard-card.inactive { opacity: 0.6; border-left-color: #6c757d; }
    .metric-badge { font-size: 0.75rem; }
    .btn-action { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Ireland</a>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-outline-light btn-sm" href="/public/club.php?slug=<?= e($club['slug']) ?>">Back to Club</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1">Club Leaderboards</h1>
      <p class="text-muted mb-0"><?= e($club['name']) ?> - Manage member rankings</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
      <i class="bi bi-plus-lg me-1"></i> New Leaderboard
    </button>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <?php if (empty($leaderboards)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-trophy fs-1 text-muted"></i>
        <h5 class="mt-3">No Leaderboards Yet</h5>
        <p class="text-muted mb-3">Create your first club leaderboard to track member rankings.</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
          <i class="bi bi-plus-lg me-1"></i> Create Leaderboard
        </button>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($leaderboards as $lb): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card leaderboard-card h-100 <?= $lb['is_active'] ? '' : 'inactive' ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0"><?= e($lb['name']) ?></h5>
                <?php if (!$lb['is_active']): ?>
                  <span class="badge bg-secondary">Inactive</span>
                <?php endif; ?>
              </div>
              
              <div class="mb-3">
                <span class="badge bg-primary metric-badge"><?= $metricLabels[$lb['metric_type']] ?? $lb['metric_type'] ?></span>
                <span class="badge bg-outline-secondary metric-badge border"><?= $scopeLabels[$lb['time_scope']] ?? $lb['time_scope'] ?></span>
              </div>
              
              <?php if ($lb['description']): ?>
                <p class="card-text text-muted small"><?= e($lb['description']) ?></p>
              <?php endif; ?>
              
              <?php if ($lb['season_name']): ?>
                <p class="small text-muted mb-2"><i class="bi bi-calendar-event me-1"></i> <?= e($lb['season_name']) ?></p>
              <?php endif; ?>
              
              <p class="small text-muted mb-3">
                <i class="bi bi-people me-1"></i> <?= $lb['entry_count'] ?> members ranked
              </p>
              
              <div class="d-flex gap-2 flex-wrap">
                <a href="/public/club_leaderboard.php?id=<?= $lb['id'] ?>" class="btn btn-outline-primary btn-action">
                  <i class="bi bi-eye me-1"></i> View
                </a>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="recalculate">
                  <input type="hidden" name="leaderboard_id" value="<?= $lb['id'] ?>">
                  <button type="submit" class="btn btn-outline-secondary btn-action">
                    <i class="bi bi-arrow-repeat me-1"></i> Refresh
                  </button>
                </form>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="leaderboard_id" value="<?= $lb['id'] ?>">
                  <button type="submit" class="btn btn-outline-warning btn-action">
                    <i class="bi bi-<?= $lb['is_active'] ? 'pause' : 'play' ?>"></i>
                  </button>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this leaderboard?')">
                  <input type="hidden" name="action" value="delete_leaderboard">
                  <input type="hidden" name="leaderboard_id" value="<?= $lb['id'] ?>">
                  <button type="submit" class="btn btn-outline-danger btn-action">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="create_leaderboard">
        <div class="modal-header">
          <h5 class="modal-title">Create Leaderboard</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Leaderboard Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g., 2025 Season Rankings">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Optional description..."></textarea>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Ranking Metric</label>
            <select name="metric_type" class="form-select" id="metricType">
              <option value="competition_points">Competition Points</option>
              <option value="total_catches">Total Catches Logged</option>
              <option value="total_weight">Total Weight Caught (kg)</option>
              <option value="biggest_fish">Biggest Single Fish (kg)</option>
              <option value="species_count">Species Variety (count)</option>
            </select>
            <div class="form-text">How members are ranked on this leaderboard.</div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Time Period</label>
            <select name="time_scope" class="form-select" id="timeScope" onchange="toggleDateFields()">
              <option value="this_year">This Year (<?= date('Y') ?>)</option>
              <option value="all_time">All Time</option>
              <option value="custom">Custom Date Range</option>
            </select>
            <div class="form-text">Tip: For season-specific rankings, use "Competition Points" with a linked season.</div>
          </div>
          
          <div id="dateFields" style="display: none;">
            <div class="row">
              <div class="col-6">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control">
              </div>
              <div class="col-6">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control">
              </div>
            </div>
          </div>
          
          <div class="mb-3" id="seasonField">
            <label class="form-label">Link to Season (Optional)</label>
            <select name="season_id" class="form-select">
              <option value="">-- No specific season --</option>
              <?php foreach ($seasons as $season): ?>
                <option value="<?= $season['id'] ?>"><?= e($season['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">For competition points, link to a specific season.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Leaderboard</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleDateFields() {
  const scope = document.getElementById('timeScope').value;
  document.getElementById('dateFields').style.display = scope === 'custom' ? 'block' : 'none';
}
</script>
</body>
</html>
