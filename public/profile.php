<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_login();

$userId = current_user_id();
$errors = [];
$success = false;

$stmt = $pdo->prepare("SELECT id, name, email, profile_picture_url, dob, phone, town, city, country, gender FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
  http_response_code(404);
  exit("User not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim((string)($_POST['name'] ?? ''));
  $dob = trim((string)($_POST['dob'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $town = trim((string)($_POST['town'] ?? ''));
  $city = trim((string)($_POST['city'] ?? ''));
  $country = trim((string)($_POST['country'] ?? ''));
  $gender = trim((string)($_POST['gender'] ?? ''));
  $profile_picture_url = trim((string)($_POST['profile_picture_url'] ?? ''));

  if ($name === '') {
    $errors[] = "Name is required.";
  }
  if (strlen($name) > 120) {
    $errors[] = "Name must be 120 characters or less.";
  }

  if (!$errors) {
    $stmt = $pdo->prepare("
      UPDATE users SET 
        name = ?,
        profile_picture_url = ?,
        dob = ?,
        phone = ?,
        town = ?,
        city = ?,
        country = ?,
        gender = ?,
        updated_at = NOW()
      WHERE id = ?
    ");
    $stmt->execute([
      $name,
      $profile_picture_url === '' ? null : $profile_picture_url,
      $dob === '' ? null : $dob,
      $phone === '' ? null : $phone,
      $town === '' ? null : $town,
      $city === '' ? null : $city,
      $country === '' ? null : $country,
      $gender === '' ? null : $gender,
      $userId
    ]);
    $success = true;

    // Reload user
    $stmt = $pdo->prepare("SELECT id, name, email, profile_picture_url, dob, phone, town, city, country, gender FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
  }
}

$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&size=150&background=0D6EFD&color=fff';
$avatarUrl = $user['profile_picture_url'] ?: $defaultAvatar;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Your Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .profile-picture {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #dee2e6;
    }
  </style>
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
  <h1 class="mb-4">Your Profile</h1>

  <?php if ($success): ?>
    <div class="alert alert-success">Profile updated successfully!</div>
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

  <div class="card">
    <div class="card-body">
      <form method="post">
        <div class="text-center mb-4">
          <img src="<?= e($avatarUrl) ?>" alt="Profile Picture" class="profile-picture mb-3">
          <div>
            <label class="form-label">Profile Picture URL</label>
            <input type="url" class="form-control" name="profile_picture_url" value="<?= e($user['profile_picture_url'] ?? '') ?>" placeholder="https://example.com/photo.jpg">
            <div class="form-text">Enter a URL to your profile picture, or leave blank for default.</div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Name *</label>
            <input type="text" class="form-control" name="name" value="<?= e($user['name']) ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
            <div class="form-text">Email cannot be changed.</div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control" name="dob" value="<?= e($user['dob'] ?? '') ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Gender</label>
            <select class="form-select" name="gender">
              <option value="">-- Select --</option>
              <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
              <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
              <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
              <option value="prefer_not_to_say" <?= ($user['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : '' ?>>Prefer not to say</option>
            </select>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone</label>
            <input type="tel" class="form-control" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+353 1234567">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Town</label>
            <input type="text" class="form-control" name="town" value="<?= e($user['town'] ?? '') ?>" placeholder="e.g. Tallaght">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">City</label>
            <input type="text" class="form-control" name="city" value="<?= e($user['city'] ?? '') ?>" placeholder="e.g. Dublin">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Country</label>
            <input type="text" class="form-control" name="country" value="<?= e($user['country'] ?? '') ?>" placeholder="e.g. Ireland">
          </div>
        </div>

        <div class="mt-3">
          <button type="submit" class="btn btn-primary">Save Profile</button>
          <a href="/public/dashboard.php" class="btn btn-outline-secondary ms-2">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
