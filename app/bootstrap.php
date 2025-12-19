<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

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

// Define BASE_URL for redirects (set in config.local.php for Laragon)
if (isset($config['base_url'])) {
  define('BASE_URL', $config['base_url']);
}

$driver = $db['driver'] ?? 'mysql';
if ($driver === 'pgsql') {
  $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname={$db['name']}";
} else {
  $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
}

try {
  $pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  exit("DB connection failed: " . htmlspecialchars($e->getMessage()));
}

// Include helpers
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/notifications.php';

function current_user() {
  global $pdo;
  $userId = current_user_id();
  if (!$userId) return null;
  
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  return $stmt->fetch();
}
