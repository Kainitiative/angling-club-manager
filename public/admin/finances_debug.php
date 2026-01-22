<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<h3>Debug Info - Step by Step</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";

try {
    echo "<p>Step 1: Loading bootstrap...</p>";
    require_once __DIR__ . '/../../app/bootstrap.php';
    echo "<p style='color:green'>Bootstrap loaded OK</p>";
    
    echo "<p>Step 2: Loading layout file...</p>";
    require_once __DIR__ . '/../../app/layout/club_admin_shell.php';
    echo "<p style='color:green'>Layout file loaded OK</p>";
    
    echo "<p>Step 3: Checking login...</p>";
    require_login();
    echo "<p style='color:green'>Login check OK - User ID: " . current_user_id() . "</p>";
    
    echo "<p>Step 4: Getting club...</p>";
    $clubId = (int)($_GET['club_id'] ?? 1);
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch();
    echo "<p style='color:green'>Club query OK: " . ($club ? $club['name'] : 'NOT FOUND') . "</p>";
    
    if ($club) {
        echo "<p>Step 5: Getting admin row...</p>";
        $userId = current_user_id();
        $stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
        $stmt->execute([$clubId, $userId]);
        $adminRow = $stmt->fetch();
        echo "<p style='color:green'>Admin check OK: " . ($adminRow ? $adminRow['admin_role'] : 'not admin') . "</p>";
        
        echo "<p>Step 6: Getting member row...</p>";
        $stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
        $stmt->execute([$clubId, $userId]);
        $memberRow = $stmt->fetch();
        $committeeRole = $memberRow ? ($memberRow['committee_role'] ?? 'member') : 'member';
        echo "<p style='color:green'>Member check OK: " . $committeeRole . "</p>";
        
        echo "<p>Step 7: Checking club_finances table...</p>";
        $stmt = $pdo->query("SHOW TABLES LIKE 'club_finances'");
        $exists = $stmt->rowCount() > 0;
        echo "<p>club_finances exists: " . ($exists ? 'YES' : 'NO') . "</p>";
        
        if ($exists) {
            echo "<p>Step 8: Querying finances...</p>";
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM club_finances WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $count = $stmt->fetchColumn();
            echo "<p style='color:green'>Finances query OK: $count records</p>";
        }
        
        echo "<p>Step 9: Testing layout function...</p>";
        ob_start();
        club_admin_shell_start($pdo, $club, ['title' => 'Test']);
        $output = ob_get_clean();
        echo "<p style='color:green'>Layout shell started OK (" . strlen($output) . " bytes)</p>";
    }
    
    echo "<h4 style='color:green'>ALL CHECKS PASSED!</h4>";
    
} catch (Throwable $e) {
    echo "<p style='color:red'><strong>ERROR at step above:</strong></p>";
    echo "<p style='color:red'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
