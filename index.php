<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$errors = [];
$email = '';

// If already logged in, redirect to dashboard
if (current_user_id()) {
  redirect('/public/dashboard.php');
}

// Handle login form submission
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
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .hero {
      background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
      color: white;
      padding: 60px 0;
    }
    .login-card {
      max-width: 400px;
    }
  </style>
</head>
<body>

<section class="hero">
  <div class="container text-center">
    <h1 class="display-4 fw-bold">Angling Club Manager</h1>
    <p class="lead">Manage your fishing club with ease. Members, meetings, competitions and more.</p>
  </div>
</section>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card login-card mx-auto shadow">
        <div class="card-body p-4">
          <h2 class="card-title text-center mb-4">Log In</h2>

          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <?= e($errors[0]) ?>
            </div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Log In</button>
          </form>

          <p class="text-center mt-3 mb-0">
            New here? <a href="/public/auth/register.php">Create an account</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="bg-light py-4 mt-5">
  <div class="container text-center text-muted">
    <p class="mb-0">Angling Club Manager</p>
  </div>
</footer>

</body>
</html>
