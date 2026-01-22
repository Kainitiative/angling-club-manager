<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/superadmin.php';
require_once __DIR__ . '/../app/layout/member_shell.php';

require_login();

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT id, name, email, profile_picture_url, dob, phone, town, city, country, gender FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  session_unset();
  session_destroy();
  header('Location: /public/auth/login.php');
  exit;
}

$stmt = $pdo->prepare("
  SELECT c.id, c.name, c.slug, c.contact_email, c.location_text, c.logo_url, ca.admin_role,
         (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.membership_status = 'pending') as pending_count,
         (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.membership_status = 'active') as member_count
  FROM clubs c
  JOIN club_admins ca ON c.id = ca.club_id
  WHERE ca.user_id = ?
  ORDER BY c.created_at DESC
");
$stmt->execute([$userId]);
$adminClubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT c.id, c.name, c.slug, c.contact_email, c.town, c.logo_url, cm.membership_status, cm.joined_at
  FROM clubs c
  JOIN club_members cm ON c.id = cm.club_id
  WHERE cm.user_id = ?
  ORDER BY cm.joined_at DESC
");
$stmt->execute([$userId]);
$memberClubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasOwnClub = false;
foreach ($adminClubs as $club) {
  if (($club['admin_role'] ?? '') === 'owner') {
    $hasOwnClub = true;
    break;
  }
}

$isInAnyClub = $hasOwnClub || !empty($memberClubs) || !empty($adminClubs);

$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode((string)($user['name'] ?? 'User')) . '&size=120&background=1e3a5f&color=fff&bold=true';
$avatarUrl = !empty($user['profile_picture_url']) ? $user['profile_picture_url'] : $defaultAvatar;

$location = array_filter([
  $user['town'] ?? null,
  $user['city'] ?? null,
  $user['country'] ?? null
]);
$locationStr = $location ? implode(', ', $location) : 'Location not set';

$totalPending = 0;
foreach ($adminClubs as $club) {
  $totalPending += (int)($club['pending_count'] ?? 0);
}

$upcomingCompetitions = [];

$stmt = $pdo->prepare("
  SELECT comp.*, c.name as club_name, c.slug as club_slug
  FROM competitions comp
  JOIN clubs c ON comp.club_id = c.id
  WHERE comp.status = 'upcoming'
    AND comp.competition_date >= CURRENT_DATE
  ORDER BY comp.competition_date ASC
  LIMIT 6
");
$stmt->execute();
$upcomingCompetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$memberClubIds = array_column($memberClubs, 'id');
$adminClubIds = array_column($adminClubs, 'id');
$allUserClubIds = array_unique(array_merge($memberClubIds, $adminClubIds));

$clubCompetitions = [];
if (!empty($allUserClubIds)) {
  $placeholders = implode(',', array_fill(0, count($allUserClubIds), '?'));
  $stmt = $pdo->prepare("
    SELECT comp.*, c.name as club_name, c.slug as club_slug
    FROM competitions comp
    JOIN clubs c ON comp.club_id = c.id
    WHERE comp.competition_date >= CURRENT_DATE
      AND comp.club_id IN ($placeholders)
    ORDER BY comp.competition_date ASC
    LIMIT 5
  ");
  $stmt->execute($allUserClubIds);
  $clubCompetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$fishingStats = [
  'total_catches' => 0,
  'personal_bests' => 0,
  'biggest_catch_kg' => 0,
  'biggest_species' => '',
  'recent_catches' => []
];

try {
  $catchTable = (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') ? 'catches' : 'catch_logs';
  
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM $catchTable WHERE user_id = ?");
  $stmt->execute([$userId]);
  $fishingStats['total_catches'] = (int)$stmt->fetchColumn();
  
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM $catchTable WHERE user_id = ? AND is_personal_best = 1");
  $stmt->execute([$userId]);
  $fishingStats['personal_bests'] = (int)$stmt->fetchColumn();
  
  $stmt = $pdo->prepare("
    SELECT c.weight_kg, fs.name as species
    FROM $catchTable c
    LEFT JOIN fish_species fs ON c.species_id = fs.id
    WHERE c.user_id = ? AND c.weight_kg IS NOT NULL 
    ORDER BY c.weight_kg DESC LIMIT 1
  ");
  $stmt->execute([$userId]);
  $biggest = $stmt->fetch();
  if ($biggest) {
    $fishingStats['biggest_catch_kg'] = (float)$biggest['weight_kg'];
    $fishingStats['biggest_species'] = $biggest['species'] ?? 'Unknown';
  }
  
  $stmt = $pdo->prepare("
    SELECT ca.*, cl.name as club_name, cl.slug as club_slug, fs.name as species
    FROM $catchTable ca
    JOIN clubs cl ON ca.club_id = cl.id
    LEFT JOIN fish_species fs ON ca.species_id = fs.id
    WHERE ca.user_id = ?
    ORDER BY ca.catch_date DESC, ca.created_at DESC
    LIMIT 3
  ");
  $stmt->execute([$userId]);
  $fishingStats['recent_catches'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // Tables may not exist yet
}

require_once __DIR__ . '/../app/meetings.php';
$userTasks = [];
try {
  $userTasks = get_user_tasks($pdo, $userId);
  $userTasks = array_filter($userTasks, fn($t) => !in_array($t['status'], ['completed', 'cancelled']));
  $userTasks = array_slice($userTasks, 0, 5);
} catch (Exception $e) {
  $userTasks = [];
}

$pageTitle = 'Dashboard';
$currentPage = 'home';
$customStyles = '
  .profile-card {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    color: white;
    border-radius: 16px;
    overflow: hidden;
  }
  .profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.3);
    object-fit: cover;
  }
  .stat-card {
    border-radius: 12px;
  }
  .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e3a5f;
  }
  .club-card {
    border-radius: 12px;
    border: 1px solid #e9ecef;
    transition: all 0.2s;
  }
  .club-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }
  .club-logo {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    object-fit: cover;
    background: #e9ecef;
  }
  .club-logo-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
  }
  .comp-card {
    background: #fff;
    border-radius: 10px;
    padding: 1rem;
    border-left: 4px solid #3d7ab5;
    margin-bottom: 0.75rem;
  }
  .comp-card.private { border-left-color: #6c757d; }
  .comp-card.local { border-left-color: #ffc107; }
  .quick-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: white;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    border: 1px solid #e9ecef;
    transition: all 0.2s;
  }
  .quick-link:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
  }
  .quick-link-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
';

member_shell_start($pdo, ['title' => $pageTitle, 'page' => $currentPage, 'section' => 'Dashboard']);
?>

<?php if ($totalPending > 0): ?>
  <div class="alert alert-warning d-flex align-items-center mb-4">
    <span class="badge bg-danger me-3"><?= $totalPending ?></span>
    <div>
      <strong>Pending Membership Request<?= $totalPending > 1 ? 's' : '' ?></strong>
      <span class="text-muted d-none d-md-inline"> - Review in your club management below</span>
    </div>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="profile-card p-4 text-center mb-4">
      <img src="<?= e($avatarUrl) ?>" alt="Avatar" class="profile-avatar mb-3">
      <h5 class="mb-1"><?= e($user['name'] ?? 'Welcome!') ?></h5>
      <p class="opacity-75 small mb-2"><?= e($user['email'] ?? '') ?></p>
      <p class="opacity-75 small mb-0">
        <i class="bi bi-geo-alt"></i> <?= e($locationStr) ?>
      </p>
    </div>

    <?php if (!$hasOwnClub): ?>
    <a href="/public/create_club.php" class="quick-link mb-3">
      <div class="quick-link-icon bg-success bg-opacity-10 text-success">
        <i class="bi bi-plus-circle"></i>
      </div>
      <div>
        <div class="fw-medium">Create a Club</div>
        <small class="text-muted">Start your own angling club</small>
      </div>
    </a>
    <?php endif; ?>

    <?php if ($fishingStats['total_catches'] > 0): ?>
    <div class="card mt-4">
      <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-fish me-2"></i>My Fishing Stats</h6>
      </div>
      <div class="card-body">
        <div class="row text-center g-3">
          <div class="col-6">
            <div class="stat-number"><?= $fishingStats['total_catches'] ?></div>
            <small class="text-muted">Total Catches</small>
          </div>
          <div class="col-6">
            <div class="stat-number"><?= $fishingStats['personal_bests'] ?></div>
            <small class="text-muted">Personal Bests</small>
          </div>
        </div>
        <?php if ($fishingStats['biggest_catch_kg'] > 0): ?>
        <div class="mt-3 pt-3 border-top text-center">
          <small class="text-muted d-block">Biggest Catch</small>
          <strong><?= number_format($fishingStats['biggest_catch_kg'], 2) ?>kg</strong>
          <span class="text-muted"><?= e($fishingStats['biggest_species']) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($userTasks)): ?>
    <div class="card mt-4">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-check2-square me-2"></i>My Tasks</h6>
        <a href="/public/tasks.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="list-group list-group-flush">
        <?php foreach ($userTasks as $task): ?>
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-medium small"><?= e($task['title']) ?></div>
                <small class="text-muted"><?= e($task['club_name']) ?></small>
              </div>
              <?php if ($task['status'] === 'overdue'): ?>
                <span class="badge bg-danger">Overdue</span>
              <?php elseif ($task['priority'] === 'high'): ?>
                <span class="badge bg-warning">High</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-8">
    <?php if (!empty($adminClubs)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Clubs You Manage</h5>
        </div>
        <div class="card-body">
          <?php foreach ($adminClubs as $club): ?>
            <div class="club-card p-3 mb-3">
              <div class="d-flex align-items-start justify-content-between">
                <div class="d-flex align-items-center gap-3">
                  <?php if ($club['logo_url']): ?>
                    <img src="<?= e($club['logo_url']) ?>" alt="" class="club-logo">
                  <?php else: ?>
                    <div class="club-logo-placeholder">
                      <?= strtoupper(substr($club['name'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                  <div>
                    <h6 class="mb-1">
                      <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="text-decoration-none">
                        <?= e($club['name']) ?>
                      </a>
                      <?php if ($club['admin_role'] === 'owner'): ?>
                        <span class="badge bg-primary">Owner</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Admin</span>
                      <?php endif; ?>
                    </h6>
                    <small class="text-muted"><?= (int)$club['member_count'] ?> member<?= $club['member_count'] != 1 ? 's' : '' ?></small>
                  </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <?php if ((int)$club['pending_count'] > 0): ?>
                    <a href="/public/admin/members.php?club_id=<?= $club['id'] ?>" class="btn btn-warning btn-sm">
                      <?= $club['pending_count'] ?> Pending
                    </a>
                  <?php endif; ?>
                  <a href="/public/admin/members.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-primary btn-sm">Members</a>
                  <a href="/public/admin/finances.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-success btn-sm">Finances</a>
                  <a href="/public/admin/meetings.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-info btn-sm">Meetings</a>
                  <a href="/public/admin/competitions.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-secondary btn-sm">Competitions</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($memberClubs)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0"><i class="bi bi-people me-2"></i>Clubs You've Joined</h5>
        </div>
        <div class="card-body">
          <?php foreach ($memberClubs as $club): ?>
            <div class="club-card p-3 mb-3">
              <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                  <?php if ($club['logo_url']): ?>
                    <img src="<?= e($club['logo_url']) ?>" alt="" class="club-logo">
                  <?php else: ?>
                    <div class="club-logo-placeholder">
                      <?= strtoupper(substr($club['name'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                  <div>
                    <h6 class="mb-1">
                      <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="text-decoration-none">
                        <?= e($club['name']) ?>
                      </a>
                      <?php if ($club['membership_status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                      <?php elseif ($club['membership_status'] === 'active'): ?>
                        <span class="badge bg-success">Member</span>
                      <?php elseif ($club['membership_status'] === 'suspended'): ?>
                        <span class="badge bg-danger">Suspended</span>
                      <?php endif; ?>
                    </h6>
                    <small class="text-muted"><?= e($club['town'] ?? $club['contact_email'] ?? '') ?></small>
                  </div>
                </div>
                <?php if ($club['membership_status'] === 'active'): ?>
                  <a href="/public/catches.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-primary btn-sm">Log Catch</a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($adminClubs) && empty($memberClubs)): ?>
      <div class="card mb-4">
        <div class="card-body text-center py-5">
          <div class="mb-4">
            <i class="bi bi-water" style="font-size: 4rem; color: #2d5a87;"></i>
          </div>
          <h4>Welcome to Angling Ireland!</h4>
          <p class="text-muted mb-4">You're not part of any angling club yet. Browse existing clubs to join or create your own!</p>
          <div class="d-flex justify-content-center gap-3">
            <a href="/public/clubs.php" class="btn btn-primary">Browse Clubs</a>
            <a href="/public/create_club.php" class="btn btn-outline-primary">Create a Club</a>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($upcomingCompetitions) || !empty($privateCompetitions)): ?>
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Upcoming Competitions</h5>
        </div>
        <div class="card-body">
          <?php foreach ($upcomingCompetitions as $comp): ?>
            <div class="comp-card <?= $comp['is_local'] ? 'local' : '' ?>">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="mb-1">
                    <?= e($comp['title']) ?>
                    <?php if ($comp['is_local']): ?>
                      <span class="badge bg-warning text-dark">Local</span>
                    <?php endif; ?>
                  </h6>
                  <div class="small text-muted mb-1">
                    <?= e($comp['venue_name']) ?>
                    <?php if ($comp['town']): ?> &bull; <?= e($comp['town']) ?><?php endif; ?>
                  </div>
                  <div class="small fw-medium">
                    <?= date('l, j M Y', strtotime($comp['competition_date'])) ?>
                    <?php if ($comp['start_time']): ?> at <?= date('g:i A', strtotime($comp['start_time'])) ?><?php endif; ?>
                  </div>
                  <div class="small text-muted">
                    by <a href="/public/club.php?slug=<?= e($comp['club_slug']) ?>"><?= e($comp['club_name']) ?></a>
                  </div>
                </div>
                <a href="/public/competition_results.php?id=<?= $comp['id'] ?>" class="btn btn-outline-primary btn-sm">View</a>
              </div>
            </div>
          <?php endforeach; ?>
          
          <?php foreach ($privateCompetitions as $comp): ?>
            <div class="comp-card private">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="mb-1">
                    <?= e($comp['title']) ?>
                    <span class="badge bg-secondary">Members Only</span>
                  </h6>
                  <div class="small text-muted mb-1">
                    <?= e($comp['venue_name']) ?>
                    <?php if ($comp['town']): ?> &bull; <?= e($comp['town']) ?><?php endif; ?>
                  </div>
                  <div class="small fw-medium">
                    <?= date('l, j M Y', strtotime($comp['competition_date'])) ?>
                    <?php if ($comp['start_time']): ?> at <?= date('g:i A', strtotime($comp['start_time'])) ?><?php endif; ?>
                  </div>
                  <div class="small text-muted">
                    by <a href="/public/club.php?slug=<?= e($comp['club_slug']) ?>"><?= e($comp['club_name']) ?></a>
                  </div>
                </div>
                <?php if ($comp['latitude'] && $comp['longitude']): ?>
                  <a href="https://www.google.com/maps?q=<?= $comp['latitude'] ?>,<?= $comp['longitude'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                    Map
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
member_shell_end();
