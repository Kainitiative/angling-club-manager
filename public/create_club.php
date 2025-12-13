<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$userId = current_user_id();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');

  if ($name === '') {
    $errors[] = "Club name is required.";
  }

  if (!$errors) {
    try {
      $pdo->beginTransaction();

      // Generate unique slug
      $slug = generate_slug($name);
      $baseSlug = $slug;
      $counter = 1;
      $checkStmt = $pdo->prepare("SELECT id FROM clubs WHERE slug = ?");
      $checkStmt->execute([$slug]);
      while ($checkStmt->fetch()) {
        $slug = $baseSlug . '-' . $counter++;
        $checkStmt->execute([$slug]);
      }

      // Required date fields (placeholder values)
      $today = date('Y-m-d');
      $future = date('Y-m-d', strtotime('+30 days'));

      $stmt = $pdo->prepare("
        INSERT INTO clubs (name, slug, trial_start_date, trial_end_date, access_until)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->execute([$name, $slug, $today, $future, $future]);

      $clubId = $pdo->lastInsertId();

      $stmt = $pdo->prepare("
        INSERT INTO club_admins (club_id, user_id, admin_role)
        VALUES (?, ?, 'owner')
      ");
      $stmt->execute([$clubId, $userId]);

      $pdo->commit();
      $success = true;

    } catch (Throwable $ex) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = "Failed to create club.";
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create Club</title>
</head>
<body>
  <h1>Create Club</h1>

  <?php if ($success): ?>
    <p style="color:green;"><strong>Club created!</strong> <a href="/public/dashboard.php">Go to Dashboard</a></p>
  <?php endif; ?>

  <?php if ($errors): ?>
    <ul style="color:red;">
      <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="post">
    <div>
      <label>Club Name *</label><br>
      <input type="text" name="name" required>
    </div>
    <div style="margin-top:10px;">
      <button type="submit">Create</button>
    </div>
  </form>
  <?php endif; ?>

  <p><a href="/public/dashboard.php">Back to Dashboard</a></p>
</body>
</html>
