<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

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
  SELECT c.id, c.name, c.slug, c.contact_email, c.location_text, ca.admin_role,
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
  SELECT c.id, c.name, c.slug, c.contact_email, c.town, cm.membership_status, cm.joined_at
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

$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode((string)($user['name'] ?? 'User')) . '&size=80&background=0D6EFD&color=fff';
$avatarUrl = !empty($user['profile_picture_url']) ? $user['profile_picture_url'] : $defaultAvatar;

$location = array_filter([
  $user['town'] ?? null,
  $user['city'] ?? null,
  $user['country'] ?? null
]);
$locationStr = $location ? implode(', ', $location) : 'Not set';

$totalPending = 0;
foreach ($adminClubs as $club) {
  $totalPending += (int)($club['pending_count'] ?? 0);
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard - Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/profile.php">Profile</a>
      <a class="btn btn-light btn-sm ms-2" href="/public/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">

  <?php if ($totalPending > 0): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4">
      <strong class="me-2">You have <?= $totalPending ?> pending membership request<?= $totalPending > 1 ? 's' : '' ?>!</strong>
      <span class="text-muted">Review them in the club management section below.</span>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <img src="<?= e($avatarUrl) ?>" alt="Avatar" class="rounded-circle mb-3" width="80" height="80">
          <h5 class="mb-0"><?= e($user['name'] ?? '') ?></h5>
          <div class="text-muted small"><?= e($user['email'] ?? '') ?></div>
          <div class="mt-2 small"><strong>Location:</strong> <?= e($locationStr) ?></div>
        </div>
      </div>

      <?php if (!$hasOwnClub): ?>
        <div class="card shadow-sm mt-4">
          <div class="card-body">
            <h6 class="mb-3">Quick actions</h6>
            <a class="btn btn-primary w-100" href="/public/create_club.php">Create Club</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-md-8">
      
      <?php if (!empty($adminClubs)): ?>
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Clubs You Manage</h5>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              <?php foreach ($adminClubs as $club): ?>
                <?php $slug = $club['slug'] ?? ''; ?>
                <div class="list-group-item px-0">
                  <div class="d-flex w-100 justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">
                        <a href="/public/club.php?slug=<?= e($slug) ?>" class="text-decoration-none">
                          <?= e($club['name'] ?? '') ?>
                        </a>
                        <?php if ($club['admin_role'] === 'owner'): ?>
                          <span class="badge bg-warning text-dark">Owner</span>
                        <?php else: ?>
                          <span class="badge bg-secondary">Admin</span>
                        <?php endif; ?>
                      </h6>
                      <div class="small text-muted">
                        <?= (int)$club['member_count'] ?> member<?= $club['member_count'] != 1 ? 's' : '' ?>
                        <?php if (!empty($club['contact_email'])): ?>
                          &bull; <?= e($club['contact_email']) ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="text-end">
                      <?php if ((int)$club['pending_count'] > 0): ?>
                        <a href="/public/admin/members.php?club_id=<?= $club['id'] ?>" class="btn btn-warning btn-sm">
                          <?= $club['pending_count'] ?> Pending
                        </a>
                      <?php else: ?>
                        <a href="/public/admin/members.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-secondary btn-sm">
                          Manage
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($memberClubs)): ?>
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Your Memberships</h5>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              <?php foreach ($memberClubs as $club): ?>
                <?php $slug = $club['slug'] ?? ''; ?>
                <div class="list-group-item px-0">
                  <div class="d-flex w-100 justify-content-between align-items-center">
                    <div>
                      <h6 class="mb-1">
                        <a href="/public/club.php?slug=<?= e($slug) ?>" class="text-decoration-none">
                          <?= e($club['name'] ?? '') ?>
                        </a>
                        <?php if ($club['membership_status'] === 'active'): ?>
                          <span class="badge bg-success">Member</span>
                        <?php elseif ($club['membership_status'] === 'pending'): ?>
                          <span class="badge bg-info">Pending</span>
                        <?php elseif ($club['membership_status'] === 'suspended'): ?>
                          <span class="badge bg-danger">Suspended</span>
                        <?php endif; ?>
                      </h6>
                      <div class="small text-muted">
                        <?php if ($club['town']): ?>
                          <?= e($club['town']) ?>
                        <?php endif; ?>
                        <?php if ($club['membership_status'] === 'active'): ?>
                          &bull; Joined <?= date('M j, Y', strtotime($club['joined_at'])) ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div>
                      <a href="/public/club.php?slug=<?= e($slug) ?>" class="btn btn-outline-primary btn-sm">View</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if (empty($adminClubs) && empty($memberClubs)): ?>
        <div class="card shadow-sm">
          <div class="card-body text-center py-5">
            <h5 class="text-muted mb-3">Welcome to Angling Club Manager!</h5>
            <p class="text-muted">You're not linked to any clubs yet.</p>
            <a href="/public/create_club.php" class="btn btn-primary">Create Your Club</a>
            <p class="text-muted mt-3 small">Or browse public clubs and request to join one.</p>
            <a href="/" class="btn btn-outline-secondary btn-sm">Browse Clubs</a>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

</body>
</html>
