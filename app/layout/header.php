<?php
/**
 * Shared HTML header - outputs DOCTYPE through opening body tag
 * Usage: include this, then your content, then footer.php
 */
if (!isset($pageTitle)) $pageTitle = 'Angling Ireland';
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
    <link href="/assets/css/enhancements.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 56px;
            --mobile-nav-height: 64px;
            --primary-color: #0d6efd;
            --sidebar-bg: #212529;
            --sidebar-text: #adb5bd;
            --sidebar-hover: #343a40;
            --sidebar-active: #0d6efd;
            --safe-area-bottom: env(safe-area-inset-bottom, 0px);
        }
        
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            min-height: 100vh;
            min-height: -webkit-fill-available;
        }
        
        html {
            height: -webkit-fill-available;
        }
        
        /* ===== MOBILE FIRST - Base styles for mobile ===== */
        
        /* Sidebar - Hidden on mobile by default */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            height: 100dvh;
            overflow-y: auto;
            z-index: 1050;
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        /* Layout - Mobile first */
        .layout-sidebar {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100dvh;
        }
        
        .main-content {
            flex: 1;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            margin-left: 0;
            padding-bottom: calc(var(--mobile-nav-height) + var(--safe-area-bottom));
        }
        
        /* Mobile Top Bar */
        .top-bar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1030;
            min-height: var(--header-height);
            gap: 0.5rem;
        }
        
        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            padding: 0.5rem;
            color: inherit;
            min-width: 44px;
            min-height: 44px;
            border-radius: 0.375rem;
            transition: background 0.15s ease;
        }
        
        .sidebar-toggle:hover,
        .sidebar-toggle:active {
            background: rgba(0,0,0,0.05);
        }
        
        .sidebar-toggle .bi {
            font-size: 1.5rem;
        }
        
        /* Content Area - Mobile */
        .content-area {
            flex: 1;
            padding: 1rem;
            background: #f8f9fa;
        }
        
        /* Mobile Bottom Navigation */
        .mobile-nav {
            display: flex;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(var(--mobile-nav-height) + var(--safe-area-bottom));
            padding-bottom: var(--safe-area-bottom);
            background: #fff;
            border-top: 1px solid #dee2e6;
            z-index: 1020;
            justify-content: space-around;
            align-items: stretch;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
        }
        
        .mobile-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.25rem;
            color: #6c757d;
            text-decoration: none;
            font-size: 0.65rem;
            gap: 0.2rem;
            transition: color 0.15s ease;
            min-width: 0;
            position: relative;
        }
        
        .mobile-nav-item .bi {
            font-size: 1.25rem;
        }
        
        .mobile-nav-item.active {
            color: var(--primary-color);
        }
        
        .mobile-nav-item:active {
            background: rgba(0,0,0,0.03);
        }
        
        .mobile-nav-badge {
            position: absolute;
            top: 0.25rem;
            right: calc(50% - 1rem);
            background: #dc3545;
            color: #fff;
            font-size: 0.6rem;
            padding: 0.1rem 0.35rem;
            border-radius: 10px;
            font-weight: 600;
            min-width: 1rem;
            text-align: center;
        }
        
        /* Sidebar Components */
        .sidebar-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 0;
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
            flex: 1;
            overflow-y: auto;
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
            padding: 0.75rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            transition: all 0.15s ease;
            min-height: 44px;
        }
        
        .sidebar-link:hover,
        .sidebar-link:active {
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
            flex-shrink: 0;
        }
        
        .sidebar-badge {
            margin-left: auto;
            font-size: 0.7rem;
        }
        
        /* Club Selector Dropdown */
        .club-selector {
            background: rgba(255,255,255,0.1);
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin: 0.75rem;
            margin-top: 0;
            flex-shrink: 0;
        }
        
        .club-selector-btn {
            width: 100%;
            text-align: left;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 0.625rem 0.75rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 44px;
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
            flex-shrink: 0;
        }
        
        .user-menu-btn {
            width: 100%;
            text-align: left;
            background: transparent;
            border: none;
            color: var(--sidebar-text);
            padding: 0.625rem 0.75rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-height: 44px;
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
        
        /* Cards and Widgets - Mobile */
        .stat-card {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            border: 1px solid #e9ecef;
        }
        
        .stat-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        /* Page Header - Mobile */
        .page-header {
            margin-bottom: 1rem;
        }
        
        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .page-subtitle {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.875rem;
        }
        
        /* Mobile Tables */
        .table-responsive {
            margin: 0 -1rem;
            padding: 0 1rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Mobile Cards Grid */
        .row.g-3 > .col-md-4,
        .row.g-3 > .col-md-6,
        .row.g-4 > .col-md-4,
        .row.g-4 > .col-md-6 {
            margin-bottom: 0.75rem;
        }
        
        /* ===== TABLET+ STYLES ===== */
        @media (min-width: 768px) {
            .content-area {
                padding: 1.25rem;
            }
            
            .page-title {
                font-size: 1.375rem;
            }
            
            .stat-card {
                padding: 1.125rem;
            }
            
            .stat-card-icon {
                width: 44px;
                height: 44px;
            }
        }
        
        /* ===== DESKTOP STYLES ===== */
        @media (min-width: 992px) {
            /* Show sidebar on desktop */
            .sidebar {
                transform: translateX(0);
            }
            
            .sidebar-overlay {
                display: none !important;
            }
            
            .main-content {
                margin-left: var(--sidebar-width);
                padding-bottom: 0;
            }
            
            /* Hide mobile nav on desktop */
            .mobile-nav {
                display: none;
            }
            
            /* Hide toggle on desktop */
            .sidebar-toggle {
                display: none;
            }
            
            .top-bar {
                padding: 0.75rem 1.5rem;
            }
            
            .content-area {
                padding: 1.5rem;
            }
            
            .page-header {
                margin-bottom: 1.5rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .stat-card-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
            }
        }
        
        /* ===== LARGE DESKTOP ===== */
        @media (min-width: 1200px) {
            .content-area {
                padding: 2rem;
            }
        }
        
        /* ===== iOS Safe Areas ===== */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .mobile-nav {
                padding-bottom: env(safe-area-inset-bottom);
            }
            
            .main-content {
                padding-bottom: calc(var(--mobile-nav-height) + env(safe-area-inset-bottom));
            }
            
            @media (min-width: 992px) {
                .main-content {
                    padding-bottom: 0;
                }
            }
        }
        
        <?php if (!empty($customStyles)) echo $customStyles; ?>
    </style>
    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= e($bodyClass) ?>">
