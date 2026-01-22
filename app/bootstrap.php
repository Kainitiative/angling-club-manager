<?php
declare(strict_types=1);

$lockFile = __DIR__ . '/../setup.lock';
$configPath = __DIR__ . '/../config.local.php';

if (!file_exists($configPath)) {
  header('Location: /install.php');
  exit;
}

$config = require $configPath;

if (!isset($config['db'])) {
  header('Location: /install.php');
  exit;
}

$isProduction = ($config['environment'] ?? 'development') === 'production';

if ($isProduction) {
  ini_set('display_errors', '0');
  ini_set('display_startup_errors', '0');
  ini_set('log_errors', '1');
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$db = $config['db'];

if (isset($config['base_url'])) {
  define('BASE_URL', $config['base_url']);
}

if (isset($config['site'])) {
  define('SITE_NAME', $config['site']['name'] ?? 'Angling Ireland');
  define('SITE_DOMAIN', $config['site']['domain'] ?? 'anglingireland.ie');
  define('SITE_EMAIL', $config['site']['email'] ?? 'info@anglingireland.ie');
} else {
  define('SITE_NAME', 'Angling Ireland');
  define('SITE_DOMAIN', 'anglingireland.ie');
  define('SITE_EMAIL', 'info@anglingireland.ie');
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
  
  if (!file_exists($lockFile)) {
    $requiredTables = ['users', 'clubs', 'club_members', 'competitions', 'catch_logs'];
    foreach ($requiredTables as $table) {
      $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
      if ($stmt->rowCount() === 0) {
        header('Location: /install.php');
        exit;
      }
    }
  }
} catch (Throwable $e) {
  if ($isProduction) {
    http_response_code(500);
    include __DIR__ . '/error_pages/500.php';
    exit;
  }
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
