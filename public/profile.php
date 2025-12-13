<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_login();

$userId = current_user_id();
$errors = [];
$success = false;

$stmt = $pdo->prepare("SELECT id, name, email, city FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
  http_response_code(404);
  exit("User not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $city = trim((string)($_POST['city'] ?? ''));

  // Keep it simple for MVP: allow blank or up to 120 chars
  if (strlen($city) > 120) {
    $errors[] = "City/County must be 120 characters or less.";
  }

  if (!$errors) {
    $stmt = $pdo->prepare("UPDATE users SET city = ? WHERE id = ?");
    $stmt->execute([$city === '' ? null : $city, $userId]);
    $success = true;

    // Reload user
    $stmt = $pdo->prepare("SELECT id, name, email, city FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Your Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/public/dashboard.php">Angling Club Manager</a>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
      <a class="btn btn-outline-light btn-sm" href="/public/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4" style="max-width: 720px;">
  <h1 class="mb-3">Your Profile</h1>

  <?php if ($success): ?>
    <div class="alert alert-success">Profile updated ✅</div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
      <p class="mb-3"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">City/County</label>
          <input class="form-control" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" placeholder="e.g. Dublin / Tallaght / Wexford">
          <div class="form-text">This is used for showing clubs near you on your dashboard.</div>
        </div>
        <button class="btn btn-primary" type="submit">Save</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
