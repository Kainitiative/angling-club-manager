<?php
/**
 * Angling Ireland - Configuration Template
 * 
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to 'config.local.php'
 * 2. Update the database credentials below with your cPanel MySQL details
 * 3. Visit your website - the installer will run automatically
 * 
 * For cPanel/LetsHost:
 * - Database name format: cpanel_username_dbname
 * - Database user format: cpanel_username_dbuser
 */

return [
    'db' => [
        'driver'  => 'mysql',
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'your_database_name',
        'user'    => 'your_database_user',
        'pass'    => 'your_database_password',
        'charset' => 'utf8mb4',
    ],
    
    'base_url' => '',
    
    'site' => [
        'name'    => 'Angling Ireland',
        'domain'  => 'anglingireland.ie',
        'email'   => 'info@anglingireland.ie',
    ],
    
    'environment' => 'production',
];
