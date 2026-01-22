<?php
declare(strict_types=1);

/**
 * Role-Based Permission System for Angling Ireland
 * 
 * Permissions are organized by feature area:
 * - members: Member management (accept/reject, suspend, remove)
 * - meetings: Meeting scheduling and management
 * - news: Club news/announcements
 * - profile: Club profile editing
 * - policies: Club policies management
 * - finances: Financial tracking and reports
 * - competitions: Competition management
 * - catches: Catch log management
 * - documents: Document management
 * - governance: Governance hub access
 */

// Define role hierarchy and permissions
const ROLE_PERMISSIONS = [
    'owner' => [
        'members' => ['view', 'accept', 'reject', 'suspend', 'remove', 'set_role'],
        'meetings' => ['view', 'create', 'edit', 'delete'],
        'news' => ['view', 'create', 'edit', 'delete'],
        'profile' => ['view', 'edit'],
        'policies' => ['view', 'edit'],
        'finances' => ['view', 'create', 'edit', 'delete'],
        'competitions' => ['view', 'create', 'edit', 'delete'],
        'catches' => ['view', 'create', 'edit', 'delete', 'award'],
        'documents' => ['view', 'create', 'edit', 'delete'],
        'governance' => ['view', 'edit'],
        'sponsors' => ['view', 'create', 'edit', 'delete'],
        'seasons' => ['view', 'create', 'edit', 'delete'],
    ],
    'admin' => [
        'members' => ['view', 'accept', 'reject', 'suspend', 'remove', 'set_role'],
        'meetings' => ['view', 'create', 'edit', 'delete'],
        'news' => ['view', 'create', 'edit', 'delete'],
        'profile' => ['view', 'edit'],
        'policies' => ['view', 'edit'],
        'finances' => ['view', 'create', 'edit', 'delete'],
        'competitions' => ['view', 'create', 'edit', 'delete'],
        'catches' => ['view', 'create', 'edit', 'delete', 'award'],
        'documents' => ['view', 'create', 'edit', 'delete'],
        'governance' => ['view', 'edit'],
        'sponsors' => ['view', 'create', 'edit', 'delete'],
        'seasons' => ['view', 'create', 'edit', 'delete'],
    ],
    'chairperson' => [
        'members' => ['view', 'accept', 'reject', 'suspend', 'remove', 'set_role'],
        'meetings' => ['view', 'create', 'edit', 'delete'],
        'news' => ['view', 'create', 'edit', 'delete'],
        'profile' => ['view', 'edit'],
        'policies' => ['view', 'edit'],
        'finances' => ['view', 'create', 'edit', 'delete'],
        'competitions' => ['view', 'create', 'edit', 'delete'],
        'catches' => ['view', 'create', 'edit', 'delete', 'award'],
        'documents' => ['view', 'create', 'edit', 'delete'],
        'governance' => ['view', 'edit'],
        'sponsors' => ['view', 'create', 'edit', 'delete'],
        'seasons' => ['view', 'create', 'edit', 'delete'],
    ],
    'secretary' => [
        'members' => ['view', 'accept', 'reject'],
        'meetings' => ['view', 'create', 'edit', 'delete'],
        'news' => ['view', 'create', 'edit', 'delete'],
        'profile' => ['view', 'edit'],
        'policies' => ['view', 'edit'],
        'finances' => ['view'],
        'competitions' => ['view'],
        'catches' => ['view'],
        'documents' => ['view', 'create', 'edit', 'delete'],
        'governance' => ['view'],
        'sponsors' => ['view'],
        'seasons' => ['view'],
    ],
    'treasurer' => [
        'members' => ['view'],
        'meetings' => ['view'],
        'news' => ['view'],
        'profile' => ['view'],
        'policies' => ['view'],
        'finances' => ['view', 'create', 'edit', 'delete'],
        'competitions' => ['view'],
        'catches' => ['view'],
        'documents' => ['view'],
        'governance' => ['view'],
        'sponsors' => ['view'],
        'seasons' => ['view'],
    ],
    'pro' => [
        'members' => ['view'],
        'meetings' => ['view'],
        'news' => ['view', 'create', 'edit', 'delete'],
        'profile' => ['view', 'edit'],
        'policies' => ['view', 'edit'],
        'finances' => ['view'],
        'competitions' => ['view'],
        'catches' => ['view'],
        'documents' => ['view'],
        'governance' => ['view'],
        'sponsors' => ['view'],
        'seasons' => ['view'],
    ],
    'safety_officer' => [
        'members' => ['view'],
        'meetings' => ['view'],
        'news' => ['view'],
        'profile' => ['view'],
        'policies' => ['view', 'edit'],
        'finances' => ['view'],
        'competitions' => ['view'],
        'catches' => ['view'],
        'documents' => ['view'],
        'governance' => ['view'],
        'sponsors' => ['view'],
        'seasons' => ['view'],
    ],
    'child_liaison_officer' => [
        'members' => ['view'],
        'meetings' => ['view'],
        'news' => ['view'],
        'profile' => ['view'],
        'policies' => ['view'],
        'finances' => ['view'],
        'competitions' => ['view'],
        'catches' => ['view'],
        'documents' => ['view'],
        'governance' => ['view'],
        'sponsors' => ['view'],
        'seasons' => ['view'],
    ],
    'member' => [
        'members' => ['view'],
        'meetings' => [],
        'news' => [],
        'profile' => [],
        'policies' => [],
        'finances' => [],
        'competitions' => [],
        'catches' => [],
        'documents' => [],
        'governance' => [],
        'sponsors' => [],
        'seasons' => [],
    ],
];

