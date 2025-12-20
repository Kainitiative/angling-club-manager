<?php
/**
 * Super Admin Shell - Layout for platform administration pages
 * 
 * Usage:
 * $pageTitle = 'Users';
 * $currentPage = 'users';
 * include 'app/layout/super_admin_shell.php';
 * super_admin_shell_start($pdo);
 * // ... your content here ...
 * super_admin_shell_end();
 */

require_once __DIR__ . '/../nav_config.php';

function super_admin_shell_start($pdo, $options = []) {
    global $pageTitle, $currentPage;
    
    $userId = current_user_id();
    $user = current_user($pdo);
    
    $pageTitle = $options['title'] ?? $pageTitle ?? 'Super Admin';
    $currentPage = $options['page'] ?? $currentPage ?? 'dashboard';
    $section = $options['section'] ?? $pageTitle;
    
    $nav = get_super_admin_nav();
    $breadcrumbs = get_breadcrumbs('super_admin', ['section' => $section]);
    
    $avatarUrl = !empty($user['avatar_url']) ? $user['avatar_url'] : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['email'] ?? ''))) . '?d=mp&s=80';
    
    $customStyles = '
        .sidebar {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        }
        .sidebar-link.active {
            background: #e94560;
        }
    ';
    
    include __DIR__ . '/header.php';
?>
<div class="layout-sidebar">
    <div class="sidebar-overlay"></div>
    
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="/public/superadmin/" class="sidebar-brand">
                <i class="bi bi-shield-lock"></i>
                Super Admin
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-title">Platform</div>
                <?php foreach ($nav as $item): ?>
                    <a href="<?= e($item['url']) ?>" class="sidebar-link <?= $currentPage === $item['id'] ? 'active' : '' ?>">
                        <i class="bi bi-<?= $item['icon'] ?>"></i>
                        <?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Navigation</div>
                <a href="/public/dashboard.php" class="sidebar-link">
                    <i class="bi bi-arrow-left"></i>
                    Back to App
                </a>
            </div>
        </nav>
        
        <div class="user-menu">
            <div class="dropdown">
                <button class="user-menu-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <img src="<?= e($avatarUrl) ?>" alt="" class="user-avatar">
                    <span class="text-truncate" style="max-width: 140px;"><?= e($user['name'] ?? 'Admin') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><a class="dropdown-item" href="/public/profile.php"><i class="bi bi-person-gear me-2"></i>Edit Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/public/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </aside>
    
    <main class="main-content">
        <header class="top-bar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" type="button">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <?= render_breadcrumbs($breadcrumbs) ?>
            </div>
            <div>
                <span class="badge bg-danger">Super Admin Mode</span>
            </div>
        </header>
        
        <div class="content-area">
<?php
}

function super_admin_shell_end() {
?>
        </div>
    </main>
</div>
<?php
    include __DIR__ . '/footer.php';
}
