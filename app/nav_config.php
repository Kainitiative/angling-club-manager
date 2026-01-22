<?php
/**
 * Navigation Configuration
 * Central place to define all menus and navigation structure
 */

function get_member_nav($pdo, $userId) {
    $hasOwnClub = false;
    $isClubMember = false;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM club_admins WHERE user_id = ? AND admin_role = 'owner'");
        $stmt->execute([$userId]);
        $hasOwnClub = (int)$stmt->fetchColumn() > 0;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        $isClubMember = (int)$stmt->fetchColumn() > 0;
    } catch (Exception $e) {}
    
    $mainNav = [
        ['id' => 'home', 'label' => 'Dashboard', 'url' => '/public/dashboard.php', 'icon' => 'house'],
        ['id' => 'clubs', 'label' => 'Browse Clubs', 'url' => '/public/clubs.php', 'icon' => 'people'],
        ['id' => 'competitions', 'label' => 'Competitions', 'url' => '/public/competitions.php', 'icon' => 'trophy'],
    ];
    
    if (!$hasOwnClub) {
        $mainNav[] = ['id' => 'create_club', 'label' => 'Create a Club', 'url' => '/public/create_club.php', 'icon' => 'plus-circle'];
    }
    
    $personalNav = [
        ['id' => 'messages', 'label' => 'Messages', 'url' => '/public/messages.php', 'icon' => 'envelope', 'badge' => 'messages'],
        ['id' => 'notifications', 'label' => 'Notifications', 'url' => '/public/notifications.php', 'icon' => 'bell', 'badge' => 'notifications'],
    ];
    
    if ($isClubMember || $hasOwnClub) {
        $personalNav[] = ['id' => 'juniors', 'label' => 'Junior Members', 'url' => '/public/juniors.php', 'icon' => 'people'];
        $personalNav[] = ['id' => 'tasks', 'label' => 'My Tasks', 'url' => '/public/tasks.php', 'icon' => 'check2-square'];
    }
    
    $nav = [
        'main' => $mainNav,
        'personal' => $personalNav,
        'account' => [
            ['id' => 'profile', 'label' => 'Edit Profile', 'url' => '/public/profile.php', 'icon' => 'person-gear'],
            ['id' => 'logout', 'label' => 'Logout', 'url' => '/public/auth/logout.php', 'icon' => 'box-arrow-right'],
        ],
    ];
    
    return $nav;
}

function get_club_admin_nav($clubId, $clubSlug, $userRole = 'admin') {
    $nav = [
        'overview' => [
            'label' => 'Overview',
            'items' => [
                ['id' => 'club_page', 'label' => 'View Club Page', 'url' => "/public/club.php?slug=$clubSlug", 'icon' => 'eye', 'external' => true],
            ],
        ],
        'people' => [
            'label' => 'People',
            'items' => [
                ['id' => 'members', 'label' => 'Members', 'url' => "/public/admin/members.php?club_id=$clubId", 'icon' => 'people'],
            ],
        ],
        'engagement' => [
            'label' => 'Engagement',
            'items' => [
                ['id' => 'news', 'label' => 'News', 'url' => "/public/admin/news.php?club_id=$clubId", 'icon' => 'newspaper'],
                ['id' => 'catches', 'label' => 'Catch Log', 'url' => "/public/catches.php?slug=$clubSlug", 'icon' => 'water'],
                ['id' => 'competitions', 'label' => 'Competitions', 'url' => "/public/admin/competitions.php?club_id=$clubId", 'icon' => 'trophy'],
                ['id' => 'seasons', 'label' => 'Seasons', 'url' => "/public/admin/seasons.php?club_id=$clubId", 'icon' => 'calendar-range'],
            ],
        ],
        'governance' => [
            'label' => 'Governance',
            'items' => [
                ['id' => 'governance_hub', 'label' => 'Best Practices', 'url' => "/public/admin/governance.php?club_id=$clubId", 'icon' => 'shield-check'],
                ['id' => 'committee', 'label' => 'Committee Guide', 'url' => "/public/admin/committee.php?club_id=$clubId", 'icon' => 'people'],
                ['id' => 'documents', 'label' => 'Documents', 'url' => "/public/admin/documents.php?club_id=$clubId", 'icon' => 'file-earmark-text'],
                ['id' => 'meetings', 'label' => 'Meetings', 'url' => "/public/admin/meetings.php?club_id=$clubId", 'icon' => 'calendar-event'],
                ['id' => 'policies', 'label' => 'Policies', 'url' => "/public/admin/policies.php?club_id=$clubId", 'icon' => 'file-text'],
            ],
        ],
        'finance' => [
            'label' => 'Finance',
            'items' => [
                ['id' => 'transactions', 'label' => 'Transactions', 'url' => "/public/admin/finances.php?club_id=$clubId", 'icon' => 'cash-stack'],
                ['id' => 'accounts', 'label' => 'Accounts', 'url' => "/public/admin/finances.php?club_id=$clubId&accounts=1", 'icon' => 'bank'],
                ['id' => 'reports', 'label' => 'Reports', 'url' => "/public/admin/finances.php?club_id=$clubId&report=1", 'icon' => 'graph-up'],
            ],
        ],
        'partners' => [
            'label' => 'Partners',
            'items' => [
                ['id' => 'sponsors', 'label' => 'Sponsors', 'url' => "/public/admin/sponsors.php?club_id=$clubId", 'icon' => 'building'],
            ],
        ],
        'settings' => [
            'label' => 'Settings',
            'items' => [
                ['id' => 'profile', 'label' => 'Club Profile', 'url' => "/public/admin/club_profile.php?club_id=$clubId", 'icon' => 'gear'],
            ],
        ],
    ];
    
    return $nav;
}

