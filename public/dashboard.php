<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_login();

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT id, name, email, city FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
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
  <p class="text-muted mb-4">Logged in ✅</p>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Your profile</h5>
      <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($user['name'] ?? '') ?></p>
      <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '') ?></p>
      <p class="mb-0"><strong>City/County:</strong> <?= htmlspecialchars($user['city'] ?? 'Not set') ?></p>
    </div>
  </div>
</div>
</body>
</html>
