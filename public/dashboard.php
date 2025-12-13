<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_login();

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT id, name, email, city FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
  SELECT c.id, c.name, c.contact_email, c.location_text, ca.admin_role
  FROM clubs c
  JOIN club_admins ca ON c.id = ca.club_id
  WHERE ca.user_id = ?
  ORDER BY c.created_at DESC
");
$stmt->execute([$userId]);
$clubs = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard - Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
  <h1 class="mb-2">Dashboard</h1>
  <p class="text-muted mb-4">Logged in as <?= e($user['name'] ?? '') ?></p>

  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Your Profile</h5>
          <p class="mb-1"><strong>Name:</strong> <?= e($user['name'] ?? '') ?></p>
          <p class="mb-1"><strong>Email:</strong> <?= e($user['email'] ?? '') ?></p>
          <p class="mb-0"><strong>City/County:</strong> <?= e($user['city'] ?? 'Not set') ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Your Clubs</h2>
    <a href="/public/create_club.php" class="btn btn-primary">+ Create Club</a>
  </div>

  <?php if (empty($clubs)): ?>
    <div class="alert alert-info">
      You haven't created any clubs yet. <a href="/public/create_club.php" class="alert-link">Create one now</a>!
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($clubs as $club): ?>
        <div class="col-md-6 mb-3">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"><?= e($club['name']) ?></h5>
              <p class="mb-1"><span class="badge bg-secondary"><?= e($club['admin_role']) ?></span></p>
              <?php if ($club['location_text']): ?>
                <p class="mb-1 text-muted"><small><?= e($club['location_text']) ?></small></p>
              <?php endif; ?>
              <?php if ($club['contact_email']): ?>
                <p class="mb-0 text-muted"><small><?= e($club['contact_email']) ?></small></p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