function get_super_admin_nav() {
    return [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'url' => '/public/superadmin/', 'icon' => 'speedometer2'],
        ['id' => 'clubs', 'label' => 'Clubs', 'url' => '/public/superadmin/clubs.php', 'icon' => 'building'],
        ['id' => 'users', 'label' => 'Users', 'url' => '/public/superadmin/users.php', 'icon' => 'people'],
        ['id' => 'subscriptions', 'label' => 'Subscriptions', 'url' => '/public/superadmin/subscriptions.php', 'icon' => 'credit-card'],
    ];
}

function get_public_nav($isLoggedIn = false) {
    if ($isLoggedIn) {
        return [
            ['label' => 'Dashboard', 'url' => '/public/dashboard.php', 'class' => 'btn-outline-light'],
            ['label' => 'Guides & Boats', 'url' => '/public/clubs.php?type=commercial', 'class' => 'btn-outline-light'],
            ['label' => 'Logout', 'url' => '/public/auth/logout.php', 'class' => 'btn-light'],
        ];
    }
    return [
        ['label' => 'Guides & Boats', 'url' => '/public/clubs.php?type=commercial', 'class' => 'btn-outline-light'],
        ['label' => 'Log In', 'url' => '/', 'class' => 'btn-outline-light', 'modal' => 'loginModal'],
        ['label' => 'Sign Up', 'url' => '/public/auth/register.php', 'class' => 'btn-primary'],
    ];
}

function render_icon($name, $size = 16) {
    return "<i class=\"bi bi-$name\" style=\"font-size: {$size}px;\"></i>";
}

function get_breadcrumbs($page, $context = []) {
    $crumbs = [['label' => 'Home', 'url' => '/public/dashboard.php']];
    
    switch ($page) {
        case 'club_admin':
            $crumbs[] = ['label' => $context['club_name'] ?? 'Club', 'url' => '/public/club.php?slug=' . ($context['club_slug'] ?? '')];
            $crumbs[] = ['label' => 'Admin', 'url' => null];
            if (!empty($context['section'])) {
                $crumbs[] = ['label' => $context['section'], 'url' => null];
            }
            break;
        case 'member':
            if (!empty($context['section'])) {
                $crumbs[] = ['label' => $context['section'], 'url' => null];
            }
            break;
        case 'super_admin':
            $crumbs = [['label' => 'Super Admin', 'url' => '/public/superadmin/']];
            if (!empty($context['section'])) {
                $crumbs[] = ['label' => $context['section'], 'url' => null];
            }
            break;
    }
    
    return $crumbs;
}

function render_breadcrumbs($crumbs) {
    if (empty($crumbs)) return '';
    
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">';
    $last = count($crumbs) - 1;
    
    foreach ($crumbs as $i => $crumb) {
        if ($i === $last || empty($crumb['url'])) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . e($crumb['label']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . e($crumb['url']) . '">' . e($crumb['label']) . '</a></li>';
        }
    }
    
    $html .= '</ol></nav>';
    return $html;
}
