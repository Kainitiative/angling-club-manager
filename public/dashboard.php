<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_login();

$userId = current_user_id();

// Load current user (ASSOC so keys work predictably)
$stmt = $pdo->prepare("SELECT id, name, email, profile_picture_url, dob, phone, town, city, country, gender FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If DB was reset (schema.sql drop/recreate), the session might point to a user id that no longer exists.
// In that case, force logout and send back to login.
if (!$user) {
  session_unset();
  session_destroy();
  header('Location: /auth/login.php');
  exit;
}

// Load clubs where the user is an admin
$stmt = $pdo->prepare("
  SELECT c.id, c.name, c.slug, c.contact_email, c.location_text, ca.admin_role
  FROM clubs c
  JOIN club_admins ca ON c.id = ca.club_id
  WHERE ca.user_id = ?
  ORDER BY c.created_at DESC
");
$stmt->execute([$userId]);
$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasOwnClub = false;
foreach ($clubs as $club) {
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

      <div class="card shadow-sm mt-4">
        <div class="card-body">
          <h6 class="mb-3">Quick actions</h6>
          <a class="btn btn-primary w-100" href="/public/create_club.php">Create Club</a>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Your Clubs</h5>
            <?php if ($hasOwnClub): ?>
              <span class="badge text-bg-success">Owner</span>
            <?php else: ?>
              <span class="badge text-bg-secondary">Member/Admin</span>
            <?php endif; ?>
          </div>

          <?php if (!$clubs): ?>
            <div class="alert alert-info mb-0">
              You’re not linked to any clubs yet. Click <strong>Create Club</strong> to get started.
            </div>
          <?php else: ?>
            <div class="list-group">
              <?php foreach ($clubs as $club): ?>
                <?php
                  // Defensive slug handling: avoid urlencode(NULL)
                  $slug = $club['slug'] ?? '';
                  $clubUrl = ($slug !== '') ? ('/club.php?slug=' . urlencode((string)$slug)) : '#';
                ?>
                <a class="list-group-item list-group-item-action" href="<?= e($clubUrl) ?>">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= e($club['name'] ?? '') ?></h6>
                    <small class="text-muted"><?= e($club['admin_role'] ?? '') ?></small>
                  </div>
                  <div class="small text-muted">
                    <?= e($club['contact_email'] ?? 'No contact email') ?>
                    <?php if (!empty($club['location_text'])): ?>
                      • <?= e($club['location_text']) ?>
                    <?php endif; ?>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

  </div>
</div>

</body>
</html>
