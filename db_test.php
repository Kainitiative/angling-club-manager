<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.local.php';

$db = $config['db'];

$dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";

try {
  $pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  echo "<h1>DB Connected ✅</h1>";
  echo "<p>Database: " . htmlspecialchars($db['name']) . "</p>";
} catch (Throwable $e) {
  http_response_code(500);
  echo "<h1>DB Connection Failed ❌</h1>";
  echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
