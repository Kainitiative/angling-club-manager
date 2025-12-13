<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$updatesDir = __DIR__ . '/../db/updates';

if (!is_dir($updatesDir)) {
    exit("No updates directory found.\n");
}

$trackingTable = 'schema_migrations';
$pdo->exec("
    CREATE TABLE IF NOT EXISTS {$trackingTable} (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$stmt = $pdo->query("SELECT migration FROM {$trackingTable}");
$applied = $stmt->fetchAll(PDO::FETCH_COLUMN);

$files = glob($updatesDir . '/*.sql');
sort($files);

$count = 0;
foreach ($files as $file) {
    $filename = basename($file);
    
    if (in_array($filename, $applied, true)) {
        continue;
    }
    
    $sql = file_get_contents($file);
    if (trim($sql) === '') {
        continue;
    }
    
    try {
        $pdo->exec($sql);
        
        $stmt = $pdo->prepare("INSERT INTO {$trackingTable} (migration) VALUES (?)");
        $stmt->execute([$filename]);
        
        echo "Applied: {$filename}\n";
        $count++;
    } catch (PDOException $e) {
        echo "ERROR applying {$filename}: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if ($count === 0) {
    echo "No new migrations to apply.\n";
} else {
    echo "Applied {$count} migration(s).\n";
}
