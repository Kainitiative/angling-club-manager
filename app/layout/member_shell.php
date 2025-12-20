<?php
/**
 * Member Shell - Layout for logged-in user personal pages
 * (Dashboard, Messages, Notifications, Tasks, Profile, etc.)
 * 
 * Usage:
 * $pageTitle = 'Dashboard';
 * $currentPage = 'home';
 * include 'app/layout/member_shell.php';
 * member_shell_start($pdo);
 * // ... your content here ...
 * member_shell_end();
 */

require_once __DIR__ . '/../nav_config.php';

function member_shell_start($pdo, $options = []) {
    global $pageTitle, $currentPage;
    
    $userId = current_user_id();
    $user = current_user($pdo);
    
    $pageTitle = $options['title'] ?? $pageTitle ?? 'Dashboard';
    $currentPage = $options['page'] ?? $currentPage ?? 'home';
    $section = $options['section'] ?? '';
    
    // Get badge counts
    $unreadNotifications = 0;
    $unreadMessages = 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $unreadNotifications = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM club_messages WHERE recipient_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $unreadMessages = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}
    
    $nav = get_member_nav($pdo, $userId);
    $breadcrumbs = get_breadcrumbs('member', ['section' => $section]);
    $isSuperAdmin = function_exists('is_super_admin') && is_super_admin($pdo);
    
    $avatarUrl = !empty($user['avatar_url']) ? $user['avatar_url'] : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['email'] ?? ''))) . '?d=mp&s=80';
    
    include __DIR__ . '/header.php';
?>
<div class="layout-sidebar">
    <div class="sidebar-overlay"></div>
    
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="/public/dashboard.php" class="sidebar-brand">
                <i class="bi bi-water"></i>
                Angling Club Manager
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-title">Main</div>
                <?php foreach ($nav['main'] as $item): ?>
                    <a href="<?= e($item['url']) ?>" class="sidebar-link <?= $currentPage === $item['id'] ? 'active' : '' ?>">
                        <i class="bi bi-<?= $item['icon'] ?>"></i>
                        <?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Personal</div>
                <?php foreach ($nav['personal'] as $item): ?>
                    <?php 
                    $badge = 0;
                    if (($item['badge'] ?? '') === 'notifications') $badge = $unreadNotifications;
                    if (($item['badge'] ?? '') === 'messages') $badge = $unreadMessages;
                    ?>
                    <a href="<?= e($item['url']) ?>" class="sidebar-link <?= $currentPage === $item['id'] ? 'active' : '' ?>">
                        <i class="bi bi-<?= $item['icon'] ?>"></i>
                        <?= e($item['label']) ?>
                        <?php if ($badge > 0): ?>
                            <span class="badge bg-danger sidebar-badge"><?= $badge > 99 ? '99+' : $badge ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($isSuperAdmin): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Admin</div>
                <a href="/public/superadmin/" class="sidebar-link <?= $currentPage === 'superadmin' ? 'active' : '' ?>">
                    <i class="bi bi-shield-lock"></i>
                    Super Admin
                </a>
            </div>
            <?php endif; ?>
        </nav>
        
        <div class="user-menu">
            <div class="dropdown">
                <button class="user-menu-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <img src="<?= e($avatarUrl) ?>" alt="" class="user-avatar">
                    <span class="text-truncate" style="max-width: 140px;"><?= e($user['name'] ?? 'User') ?></span>
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
            <div class="d-flex align-items-center gap-2">
                <a href="/public/notifications.php" class="btn btn-light btn-sm position-relative">
                    <i class="bi bi-bell"></i>
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                            <?= $unreadNotifications > 9 ? '9+' : $unreadNotifications ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="/public/messages.php" class="btn btn-light btn-sm position-relative">
                    <i class="bi bi-envelope"></i>
                    <?php if ($unreadMessages > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                            <?= $unreadMessages > 9 ? '9+' : $unreadMessages ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </header>
        
        <div class="content-area">
<?php
}

function member_shell_end() {
?>
        </div>
    </main>
</div>
<?php
    include __DIR__ . '/footer.php';
}
