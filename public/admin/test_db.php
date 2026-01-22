<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h2>Database Connection Test</h2>";

try {
    require_once __DIR__ . '/../../app/bootstrap.php';
    echo "<p style='color:green;'>Bootstrap loaded OK</p>";
    
    echo "<p>Testing database tables...</p>";
    
    $tables = ['club_profile_settings', 'club_membership_fees', 'club_perks', 'club_gallery', 'sponsors'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "<p style='color:green;'>Table '$table' exists</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Table '$table' MISSING: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h3>PHP Info</h3>";
    echo "<p>PHP Version: " . phpversion() . "</p>";
    echo "<p>GD Extension: " . (extension_loaded('gd') ? 'Loaded' : 'NOT LOADED') . "</p>";
    
} catch (Throwable $e) {
    echo "<p style='color:red;'><b>Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . " (line " . $e->getLine() . ")</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
