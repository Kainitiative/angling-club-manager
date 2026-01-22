<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<h3>Debug Info</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";

try {
    require_once __DIR__ . '/../../app/bootstrap.php';
    echo "<p style='color:green'>Bootstrap loaded OK</p>";
    
    echo "<p>DB Driver: " . (defined('DB_DRIVER') ? DB_DRIVER : 'undefined') . "</p>";
    
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color:green'>Database connection OK</p>";
    
    // Check if club_finances table exists
    if (DB_DRIVER === 'mysql') {
        $stmt = $pdo->query("SHOW TABLES LIKE 'club_finances'");
        $exists = $stmt->rowCount() > 0;
    } else {
        $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'club_finances')");
        $exists = $stmt->fetchColumn();
    }
    echo "<p>club_finances table exists: " . ($exists ? 'YES' : 'NO') . "</p>";
    
    // Check club_members table structure
    if (DB_DRIVER === 'mysql') {
        $stmt = $pdo->query("DESCRIBE club_members");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>club_members columns: " . implode(', ', $columns) . "</p>";
    }
    
    // Test the problematic query
    $clubId = 1;
    $userId = $_SESSION['user_id'] ?? 0;
    echo "<p>Testing with club_id=$clubId, user_id=$userId</p>";
    
    $stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
    $stmt->execute([$clubId, $userId]);
    $row = $stmt->fetch();
    echo "<p style='color:green'>Member query OK. Result: " . ($row ? json_encode($row) : 'null') . "</p>";
    
    echo "<h4>All checks passed!</h4>";
    
} catch (Throwable $e) {
    echo "<p style='color:red'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
