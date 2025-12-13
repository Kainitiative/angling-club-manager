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

$dsn = "pgsql:host={$db['host']};port={$db['port']};dbname={$db['name']}";

try {
  $pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  exit("DB connection failed: " . htmlspecialchars($e->getMessage()));
}