/**
 * Get user's role in a specific club
 * Returns both admin role (from club_admins) and committee role (from club_members)
 */
function get_user_club_roles(PDO $pdo, int $userId, int $clubId): array {
    $roles = [
        'is_admin' => false,
        'admin_role' => null,
        'committee_role' => null,
        'effective_role' => 'member',
        'is_member' => false,
    ];
    
    // Check club_admins table
    $stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
    $stmt->execute([$clubId, $userId]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        $roles['is_admin'] = true;
        $roles['admin_role'] = $admin['admin_role'];
        $roles['effective_role'] = $admin['admin_role']; // owner or admin
    }
    
    // Check club_members table
    $stmt = $pdo->prepare("SELECT committee_role, membership_status FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
    $stmt->execute([$clubId, $userId]);
    $member = $stmt->fetch();
    
    if ($member) {
        $roles['is_member'] = true;
        $roles['committee_role'] = $member['committee_role'];
        
        // Committee role takes precedence for permissions if not an admin
        // But if admin, they keep admin powers plus any committee role
        if (!$roles['is_admin'] && $member['committee_role']) {
            $roles['effective_role'] = $member['committee_role'];
        }
    }
    
    return $roles;
}

/**
 * Check if user has a specific permission for a feature in a club
 */
function has_permission(PDO $pdo, int $userId, int $clubId, string $feature, string $action): bool {
    $roles = get_user_club_roles($pdo, $userId, $clubId);
    
    if (!$roles['is_member'] && !$roles['is_admin']) {
        return false;
    }
    
    $effectiveRole = $roles['effective_role'];
    
    // Check if the role has the permission
    if (!isset(ROLE_PERMISSIONS[$effectiveRole])) {
        return false;
    }
    
    if (!isset(ROLE_PERMISSIONS[$effectiveRole][$feature])) {
        return false;
    }
    
    return in_array($action, ROLE_PERMISSIONS[$effectiveRole][$feature]);
}

/**
 * Check if user can view a feature (shorthand for view permission)
 */
function can_view(PDO $pdo, int $userId, int $clubId, string $feature): bool {
    return has_permission($pdo, $userId, $clubId, $feature, 'view');
}

/**
 * Check if user can edit a feature (shorthand for edit permission)
 */
function can_edit(PDO $pdo, int $userId, int $clubId, string $feature): bool {
    return has_permission($pdo, $userId, $clubId, $feature, 'edit');
}

/**
 * Check if user can create in a feature (shorthand for create permission)
 */
function can_create(PDO $pdo, int $userId, int $clubId, string $feature): bool {
    return has_permission($pdo, $userId, $clubId, $feature, 'create');
}

/**
 * Check if user can delete in a feature (shorthand for delete permission)
 */
function can_delete(PDO $pdo, int $userId, int $clubId, string $feature): bool {
    return has_permission($pdo, $userId, $clubId, $feature, 'delete');
}

/**
 * Check if user is a club admin (owner or admin role)
 */
function is_club_admin(PDO $pdo, int $userId, int $clubId): bool {
    $roles = get_user_club_roles($pdo, $userId, $clubId);
    return $roles['is_admin'];
}

/**
 * Check if user is club owner
 */
function is_club_owner(PDO $pdo, int $userId, int $clubId): bool {
    $roles = get_user_club_roles($pdo, $userId, $clubId);
    return $roles['admin_role'] === 'owner';
}

/**
 * Check if user has any committee role (including admin roles)
 */
function has_committee_role(PDO $pdo, int $userId, int $clubId): bool {
    $roles = get_user_club_roles($pdo, $userId, $clubId);
    return $roles['is_admin'] || ($roles['committee_role'] && $roles['committee_role'] !== 'member');
}

/**
 * Get all permissions for a user in a club (useful for UI rendering)
 */
function get_user_permissions(PDO $pdo, int $userId, int $clubId): array {
    $roles = get_user_club_roles($pdo, $userId, $clubId);
    
    if (!$roles['is_member'] && !$roles['is_admin']) {
        return [];
    }
    
    $effectiveRole = $roles['effective_role'];
    
    if (!isset(ROLE_PERMISSIONS[$effectiveRole])) {
        return [];
    }
    
    return [
        'role' => $effectiveRole,
        'roles' => $roles,
        'permissions' => ROLE_PERMISSIONS[$effectiveRole],
    ];
}

/**
 * Get role display name
 */
function get_role_display_name(string $role): string {
    $names = [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'chairperson' => 'Chairperson',
        'secretary' => 'Secretary',
        'treasurer' => 'Treasurer',
        'pro' => 'PRO',
        'safety_officer' => 'Safety Officer',
        'child_liaison_officer' => 'Child Liaison Officer',
        'member' => 'Member',
    ];
    
    return $names[$role] ?? ucfirst(str_replace('_', ' ', $role));
}

/**
 * Get all available committee roles (excluding admin roles)
 */
function get_committee_roles(): array {
    return [
        'chairperson' => 'Chairperson',
        'secretary' => 'Secretary',
        'treasurer' => 'Treasurer',
        'pro' => 'PRO',
        'safety_officer' => 'Safety Officer',
        'child_liaison_officer' => 'Child Liaison Officer',
        'member' => 'Member',
    ];
}

/**
 * Check if a role is an admin role
 */
function is_admin_role(string $role): bool {
    return in_array($role, ['owner', 'admin']);
}
