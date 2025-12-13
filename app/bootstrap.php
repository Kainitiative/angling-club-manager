<?php
declare(strict_types=1);

// Always show errors in local dev (we'll harden this later)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Start session for auth
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Load local config (kept out of git)
$configPath = __DIR__ . '/../config.local.php';
if (!file_exists($configPath)) {
  http_response_code(500);
  exit("Missing config.local.php");
}

$config = require $configPath;

if (!isset($config['db'])) {
  http_response_code(500);
  exit("Invalid config.local.php (missing db settings)");
}

$db = $config['db'];

$dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";

try {
  $pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  exit("DB connection failed: " . htmlspecialchars($e->getMessage()));
}

// Make $pdo available everywhere that includes bootstrap.php
