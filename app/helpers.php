<?php
declare(strict_types=1);

function redirect(string $path): void {
  header("Location: $path");
  exit;
}

function require_login(): void {
  if (empty($_SESSION['user_id'])) {
    redirect('/public/auth/login.php');
  }
}

function current_user_id(): ?int {
  return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}
