<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$userId = current_user_id();
$errors = [];
$success = false;

function post($key, $default = '') {
  return $_POST[$key] ?? $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim(post('name'));
  $contact_email = trim(post('contact_email'));
  $location_text = trim(post('location_text'));
  $about_text = trim(post('about_text'));

  if ($name === '') {
    $errors[] = "Club name is required.";
  }

  if (!$errors) {
    try {
      $pdo->beginTransaction();

      $slug = generate_slug($name);
      $baseSlug = $slug;
      $counter = 1;
      
      $checkStmt = $pdo->prepare("SELECT id FROM clubs WHERE slug = ?");
      $checkStmt->execute([$slug]);
      while ($checkStmt->fetch()) {
        $slug = $baseSlug . '-' . $counter++;
        $checkStmt->execute([$slug]);
      }

      $today = date('Y-m-d');
      $trialEnd = date('Y-m-d', strtotime('+30 days'));

      $stmt = $pdo->prepare("
        INSERT INTO clubs (name, slug, contact_email, location_text, about_text, trial_start_date, trial_end_date, access_until, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
      ");
      $stmt->execute([
        $name,
        $slug,
        $contact_email !== '' ? $contact_email : null,
        $location_text !== '' ? $location_text : null,
        $about_text !== '' ? $about_text : null,
        $today,
        $trialEnd,
        $trialEnd
      ]);

      $clubId = $pdo->lastInsertId();

      $stmt = $pdo->prepare("
        INSERT INTO club_admins (club_id, user_id, admin_role, created_at)
        VALUES (?, ?, 'owner', NOW())
      ");
      $stmt->execute([$clubId, $userId]);

      $pdo->commit();
      $success = true;

    } catch (Throwable $ex) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = "Failed to create club: " . $ex->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create Club - Angling Club Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/public/dashboard.php">Angling Club Manager</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
      <a class="btn btn-outline-light btn-sm" href="/public/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4" style="max-width: 600px;">
  <h1 class="mb-4">Create New Club</h1>

  <?php if ($success): ?>
    <div class="alert alert-success">
      <strong>Club created successfully!</strong>
      <a href="/public/dashboard.php" class="alert-link">Go to Dashboard</a>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="post" class="card card-body">
    <div class="mb-3">
      <label class="form-label">Club Name *</label>
      <input type="text" name="name" class="form-control" value="<?= e(post('name')) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Contact Email</label>
      <input type="email" name="contact_email" class="form-control" value="<?= e(post('contact_email')) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Location</label>
      <input type="text" name="location_text" class="form-control" value="<?= e(post('location_text')) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">About</label>
      <textarea name="about_text" class="form-control" rows="3"><?= e(post('about_text')) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Create Club</button>
  </form>
  <?php endif; ?>

  <p class="mt-3">
    <a href="/public/dashboard.php">&larr; Back to Dashboard</a>
  </p>
</div>
</body>
</html>
