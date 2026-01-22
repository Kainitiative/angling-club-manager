<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

require_login();

$leaderboardId = (int)($_GET['id'] ?? 0);

if (!$leaderboardId) {
  header('Location: /public/dashboard.php');
  exit;
}

$stmt = $pdo->prepare("
  SELECT cl.*, c.name as club_name, c.slug as club_slug, c.logo_url,
         cs.name as season_name
  FROM club_leaderboards cl
  JOIN clubs c ON cl.club_id = c.id
  LEFT JOIN competition_seasons cs ON cl.season_id = cs.id
  WHERE cl.id = ?
");
$stmt->execute([$leaderboardId]);
$leaderboard = $stmt->fetch();

if (!$leaderboard) {
  http_response_code(404);
  exit('Leaderboard not found');
}

$userId = current_user_id();
$clubId = $leaderboard['club_id'];

$stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
$stmt->execute([$clubId, $userId]);
$isAdmin = (bool)$stmt->fetch();

$stmt = $pdo->prepare("SELECT membership_status FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
$stmt->execute([$clubId, $userId]);
$isMember = (bool)$stmt->fetch();

if (!$isAdmin && !$isMember) {
  http_response_code(403);
  exit('This leaderboard is only visible to club members');
}

$stmt = $pdo->prepare("
  SELECT le.*, u.name as member_name, u.profile_picture_url, u.town
  FROM leaderboard_entries le
  JOIN users u ON le.user_id = u.id
  WHERE le.leaderboard_id = ?
  ORDER BY le.rank_position ASC
");
$stmt->execute([$leaderboardId]);
$entries = $stmt->fetchAll();

$metricLabels = [
  'competition_points' => 'Points',
  'total_catches' => 'Catches',
  'total_weight' => 'Weight (kg)',
  'biggest_fish' => 'Biggest (kg)',
  'species_count' => 'Species'
];

$metricIcons = [
  'competition_points' => 'trophy',
  'total_catches' => 'water',
  'total_weight' => 'speedometer2',
  'biggest_fish' => 'award',
  'species_count' => 'collection'
];

$scopeLabels = [
  'all_time' => 'All Time',
  'this_year' => date('Y'),
  'this_season' => 'Current Season',
  'custom' => 'Custom Period'
];

$currentUserRank = null;
foreach ($entries as $entry) {
  if ($entry['user_id'] == $userId) {
    $currentUserRank = $entry;
    break;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($leaderboard['name']) ?> - <?= e($leaderboard['club_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { 
      --primary-dark: #1e3a5f; 
      --primary: #2d5a87;
      --gold: #ffd700;
      --silver: #c0c0c0;
      --bronze: #cd7f32;
    }
    .navbar-custom { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); }
    
    .hero-section {
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
      color: white;
      padding: 3rem 0;
      margin-bottom: 2rem;
    }
    
    .podium-section { margin-bottom: 2rem; }
    
    .podium-card {
      text-align: center;
      border-radius: 12px;
      padding: 1.5rem;
      transition: transform 0.3s;
    }
    .podium-card:hover { transform: translateY(-5px); }
    
    .podium-1 { 
      background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%); 
      border: 2px solid var(--gold);
      order: 2;
    }
    .podium-2 { 
      background: linear-gradient(135deg, #dfe6e9 0%, #b2bec3 100%); 
      border: 2px solid var(--silver);
      order: 1;
      margin-top: 1.5rem;
    }
    .podium-3 { 
      background: linear-gradient(135deg, #fab1a0 0%, #e17055 100%); 
      border: 2px solid var(--bronze);
      order: 3;
      margin-top: 1.5rem;
    }
    
    .podium-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid white;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      margin-bottom: 1rem;
    }
    .podium-avatar-placeholder {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: rgba(255,255,255,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: rgba(0,0,0,0.3);
      margin: 0 auto 1rem;
      border: 3px solid white;
    }
    
    .podium-rank {
      font-size: 1.5rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }
    .podium-1 .podium-rank { color: #b7791f; }
    .podium-2 .podium-rank { color: #4a5568; }
    .podium-3 .podium-rank { color: #9c4221; }
    
    .podium-score {
      font-size: 1.75rem;
      font-weight: bold;
      color: #2d3748;
    }
    
    .leaderboard-table .rank-cell { width: 60px; font-weight: bold; }
    .leaderboard-table .avatar-cell { width: 50px; }
    .leaderboard-table .score-cell { font-weight: bold; color: var(--primary-dark); }
    
    .leaderboard-row { transition: background 0.2s; }
    .leaderboard-row:hover { background: #f8f9fa; }
    .leaderboard-row.current-user { background: #e3f2fd !important; }
    
    .rank-badge {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    .rank-1 { background: var(--gold); color: #744210; }
    .rank-2 { background: var(--silver); color: #2d3748; }
    .rank-3 { background: var(--bronze); color: white; }
    .rank-default { background: #e2e8f0; color: #4a5568; }
    
    .member-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }
    
    .your-position-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 12px;
    }
    
    .stat-card {
      border-radius: 8px;
      text-align: center;
      padding: 1rem;
    }
    
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
    }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Ireland</a>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-outline-light btn-sm" href="/public/club.php?slug=<?= e($leaderboard['club_slug']) ?>">Back to Club</a>
    </div>
  </div>
</nav>

<div class="hero-section">
  <div class="container text-center">
    <?php if ($leaderboard['logo_url']): ?>
      <img src="<?= e($leaderboard['logo_url']) ?>" alt="" class="rounded-circle mb-3" style="width: 60px; height: 60px; object-fit: cover; border: 2px solid white;">
    <?php endif; ?>
    <h1 class="mb-2"><?= e($leaderboard['name']) ?></h1>
    <p class="mb-2 opacity-75"><?= e($leaderboard['club_name']) ?></p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <span class="badge bg-light text-dark">
        <i class="bi bi-<?= $metricIcons[$leaderboard['metric_type']] ?? 'graph-up' ?> me-1"></i>
        <?= $metricLabels[$leaderboard['metric_type']] ?? $leaderboard['metric_type'] ?>
      </span>
      <span class="badge bg-light text-dark">
        <i class="bi bi-calendar me-1"></i>
        <?= $scopeLabels[$leaderboard['time_scope']] ?? $leaderboard['time_scope'] ?>
      </span>
      <?php if ($leaderboard['season_name']): ?>
        <span class="badge bg-light text-dark">
          <i class="bi bi-trophy me-1"></i>
          <?= e($leaderboard['season_name']) ?>
        </span>
      <?php endif; ?>
    </div>
    <?php if ($leaderboard['description']): ?>
      <p class="mt-3 mb-0 opacity-75"><?= e($leaderboard['description']) ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="container pb-5">
  
  <?php if (empty($entries)): ?>
    <div class="card">
      <div class="card-body empty-state">
        <i class="bi bi-trophy fs-1 text-muted"></i>
        <h4 class="mt-3">No Rankings Yet</h4>
        <p class="text-muted">Rankings will appear once members start logging catches or competition results come in.</p>
      </div>
    </div>
  <?php else: ?>
    
    <?php if ($currentUserRank): ?>
      <div class="your-position-card p-4 mb-4">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="rank-badge rank-default" style="width: 50px; height: 50px; font-size: 1.25rem; background: rgba(255,255,255,0.2); color: white;">
              <?= $currentUserRank['rank_position'] ?>
            </div>
          </div>
          <div class="col">
            <h5 class="mb-1">Your Position</h5>
            <p class="mb-0 opacity-75">You're ranked #<?= $currentUserRank['rank_position'] ?> out of <?= count($entries) ?> members</p>
          </div>
          <div class="col-auto text-end">
            <div class="fs-3 fw-bold"><?= number_format((float)$currentUserRank['score'], $leaderboard['metric_type'] === 'total_catches' || $leaderboard['metric_type'] === 'species_count' ? 0 : 2) ?></div>
            <small class="opacity-75"><?= $metricLabels[$leaderboard['metric_type']] ?? 'Score' ?></small>
          </div>
        </div>
      </div>
    <?php endif; ?>
    
    <?php if (count($entries) >= 3): ?>
      <div class="podium-section">
        <div class="row justify-content-center g-3">
          <?php 
          $podium = array_slice($entries, 0, 3);
          foreach ($podium as $i => $p): 
            $position = $i + 1;
          ?>
            <div class="col-md-4 col-lg-3 d-flex">
              <div class="podium-card podium-<?= $position ?> flex-fill">
                <div class="podium-rank">
                  <?php if ($position === 1): ?>
                    <i class="bi bi-trophy-fill"></i>
                  <?php elseif ($position === 2): ?>
                    <i class="bi bi-award-fill"></i>
                  <?php else: ?>
                    <i class="bi bi-award"></i>
                  <?php endif; ?>
                  #<?= $position ?>
                </div>
                <?php if ($p['profile_picture_url']): ?>
                  <img src="<?= e($p['profile_picture_url']) ?>" alt="" class="podium-avatar">
                <?php else: ?>
                  <div class="podium-avatar-placeholder"><i class="bi bi-person"></i></div>
                <?php endif; ?>
                <h6 class="mb-1"><?= e($p['member_name']) ?></h6>
                <?php if ($p['town']): ?>
                  <small class="text-muted d-block mb-2"><?= e($p['town']) ?></small>
                <?php endif; ?>
                <div class="podium-score">
                  <?= number_format((float)$p['score'], $leaderboard['metric_type'] === 'total_catches' || $leaderboard['metric_type'] === 'species_count' ? 0 : 2) ?>
                </div>
                <small class="text-muted"><?= $metricLabels[$leaderboard['metric_type']] ?? 'Score' ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    
    <div class="card">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Full Rankings</h5>
        <small class="text-muted"><?= count($entries) ?> members</small>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover leaderboard-table mb-0">
            <thead class="table-light">
              <tr>
                <th class="rank-cell">#</th>
                <th class="avatar-cell"></th>
                <th>Member</th>
                <th class="text-end score-cell"><?= $metricLabels[$leaderboard['metric_type']] ?? 'Score' ?></th>
                <?php if ($leaderboard['metric_type'] === 'competition_points'): ?>
                  <th class="text-center">Comps</th>
                <?php elseif (in_array($leaderboard['metric_type'], ['total_weight', 'biggest_fish', 'species_count'])): ?>
                  <th class="text-center">Catches</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($entries as $entry): 
                $isCurrentUser = $entry['user_id'] == $userId;
                $rankClass = match($entry['rank_position']) {
                  1 => 'rank-1',
                  2 => 'rank-2',
                  3 => 'rank-3',
                  default => 'rank-default'
                };
              ?>
                <tr class="leaderboard-row <?= $isCurrentUser ? 'current-user' : '' ?>">
                  <td class="rank-cell">
                    <span class="rank-badge <?= $rankClass ?>"><?= $entry['rank_position'] ?></span>
                  </td>
                  <td class="avatar-cell">
                    <?php if ($entry['profile_picture_url']): ?>
                      <img src="<?= e($entry['profile_picture_url']) ?>" alt="" class="member-avatar">
                    <?php else: ?>
                      <div class="member-avatar bg-secondary d-flex align-items-center justify-content-center text-white">
                        <i class="bi bi-person"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-semibold"><?= e($entry['member_name']) ?></div>
                    <?php if ($entry['town']): ?>
                      <small class="text-muted"><?= e($entry['town']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td class="text-end score-cell">
                    <?= number_format((float)$entry['score'], $leaderboard['metric_type'] === 'total_catches' || $leaderboard['metric_type'] === 'species_count' ? 0 : 2) ?>
                  </td>
                  <?php if ($leaderboard['metric_type'] === 'competition_points'): ?>
                    <td class="text-center text-muted"><?= $entry['competitions_count'] ?></td>
                  <?php elseif (in_array($leaderboard['metric_type'], ['total_weight', 'biggest_fish', 'species_count'])): ?>
                    <td class="text-center text-muted"><?= $entry['catches_count'] ?></td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer bg-white text-muted small text-center">
        Last updated: <?= date('j M Y, g:i a', strtotime($entries[0]['calculated_at'] ?? 'now')) ?>
      </div>
    </div>
  <?php endif; ?>
  
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
