<?php
/**
 * Angling Ireland - Automatic Database Installer
 * 
 * This script runs on first load to set up the database tables.
 * Once complete, it creates a setup.lock file to prevent re-running.
 */
declare(strict_types=1);

$lockFile = __DIR__ . '/setup.lock';

if (file_exists($lockFile)) {
    header('Location: /');
    exit;
}

$configPath = __DIR__ . '/config.local.php';
$configExists = file_exists($configPath);
$error = '';
$success = '';
$step = 'config';

if ($configExists) {
    $config = require $configPath;
    $step = 'install';
    
    try {
        $db = $config['db'];
        $driver = $db['driver'] ?? 'mysql';
        
        if ($driver === 'pgsql') {
            $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname={$db['name']}";
        } else {
            $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
        }
        
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $tablesExist = $stmt->rowCount() > 0;
        
        if ($tablesExist) {
            file_put_contents($lockFile, date('Y-m-d H:i:s') . " - Installation already complete.\n");
            header('Location: /');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
            $schemaFile = __DIR__ . '/db/install_schema.sql';
            
            if (!file_exists($schemaFile)) {
                throw new Exception('Schema file not found: db/install_schema.sql');
            }
            
            $schema = file_get_contents($schemaFile);
            
            // Remove comments and split by semicolon
            $schema = preg_replace('/--.*$/m', '', $schema);
            $schema = preg_replace('/\/\*.*?\*\//s', '', $schema);
            
            $statements = array_filter(
                array_map('trim', explode(';', $schema)),
                fn($s) => !empty($s)
            );
            
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $executedCount = 0;
            foreach ($statements as $sql) {
                $sql = trim($sql);
                if (!empty($sql) && strlen($sql) > 5) {
                    try {
                        $pdo->exec($sql);
                        $executedCount++;
                    } catch (PDOException $e) {
                        // Log the error but continue - some statements may fail if tables exist
                        error_log("SQL Error: " . $e->getMessage() . " | SQL: " . substr($sql, 0, 100));
                    }
                }
            }
            
            // Verify users table was created
            $checkStmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($checkStmt->rowCount() === 0) {
                throw new Exception("Failed to create database tables. Executed $executedCount statements but users table not found. Check that your MySQL user has CREATE TABLE permissions.");
            }
            
            if (isset($_POST['admin_email']) && isset($_POST['admin_password']) && isset($_POST['admin_name'])) {
                $adminEmail = strtolower(trim($_POST['admin_email']));
                $adminName = trim($_POST['admin_name']);
                $adminPassword = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, is_super_admin) VALUES (?, ?, ?, 'super_admin', 1)");
                $stmt->execute([$adminName, $adminEmail, $adminPassword]);
            }
            
            file_put_contents($lockFile, date('Y-m-d H:i:s') . " - Installation completed successfully.\n");
            $success = 'Installation complete! Redirecting to homepage...';
            header('Refresh: 3; URL=/');
        }
        
    } catch (PDOException $e) {
        $error = 'Database connection failed: ' . htmlspecialchars($e->getMessage());
        $step = 'config';
    } catch (Exception $e) {
        $error = 'Installation error: ' . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Angling Ireland</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); min-height: 100vh; }
        .install-card { max-width: 600px; margin: 50px auto; }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 2rem; }
        .step { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e9ecef; color: #6c757d; font-weight: bold; margin: 0 10px; }
        .step.active { background: #0d6efd; color: white; }
        .step.complete { background: #198754; color: white; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="install-card">
        <div class="text-center mb-4">
            <h1 class="text-white"><i class="bi bi-water me-2"></i>Angling Ireland</h1>
            <p class="text-white-50">Database Installation</p>
        </div>
        
        <div class="card shadow-lg">
            <div class="card-body p-4">
                <div class="step-indicator">
                    <div class="step <?= $step === 'config' ? 'active' : ($configExists ? 'complete' : '') ?>">1</div>
                    <div class="step <?= $step === 'install' ? 'active' : '' ?>">2</div>
                    <div class="step <?= !empty($success) ? 'complete' : '' ?>">3</div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i><?= $success ?>
                    </div>
                <?php elseif ($step === 'config'): ?>
                    <h4 class="mb-3"><i class="bi bi-gear me-2"></i>Step 1: Configuration</h4>
                    <p class="text-muted">Before we can install, you need to configure your database connection.</p>
                    
                    <div class="alert alert-info">
                        <strong>Instructions:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Copy <code>config.sample.php</code> to <code>config.local.php</code></li>
                            <li>Edit <code>config.local.php</code> with your cPanel database credentials</li>
                            <li>Refresh this page</li>
                        </ol>
                    </div>
                    
                    <div class="bg-light p-3 rounded">
                        <strong>cPanel Database Setup:</strong>
                        <ul class="mb-0 mt-2 small">
                            <li>Go to cPanel > MySQL Databases</li>
                            <li>Create a new database</li>
                            <li>Create a new user with a strong password</li>
                            <li>Add the user to the database with ALL PRIVILEGES</li>
                        </ul>
                    </div>
                    
                <?php elseif ($step === 'install'): ?>
                    <h4 class="mb-3"><i class="bi bi-database me-2"></i>Step 2: Install Database</h4>
                    <p class="text-muted">Your database connection is working. Now let's create the tables and set up your admin account.</p>
                    
                    <form method="post">
                        <h5 class="mt-4 mb-3">Create Admin Account</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="admin_name" class="form-control" required placeholder="Your Name">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="admin_email" class="form-control" required placeholder="admin@anglingireland.ie">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="admin_password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" name="install" value="1" class="btn btn-primary btn-lg">
                                <i class="bi bi-download me-2"></i>Install Database
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <p class="text-center text-white-50 mt-4 small">
            &copy; <?= date('Y') ?> Angling Ireland - Free Forever for Irish Anglers
        </p>
    </div>
</div>
</body>
</html>
