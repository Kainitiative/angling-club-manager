<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout/member_shell.php';

$seasonId = (int)($_GET['season_id'] ?? 0);

if (!$seasonId) {
  header('Location: /public/dashboard.php');
  exit;
}

$stmt = $pdo->prepare("SELECT cs.*, c.name as club_name, c.slug FROM competition_seasons cs JOIN clubs c ON cs.club_id = c.id WHERE cs.id = ?");
$stmt->execute([$seasonId]);
$season = $stmt->fetch();

if (!$season) {
  http_response_code(404);
  exit('Season not found');
}

$stmt = $pdo->prepare("
  SELECT ss.*, u.name as angler_name, u.profile_picture_url
  FROM season_standings ss
  JOIN users u ON ss.user_id = u.id
  WHERE ss.season_id = ?
  ORDER BY ss.total_points DESC, ss.total_weight_kg DESC, ss.wins DESC
");
$stmt->execute([$seasonId]);
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT c.*, 
         (SELECT COUNT(*) FROM competition_results WHERE competition_id = c.id) as result_count
  FROM competitions c
  WHERE c.season_id = ?
  ORDER BY c.competition_date DESC
");
$stmt->execute([$seasonId]);
$competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scoringLabels = [
  'total_points' => 'Total Points',
  'total_weight' => 'Total Weight',
  'best_n' => 'Best ' . $season['best_n_count'] . ' Results',
];

$pageTitle = 'Season Leaderboard';
$currentPage = 'leaderboard';
member_shell_start($pdo, ['title' => $pageTitle, 'page' => $currentPage, 'section' => e($season['club_name'])]);
?>
<style>
  .position-1 { background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%); }
  .position-2 { background: linear-gradient(135deg, #c0c0c0 0%, #e0e0e0 100%); }
  .position-3 { background: linear-gradient(135deg, #cd7f32 0%, #daa06d 100%); }
  .leaderboard-row { transition: transform 0.2s; }
  .leaderboard-row:hover { transform: translateX(5px); }
</style>

<div class="text-center mb-4">
  <h1 class="mb-2"><?= e($season['name']) ?></h1>
  <p class="text-muted mb-1"><?= e($season['club_name']) ?></p>
  <p class="small text-muted">
    <?= date('j M Y', strtotime($season['start_date'])) ?> - <?= date('j M Y', strtotime($season['end_date'])) ?>
    &bull; <?= $scoringLabels[$season['scoring_type']] ?? $season['scoring_type'] ?>
  </p>
</div>

<div class="mb-3">
  <a class="btn btn-outline-secondary btn-sm" href="/public/club.php?slug=<?= e($season['slug']) ?>">Back to Club</a>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Season Leaderboard</h5>
      </div>
      <div class="card-body p-0">
        <?php if (empty($standings)): ?>
          <p class="text-muted text-center py-4 mb-0">No standings yet. Results will appear after competitions are scored.</p>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th>Angler</th>
                <th class="text-center">Points</th>
                <th class="text-center">Weight (kg)</th>
                <th class="text-center">Comps</th>
                <th class="text-center">Wins</th>
                <th class="text-center">Podiums</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($standings as $i => $s): 
                $position = $i + 1;
                $positionClass = $position <= 3 ? "position-{$position}" : '';
              ?>
                <tr class="leaderboard-row <?= $positionClass ?>">
                  <td class="fw-bold"><?= $position ?></td>
                  <td>
                    <div class="d-flex align-items-center">
                      <?php if ($s['profile_picture_url']): ?>
                        <img src="<?= e($s['profile_picture_url']) ?>" alt="" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                      <?php endif; ?>
                      <?= e($s['angler_name']) ?>
                    </div>
                  </td>
                  <td class="text-center fw-bold"><?= number_format((float)$s['total_points'], 1) ?></td>
                  <td class="text-center"><?= number_format((float)$s['total_weight_kg'], 3) ?></td>
                  <td class="text-center"><?= $s['competitions_entered'] ?></td>
                  <td class="text-center"><?= $s['wins'] ?></td>
                  <td class="text-center"><?= $s['podiums'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header bg-white">
        <h5 class="mb-0">Season Competitions</h5>
      </div>
      <div class="card-body p-0">
        <?php if (empty($competitions)): ?>
          <p class="text-muted text-center py-3 mb-0">No competitions in this season yet.</p>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($competitions as $comp): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <strong><?= e($comp['title']) ?></strong><br>
                    <small class="text-muted"><?= date('j M Y', strtotime($comp['competition_date'])) ?></small>
                  </div>
                  <?php if ($comp['result_count'] > 0): ?>
                    <a href="/public/competition_results.php?id=<?= $comp['id'] ?>" class="btn btn-outline-success btn-sm">Results</a>
                  <?php else: ?>
                    <span class="badge bg-secondary">Pending</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <?php if ($season['description']): ?>
      <div class="card mt-3">
        <div class="card-header bg-white">
          <h6 class="mb-0">About This Season</h6>
        </div>
        <div class="card-body">
          <p class="mb-0"><?= nl2br(e($season['description'])) ?></p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php member_shell_end(); ?>
