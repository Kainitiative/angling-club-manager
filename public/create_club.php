<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$userId = current_user_id();
$errors = [];
$createdClubId = null;

function post($key, $default = '') {
  return $_POST[$key] ?? $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim(post('name'));
  $county = trim(post('county'));
  $city = trim(post('city'));

  if ($name === '') $errors[] = "Club name is required.";

  if (!$errors) {
    try {
      $pdo->beginTransaction();

      $clubId = uuidv4();
      $stmt = $pdo->prepare("
        INSERT INTO clubs (id, name, county, city, created_at)
        VALUES (:id, :name, :county, :city, NOW())
      ");
      $stmt->execute([
        ':id' => $clubId,
        ':name' => $name,
        ':county' => $county !== '' ? $county : null,
        ':city' => $city !== '' ? $city : null,
      ]);

      $adminId = uuidv4();
      $stmt = $pdo->prepare("
        INSERT INTO club_admins (id, club_id, user_id, role, created_at)
        VALUES (:id, :club_id, :user_id, :role, NOW())
      ");
      $stmt->execute([
        ':id' => $adminId,
        ':club_id' => $clubId,
        ':user_id' => $userId,
        ':role' => 'owner',
      ]);

      $pdo->commit();
      $createdClubId = $clubId;

    } catch (Throwable $e) {
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h1>Create Club</h1>

  <?php if ($createdClubId): ?>
    <div style="padding:10px;border:1px solid #2d6;margin:10px 0;">
      Created! Club ID: <strong><?= e($createdClubId) ?></strong>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div style="padding:10px;border:1px solid #c33;margin:10px 0;">
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="/create_club.php">
    <div>
      <label>Club Name *</label><br>
      <input type="text" name="name" value="<?= e(post('name')) ?>" required>
    </div>

    <div style="margin-top:10px;">
      <label>County</label><br>
      <input type="text" name="county" value="<?= e(post('county')) ?>">
    </div>

    <div style="margin-top:10px;">
      <label>City / Town</label><br>
      <input type="text" name="city" value="<?= e(post('city')) ?>">
    </div>

    <div style="margin-top:15px;">
      <button type="submit">Create</button>
    </div>
  </form>
</body>
</html>
