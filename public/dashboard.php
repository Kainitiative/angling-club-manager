<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/superadmin.php';

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

$userCountry = $user['country'] ?? '';
$userTown = $user['town'] ?? '';
$upcomingCompetitions = [];

if ($userCountry !== '') {
  if ($userTown !== '') {
    $stmt = $pdo->prepare("
      SELECT comp.*, c.name as club_name, c.slug as club_slug, 1 as is_local
      FROM competitions comp
      JOIN clubs c ON comp.club_id = c.id
      WHERE comp.visibility = 'open'
        AND comp.competition_date >= CURDATE()
        AND comp.country = ?
        AND comp.town = ?
      ORDER BY comp.competition_date ASC
      LIMIT 5
    ");
    $stmt->execute([$userCountry, $userTown]);
    $upcomingCompetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  
  $existingIds = array_column($upcomingCompetitions, 'id');
  $stmt = $pdo->prepare("
    SELECT comp.*, c.name as club_name, c.slug as club_slug, 0 as is_local
    FROM competitions comp
    JOIN clubs c ON comp.club_id = c.id
    WHERE comp.visibility = 'open'
      AND comp.competition_date >= CURDATE()
      AND comp.country = ?
    ORDER BY comp.competition_date ASC
    LIMIT 10
  ");
  $stmt->execute([$userCountry]);
  $countryComps = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  foreach ($countryComps as $comp) {
    if (!in_array($comp['id'], $existingIds) && count($upcomingCompetitions) < 6) {
      $upcomingCompetitions[] = $comp;
    }
  }
}

$memberClubIds = array_column($memberClubs, 'id');
$adminClubIds = array_column($adminClubs, 'id');
$allUserClubIds = array_unique(array_merge($memberClubIds, $adminClubIds));

$privateCompetitions = [];
if (!empty($allUserClubIds)) {
  $placeholders = implode(',', array_fill(0, count($allUserClubIds), '?'));
  $stmt = $pdo->prepare("
    SELECT comp.*, c.name as club_name, c.slug as club_slug
    FROM competitions comp
    JOIN clubs c ON comp.club_id = c.id
    WHERE comp.visibility = 'private'
      AND comp.competition_date >= CURDATE()
      AND comp.club_id IN ($placeholders)
    ORDER BY comp.competition_date ASC
    LIMIT 6
  ");
  $stmt->execute($allUserClubIds);
  $privateCompetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$fishingStats = [
  'total_catches' => 0,
  'personal_bests' => 0,
  'club_records' => 0,
  'biggest_catch_kg' => 0,
  'biggest_species' => null,
  'recent_catches' => []
];

$tableCheck = $pdo->query("SHOW TABLES LIKE 'catch_logs'")->fetch();
if ($tableCheck) {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM catch_logs WHERE user_id = ?");
  $stmt->execute([$userId]);
  $fishingStats['total_catches'] = (int)$stmt->fetchColumn();
  
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM personal_bests WHERE user_id = ?");
  $stmt->execute([$userId]);
  $fishingStats['personal_bests'] = (int)$stmt->fetchColumn();
  
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM club_records WHERE user_id = ?");
  $stmt->execute([$userId]);
  $fishingStats['club_records'] = (int)$stmt->fetchColumn();
  
  $stmt = $pdo->prepare("SELECT species, weight_kg FROM catch_logs WHERE user_id = ? AND weight_kg IS NOT NULL ORDER BY weight_kg DESC LIMIT 1");
  $stmt->execute([$userId]);
  $biggest = $stmt->fetch();
  if ($biggest) {
    $fishingStats['biggest_catch_kg'] = (float)$biggest['weight_kg'];
    $fishingStats['biggest_species'] = $biggest['species'];
  }
  
  $stmt = $pdo->prepare("
    SELECT cl.*, c.name as club_name, c.slug as club_slug
    FROM catch_logs cl
    JOIN clubs c ON cl.club_id = c.id
    WHERE cl.user_id = ?
    ORDER BY cl.catch_date DESC, cl.created_at DESC
    LIMIT 3
  ");
  $stmt->execute([$userId]);
  $fishingStats['recent_catches'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$unreadNotifications = get_unread_notification_count($pdo, $userId);
$unreadMessages = get_unread_message_count($pdo, $userId);

require_once __DIR__ . '/../app/meetings.php';
$userTasks = [];
try {
  $userTasks = get_user_tasks($pdo, $userId);
  $userTasks = array_filter($userTasks, fn($t) => !in_array($t['status'], ['completed', 'cancelled']));
  $userTasks = array_slice($userTasks, 0, 5);
} catch (Exception $e) {
  $userTasks = [];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-dark: #1e3a5f;
      --primary: #2d5a87;
      --accent: #3d7ab5;
    }
    body { background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%); min-height: 100vh; }
    .navbar-custom { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); }
    .profile-card {
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
      color: white;
      border-radius: 16px;
      overflow: hidden;
    }
    .profile-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      border: 4px solid rgba(255,255,255,0.3);
      object-fit: cover;
    }
    .quick-action-card {
      border: none;
      border-radius: 12px;
      transition: all 0.3s ease;
      cursor: pointer;
      text-decoration: none;
      display: block;
    }
    .quick-action-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.12);
    }
    .quick-action-icon {
      width: 56px;
      height: 56px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }
    .section-card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .section-header {
      background: transparent;
      border-bottom: 1px solid #eee;
      padding: 1.25rem 1.5rem;
    }
    .club-item {
      padding: 1rem 0;
      border-bottom: 1px solid #f0f0f0;
    }
    .club-item:last-child { border-bottom: none; }
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
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 1.2rem;
    }
    .comp-card {
      background: #fff;
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 0.75rem;
      border-left: 4px solid var(--accent);
      transition: all 0.2s;
    }
    .comp-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .comp-card.private { border-left-color: #6c757d; }
    .comp-card.local { border-left-color: #ffc107; }
    .badge-pending {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5253 100%);
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    .stat-number {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--primary-dark);
    }
    .welcome-banner {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 16px;
      color: white;
      padding: 2rem;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/">Angling Club Manager</a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <?php if (is_super_admin($pdo)): ?>
        <a class="btn btn-warning btn-sm" href="/public/superadmin/">Super Admin</a>
      <?php endif; ?>
      <a class="btn btn-outline-light btn-sm position-relative" href="/public/notifications.php">
        Notifications
        <?php if ($unreadNotifications > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?>
          </span>
        <?php endif; ?>
      </a>
      <a class="btn btn-outline-light btn-sm position-relative" href="/public/messages.php">
        Messages
        <?php if ($unreadMessages > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $unreadMessages > 99 ? '99+' : $unreadMessages ?>
          </span>
        <?php endif; ?>
      </a>
      <a class="btn btn-outline-light btn-sm" href="/public/profile.php">Edit Profile</a>
      <a class="btn btn-light btn-sm" href="/public/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">

  <?php if ($totalPending > 0): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4" style="border-radius: 12px;">
      <span class="badge badge-pending text-white me-3 px-3 py-2"><?= $totalPending ?></span>
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
        <h4 class="mb-1"><?= e($user['name'] ?? 'Welcome!') ?></h4>
        <p class="opacity-75 mb-2 small"><?= e($user['email'] ?? '') ?></p>
        <p class="opacity-75 mb-0 small">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
          </svg>
          <?= e($locationStr) ?>
        </p>
      </div>

      <h6 class="text-muted text-uppercase small fw-bold mb-3 px-1">Quick Actions</h6>
      
      <?php if (!$isInAnyClub): ?>
      <a href="/public/clubs.php" class="quick-action-card card mb-3">
        <div class="card-body d-flex align-items-center">
          <div class="quick-action-icon bg-primary bg-opacity-10 text-primary me-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
              <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
              <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
              <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
            </svg>
          </div>
          <div>
            <h6 class="mb-0">Browse Clubs</h6>
            <small class="text-muted">Find and join angling clubs</small>
          </div>
        </div>
      </a>
      <?php endif; ?>

      <a href="/public/competitions.php" class="quick-action-card card mb-3">
        <div class="card-body d-flex align-items-center">
          <div class="quick-action-icon bg-warning bg-opacity-10 text-warning me-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
              <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.377.135.537.255L13.3 15.1a.5.5 0 0 1-.3.9H3a.5.5 0 0 1-.3-.9l1.838-1.379c.16-.12.343-.207.537-.255L6.5 13.11v-2.173c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.132-5.89A33.076 33.076 0 0 1 2.5.5z"/>
            </svg>
          </div>
          <div>
            <h6 class="mb-0">Browse Competitions</h6>
            <small class="text-muted">Find open fishing events</small>
          </div>
        </div>
      </a>

      <?php if (!$isInAnyClub): ?>
        <a href="/public/create_club.php" class="quick-action-card card mb-3">
          <div class="card-body d-flex align-items-center">
            <div class="quick-action-icon bg-success bg-opacity-10 text-success me-3">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
              </svg>
            </div>
            <div>
              <h6 class="mb-0">Create a Club</h6>
              <small class="text-muted">Start your own angling club</small>
            </div>
          </div>
        </a>
      <?php endif; ?>
      
      <?php if ($userCountry === ''): ?>
        <div class="card border-0 bg-info bg-opacity-10 mb-3">
          <div class="card-body">
            <p class="small text-info mb-2"><strong>Tip:</strong> Set your location to see local competitions!</p>
            <a href="/public/profile.php" class="btn btn-info btn-sm">Update Profile</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-8">
      
      <?php if (empty($adminClubs) && empty($memberClubs)): ?>
        <div class="welcome-banner mb-4">
          <h3 class="mb-2">Welcome to Angling Club Manager!</h3>
          <p class="opacity-90 mb-3">You're not linked to any clubs yet. Get started by browsing clubs to join or create your own.</p>
          <a href="/public/clubs.php" class="btn btn-light me-2">Browse Clubs</a>
          <a href="/public/create_club.php" class="btn btn-outline-light">Create Club</a>
        </div>
      <?php endif; ?>

      <?php if (!empty($adminClubs)): ?>
        <div class="section-card card mb-4">
          <div class="section-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Clubs You Manage</h5>
          </div>
          <div class="card-body px-4">
            <?php foreach ($adminClubs as $club): ?>
              <?php $slug = $club['slug'] ?? ''; ?>
              <div class="club-item d-flex align-items-center">
                <?php if (!empty($club['logo_url'])): ?>
                  <img src="<?= e($club['logo_url']) ?>" alt="" class="club-logo me-3">
                <?php else: ?>
                  <div class="club-logo-placeholder me-3"><?= strtoupper(substr($club['name'], 0, 1)) ?></div>
                <?php endif; ?>
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center mb-1">
                    <a href="/public/club.php?slug=<?= e($slug) ?>" class="fw-semibold text-decoration-none text-dark me-2">
                      <?= e($club['name'] ?? '') ?>
                    </a>
                    <?php if ($club['admin_role'] === 'owner'): ?>
                      <span class="badge bg-warning text-dark">Owner</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Admin</span>
                    <?php endif; ?>
                  </div>
                  <small class="text-muted"><?= (int)$club['member_count'] ?> member<?= $club['member_count'] != 1 ? 's' : '' ?></small>
                </div>
                <div class="d-flex gap-2">
                  <a href="/public/admin/accounts.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-success btn-sm">Accounts</a>
                  <a href="/public/admin/meetings.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-info btn-sm">Meetings</a>
                  <a href="/public/admin/competitions.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-primary btn-sm">Competitions</a>
                  <?php if ((int)$club['pending_count'] > 0): ?>
                    <a href="/public/admin/members.php?club_id=<?= $club['id'] ?>" class="btn btn-warning btn-sm">
                      <?= $club['pending_count'] ?> Pending
                    </a>
                  <?php else: ?>
                    <a href="/public/admin/members.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-secondary btn-sm">Members</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($memberClubs)): ?>
        <div class="section-card card mb-4">
          <div class="section-header">
            <h5 class="mb-0 fw-bold">Your Memberships</h5>
          </div>
          <div class="card-body px-4">
            <?php foreach ($memberClubs as $club): ?>
              <?php $slug = $club['slug'] ?? ''; ?>
              <div class="club-item d-flex align-items-center">
                <?php if (!empty($club['logo_url'])): ?>
                  <img src="<?= e($club['logo_url']) ?>" alt="" class="club-logo me-3">
                <?php else: ?>
                  <div class="club-logo-placeholder me-3"><?= strtoupper(substr($club['name'], 0, 1)) ?></div>
                <?php endif; ?>
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center mb-1">
                    <a href="/public/club.php?slug=<?= e($slug) ?>" class="fw-semibold text-decoration-none text-dark me-2">
                      <?= e($club['name'] ?? '') ?>
                    </a>
                    <?php if ($club['membership_status'] === 'active'): ?>
                      <span class="badge bg-success">Member</span>
                    <?php elseif ($club['membership_status'] === 'pending'): ?>
                      <span class="badge bg-info">Pending</span>
                    <?php elseif ($club['membership_status'] === 'suspended'): ?>
                      <span class="badge bg-danger">Suspended</span>
                    <?php endif; ?>
                  </div>
                  <small class="text-muted">
                    <?php if ($club['town']): ?><?= e($club['town']) ?> &bull; <?php endif; ?>
                    <?php if ($club['membership_status'] === 'active'): ?>
                      Joined <?= date('M j, Y', strtotime($club['joined_at'])) ?>
                    <?php endif; ?>
                  </small>
                </div>
                <a href="/public/club.php?slug=<?= e($slug) ?>" class="btn btn-outline-primary btn-sm">View Club</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($fishingStats['total_catches'] > 0 || !empty($allUserClubIds)): ?>
        <div class="section-card card mb-4">
          <div class="section-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">My Fishing Stats</h5>
          </div>
          <div class="card-body">
            <div class="row text-center mb-4">
              <div class="col-3">
                <div class="stat-number"><?= $fishingStats['total_catches'] ?></div>
                <small class="text-muted">Total Catches</small>
              </div>
              <div class="col-3">
                <div class="stat-number"><?= $fishingStats['personal_bests'] ?></div>
                <small class="text-muted">Personal Bests</small>
              </div>
              <div class="col-3">
                <div class="stat-number"><?= $fishingStats['club_records'] ?></div>
                <small class="text-muted">Club Records</small>
              </div>
              <div class="col-3">
                <div class="stat-number"><?= $fishingStats['biggest_catch_kg'] > 0 ? number_format($fishingStats['biggest_catch_kg'], 2) : '-' ?></div>
                <small class="text-muted">Biggest (kg)</small>
              </div>
            </div>
            
            <?php if ($fishingStats['biggest_species']): ?>
              <p class="text-center text-muted mb-3">
                Biggest catch: <strong><?= e($fishingStats['biggest_species']) ?></strong> at <?= number_format($fishingStats['biggest_catch_kg'], 3) ?> kg
              </p>
            <?php endif; ?>
            
            <?php if (!empty($fishingStats['recent_catches'])): ?>
              <h6 class="mt-3 mb-2">Recent Catches</h6>
              <?php foreach ($fishingStats['recent_catches'] as $catch): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                  <div>
                    <strong><?= e($catch['species']) ?></strong>
                    <?php if ($catch['is_personal_best']): ?>
                      <span class="badge bg-warning text-dark">PB</span>
                    <?php endif; ?>
                    <?php if ($catch['is_club_record']): ?>
                      <span class="badge bg-danger">Record</span>
                    <?php endif; ?>
                    <br>
                    <small class="text-muted">
                      <?= date('j M Y', strtotime($catch['catch_date'])) ?>
                      &bull; <a href="/public/catches.php?slug=<?= e($catch['club_slug']) ?>"><?= e($catch['club_name']) ?></a>
                    </small>
                  </div>
                  <?php if ($catch['weight_kg']): ?>
                    <span class="fw-bold text-primary"><?= number_format((float)$catch['weight_kg'], 3) ?> kg</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php elseif (!empty($allUserClubIds)): ?>
              <p class="text-muted text-center mb-0">No catches logged yet. Visit your club's catch log to start tracking!</p>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($userTasks)): ?>
        <div class="section-card card mb-4">
          <div class="section-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">My Tasks</h5>
            <a href="/public/tasks.php" class="btn btn-outline-primary btn-sm">View All</a>
          </div>
          <div class="card-body p-0">
            <?php foreach ($userTasks as $task): ?>
              <div class="d-flex align-items-center p-3 border-bottom">
                <div class="flex-grow-1">
                  <strong><?= e($task['title']) ?></strong>
                  <?php 
                    $statusBadge = match($task['status']) {
                      'in_progress' => 'primary',
                      default => 'warning'
                    };
                  ?>
                  <span class="badge bg-<?= $statusBadge ?> ms-1"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span>
                  <?php if ($task['due_date']): ?>
                    <?php $isOverdue = strtotime($task['due_date']) < time(); ?>
                    <span class="badge <?= $isOverdue ? 'bg-danger' : 'bg-light text-dark border' ?>">
                      Due: <?= date('j M', strtotime($task['due_date'])) ?>
                    </span>
                  <?php endif; ?>
                  <br>
                  <small class="text-muted">
                    <a href="/public/club.php?slug=<?= e($task['club_slug']) ?>"><?= e($task['club_name']) ?></a>
                  </small>
                </div>
                <a href="/public/tasks.php" class="btn btn-sm btn-outline-secondary">Update</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($upcomingCompetitions) || !empty($privateCompetitions)): ?>
        <div class="section-card card mb-4">
          <div class="section-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Upcoming Competitions</h5>
            <a href="/public/competitions.php" class="btn btn-primary btn-sm">Browse All</a>
          </div>
          <div class="card-body">
            <?php foreach ($upcomingCompetitions as $comp): ?>
              <div class="comp-card <?= !empty($comp['is_local']) ? 'local' : '' ?>">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1">
                      <?= e($comp['title']) ?>
                      <?php if (!empty($comp['is_local'])): ?>
                        <span class="badge bg-warning text-dark">Local</span>
                      <?php endif; ?>
                      <span class="badge bg-success">Open</span>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
