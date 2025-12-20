<?php
/**
 * Club Admin Shell - Layout for club administration pages
 * 
 * Usage:
 * $pageTitle = 'Members';
 * $currentPage = 'members';
 * $clubId = $club['id'];
 * include 'app/layout/club_admin_shell.php';
 * club_admin_shell_start($pdo, $club);
 * // ... your content here ...
 * club_admin_shell_end();
 */

require_once __DIR__ . '/../nav_config.php';

function club_admin_shell_start($pdo, $club, $options = []) {
    global $pageTitle, $currentPage;
    
    $userId = current_user_id();
    $user = current_user($pdo);
    
    $clubId = $club['id'];
    $clubSlug = $club['slug'];
    $clubName = $club['name'];
    
    $pageTitle = $options['title'] ?? $pageTitle ?? 'Club Admin';
    $currentPage = $options['page'] ?? $currentPage ?? '';
    $section = $options['section'] ?? $pageTitle;
    
    // Get user's other admin clubs for club selector
    $adminClubs = [];
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.slug 
            FROM clubs c 
            JOIN club_admins ca ON c.id = ca.club_id 
            WHERE ca.user_id = ? 
            ORDER BY c.name
        ");
        $stmt->execute([$userId]);
        $adminClubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    
    $nav = get_club_admin_nav($clubId, $clubSlug);
    $breadcrumbs = get_breadcrumbs('club_admin', [
        'club_name' => $clubName,
        'club_slug' => $clubSlug,
        'section' => $section
    ]);
    
    $avatarUrl = !empty($user['avatar_url']) ? $user['avatar_url'] : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['email'] ?? ''))) . '?d=mp&s=80';
    
    // Determine active section based on current page
    $activeSection = '';
    foreach ($nav as $sectionKey => $sectionData) {
        foreach ($sectionData['items'] as $item) {
            if ($item['id'] === $currentPage) {
                $activeSection = $sectionKey;
                break 2;
            }
        }
    }
    
    include __DIR__ . '/header.php';
?>
<div class="layout-sidebar">
    <div class="sidebar-overlay"></div>
    
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="/public/dashboard.php" class="sidebar-brand">
                <i class="bi bi-water"></i>
                Club Admin
            </a>
        </div>
        
        <?php if (count($adminClubs) > 1): ?>
        <div class="club-selector">
            <div class="dropdown">
                <button class="club-selector-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <span class="text-truncate"><?= e($clubName) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark w-100">
                    <?php foreach ($adminClubs as $ac): ?>
                        <li>
                            <a class="dropdown-item <?= $ac['id'] == $clubId ? 'active' : '' ?>" 
                               href="?club_id=<?= $ac['id'] ?>">
                                <?= e($ac['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php else: ?>
        <div class="px-3 py-2 border-bottom border-secondary">
            <small class="text-muted">Managing</small>
            <div class="text-white fw-medium text-truncate"><?= e($clubName) ?></div>
        </div>
        <?php endif; ?>
        
        <nav class="sidebar-nav">
            <?php foreach ($nav as $sectionKey => $sectionData): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title"><?= e($sectionData['label']) ?></div>
                <?php foreach ($sectionData['items'] as $item): ?>
                    <a href="<?= e($item['url']) ?>" 
                       class="sidebar-link <?= $currentPage === $item['id'] ? 'active' : '' ?>"
                       <?= !empty($item['external']) ? 'target="_blank"' : '' ?>>
                        <i class="bi bi-<?= $item['icon'] ?>"></i>
                        <?= e($item['label']) ?>
                        <?php if (!empty($item['external'])): ?>
                            <i class="bi bi-box-arrow-up-right ms-auto" style="font-size: 0.75rem;"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Navigation</div>
                <a href="/public/dashboard.php" class="sidebar-link">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
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
                <a href="/public/club.php?slug=<?= e($clubSlug) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                    <i class="bi bi-eye me-1"></i> View Club
                </a>
            </div>
        </header>
        
        <div class="content-area">
<?php
}

function club_admin_shell_end() {
?>
        </div>
    </main>
</div>
<?php
    include __DIR__ . '/footer.php';
}
