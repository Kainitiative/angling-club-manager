<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim((string)($_POST['name'] ?? ''));
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $password = (string)($_POST['password'] ?? '');

  if ($name === '') $errors[] = "Name is required.";
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
  if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";

  if (!$errors) {
    // Check email unique
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $errors[] = "That email is already registered.";
    } else {
      $hash = password_hash($password, PASSWORD_BCRYPT);

      $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
      $stmt->execute([$name, $email, $hash]);

      $_SESSION['user_id'] = (int)$pdo->lastInsertId();
      redirect('/public/dashboard.php');
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
  <h1 class="mb-3">Create account</h1>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="card card-body">
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input class="form-control" name="name" value="<?= htmlspecialchars($name) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input class="form-control" name="email" type="email" value="<?= htmlspecialchars($email) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password (min 8)</label>
      <input class="form-control" name="password" type="password" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">Create account</button>
  </form>

  <p class="mt-3 text-center">
    Already have an account? <a href="/public/auth/login.php">Log in</a>
  </p>
</div>
</body>
</html>
