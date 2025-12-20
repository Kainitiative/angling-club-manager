<?php
/**
 * Shared HTML header - outputs DOCTYPE through opening body tag
 * Usage: include this, then your content, then footer.php
 */
if (!isset($pageTitle)) $pageTitle = 'Angling Club Manager';
if (!isset($bodyClass)) $bodyClass = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 56px;
            --primary-color: #0d6efd;
            --sidebar-bg: #212529;
            --sidebar-text: #adb5bd;
            --sidebar-hover: #343a40;
            --sidebar-active: #0d6efd;
        }
        
        body {
            min-height: 100vh;
        }
        
        /* Sidebar Layout */
        .layout-sidebar {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .sidebar-section {
            padding: 0 0.75rem;
            margin-bottom: 1rem;
        }
        
        .sidebar-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6c757d;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.75rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            transition: all 0.15s ease;
        }
        
        .sidebar-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }
        
        .sidebar-link.active {
            background: var(--sidebar-active);
            color: #fff;
        }
        
        .sidebar-link .bi {
            font-size: 1.1rem;
            width: 1.25rem;
            text-align: center;
        }
        
        .sidebar-badge {
            margin-left: auto;
            font-size: 0.7rem;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .top-bar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .content-area {
            flex: 1;
            padding: 1.5rem;
            background: #f8f9fa;
        }
        
        /* Club Selector Dropdown */
        .club-selector {
            background: rgba(255,255,255,0.1);
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin: 0.75rem;
            margin-top: 0;
        }
        
        .club-selector-btn {
            width: 100%;
            text-align: left;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .club-selector-btn:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
        }
        
        /* User Menu */
        .user-menu {
            padding: 0.75rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        
        .user-menu-btn {
            width: 100%;
            text-align: left;
            background: transparent;
            border: none;
            color: var(--sidebar-text);
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-menu-btn:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Mobile Toggle */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            padding: 0.5rem;
            color: inherit;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
        }
        
        /* Cards and Widgets */
        .stat-card {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1.25rem;
            border: 1px solid #e9ecef;
        }
        
        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .page-subtitle {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        <?php if (!empty($customStyles)) echo $customStyles; ?>
    </style>
    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= e($bodyClass) ?>">
