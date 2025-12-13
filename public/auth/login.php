<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $password = (string)($_POST['password'] ?? '');

  $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    $errors[] = "Invalid email or password.";
  } else {
    $_SESSION['user_id'] = (int)$user['id'];
    redirect('/public/dashboard.php');
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
  <h1 class="mb-3">Log in</h1>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors[0]) ?></div>
  <?php endif; ?>

  <form method="post" class="card card-body">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input class="form-control" name="email" type="email" value="<?= htmlspecialchars($email) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input class="form-control" name="password" type="password" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">Log in</button>
  </form>

  <p class="mt-3 text-center">
    New here? <a href="/public/auth/register.php">Create an account</a>
  </p>
</div>
</body>
</html>
