<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_login();

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT id, name, email, profile_picture_url, dob, phone, town, city, country, gender FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
  SELECT c.id, c.name, c.slug, c.contact_email, c.location_text, ca.admin_role
  FROM clubs c
  JOIN club_admins ca ON c.id = ca.club_id
  WHERE ca.user_id = ?
  ORDER BY c.created_at DESC
");
$stmt->execute([$userId]);
$clubs = $stmt->fetchAll();

$hasOwnClub = false;
foreach ($clubs as $club) {
  if ($club['admin_role'] === 'owner') {
    $hasOwnClub = true;
    break;
  }
}

$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&size=80&background=0D6EFD&color=fff';
$avatarUrl = $user['profile_picture_url'] ?: $defaultAvatar;

$location = array_filter([
  $user['town'] ?? null,
  $user['city'] ?? null,
  $user['country'] ?? null
]);
$locationStr = $location ? implode(', ', $location) : 'Not set';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard - Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .profile-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #dee2e6;
    }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/public/dashboard.php">Angling Club Manager</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/profile.php">Profile</a>
      <a class="btn btn-outline-light btn-sm" href="/public/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h1 class="mb-4">Dashboard</h1>

  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex align-items-start">
        <img src="<?= e($avatarUrl) ?>" alt="Profile" class="profile-avatar me-4">
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h4 class="mb-1"><?= e($user['name']) ?></h4>
              <p class="text-muted mb-2"><?= e($user['email']) ?></p>
            </div>
            <a href="/public/profile.php" class="btn btn-outline-primary btn-sm">Edit Profile</a>
          </div>
          
          <div class="row mt-3">
            <div class="col-md-4 mb-2">
              <small class="text-muted d-block">Location</small>
              <span><?= e($locationStr) ?></span>
            </div>
            <div class="col-md-4 mb-2">
              <small class="text-muted d-block">Phone</small>
              <span><?= e($user['phone'] ?? 'Not set') ?></span>
            </div>
            <div class="col-md-4 mb-2">
              <small class="text-muted d-block">Date of Birth</small>
              <span><?= $user['dob'] ? date('d M Y', strtotime($user['dob'])) : 'Not set' ?></span>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-2">
              <small class="text-muted d-block">Gender</small>
              <span><?= e(ucfirst(str_replace('_', ' ', $user['gender'] ?? '')) ?: 'Not set') ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Your Club</h2>
    <?php if (!$hasOwnClub): ?>
      <a href="/public/create_club.php" class="btn btn-primary">+ Create Club</a>
    <?php endif; ?>
  </div>

  <?php if (empty($clubs)): ?>
    <div class="alert alert-info">
      You haven't created a club yet. <a href="/public/create_club.php" class="alert-link">Create one now</a>!
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($clubs as $club): ?>
        <div class="col-md-6 mb-3">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0"><?= e($club['name']) ?></h5>
                <span class="badge bg-<?= $club['admin_role'] === 'owner' ? 'primary' : 'secondary' ?>"><?= e($club['admin_role']) ?></span>
              </div>
              <?php if ($club['location_text']): ?>
                <p class="mb-2 text-muted"><small>📍 <?= e($club['location_text']) ?></small></p>
              <?php endif; ?>
              <?php if ($club['contact_email']): ?>
                <p class="mb-2 text-muted"><small>✉️ <?= e($club['contact_email']) ?></small></p>
              <?php endif; ?>
              <div class="mt-3">
                <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-primary btn-sm">View Public Page</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
