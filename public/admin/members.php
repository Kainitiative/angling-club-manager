<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/layout/club_admin_shell.php';

require_login();

$userId = current_user_id();
$clubId = (int)($_GET['club_id'] ?? 0);
$message = '';
$messageType = '';

if (!$clubId) {
  http_response_code(400);
  exit('Club ID required');
}

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
$stmt->execute([$clubId]);
$club = $stmt->fetch();

if (!$club) {
  http_response_code(404);
  exit('Club not found');
}

// Get user's permissions for this club
$userPerms = get_user_permissions($pdo, $userId, $clubId);
$userRoles = $userPerms['roles'] ?? [];

// Must be at least a member with view permission, or a committee member
if (!can_view($pdo, $userId, $clubId, 'members')) {
  http_response_code(403);
  exit('You do not have permission to view members');
}

// Check specific permissions for actions
$canAccept = has_permission($pdo, $userId, $clubId, 'members', 'accept');
$canReject = has_permission($pdo, $userId, $clubId, 'members', 'reject');
$canSuspend = has_permission($pdo, $userId, $clubId, 'members', 'suspend');
$canRemove = has_permission($pdo, $userId, $clubId, 'members', 'remove');
$canSetRole = has_permission($pdo, $userId, $clubId, 'members', 'set_role');

$committeeRoles = get_committee_roles();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['member_id'])) {
  $memberId = (int)$_POST['member_id'];
  $action = $_POST['action'];
  
  $stmt = $pdo->prepare("SELECT cm.*, u.name, u.email FROM club_members cm JOIN users u ON cm.user_id = u.id WHERE cm.id = ? AND cm.club_id = ?");
  $stmt->execute([$memberId, $clubId]);
  $member = $stmt->fetch();
  
  if ($member) {
    // Check if target is an admin (protected)
    $targetIsAdmin = is_club_admin($pdo, (int)$member['user_id'], $clubId);
    
    if ($action === 'approve' && $canAccept) {
      $stmt = $pdo->prepare("UPDATE club_members SET membership_status = 'active', updated_at = NOW() WHERE id = ?");
      $stmt->execute([$memberId]);
      notify_membership_approved($pdo, (int)$member['user_id'], $clubId, $club['name'], $club['slug']);
      $message = "Approved membership for {$member['name']}.";
      $messageType = 'success';
    } elseif ($action === 'reject' && $canReject) {
      notify_membership_rejected($pdo, (int)$member['user_id'], $clubId, $club['name']);
      $stmt = $pdo->prepare("DELETE FROM club_members WHERE id = ?");
      $stmt->execute([$memberId]);
      $message = "Rejected membership request from {$member['name']}.";
      $messageType = 'warning';
    } elseif ($action === 'suspend' && $canSuspend && !$targetIsAdmin) {
      $stmt = $pdo->prepare("UPDATE club_members SET membership_status = 'suspended', updated_at = NOW() WHERE id = ?");
      $stmt->execute([$memberId]);
      $message = "Suspended membership for {$member['name']}.";
      $messageType = 'warning';
    } elseif ($action === 'activate' && $canSuspend && !$targetIsAdmin) {
      $stmt = $pdo->prepare("UPDATE club_members SET membership_status = 'active', updated_at = NOW() WHERE id = ?");
      $stmt->execute([$memberId]);
      $message = "Reactivated membership for {$member['name']}.";
      $messageType = 'success';
    } elseif ($action === 'remove' && $canRemove && !$targetIsAdmin) {
      $stmt = $pdo->prepare("DELETE FROM club_members WHERE id = ?");
      $stmt->execute([$memberId]);
      $message = "Removed {$member['name']} from the club.";
      $messageType = 'info';
    } elseif ($action === 'set_role' && $canSetRole) {
      $newRole = $_POST['committee_role'] ?? 'member';
      // Owners/admins can set their own committee role, but not admin roles via this form
      if (array_key_exists($newRole, $committeeRoles) && !is_admin_role($newRole)) {
        $stmt = $pdo->prepare("UPDATE club_members SET committee_role = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newRole, $memberId]);
        $message = "Updated role for {$member['name']} to " . get_role_display_name($newRole) . ".";
        $messageType = 'success';
      }
    }
  }
}

$stmt = $pdo->prepare("
  SELECT cm.*, u.id as user_id, u.name, u.email, u.profile_picture_url, u.phone, u.town, u.city, u.country,
         u.is_junior, p.name as parent_name, ca.admin_role as is_admin
  FROM club_members cm
  JOIN users u ON cm.user_id = u.id
  LEFT JOIN users p ON u.parent_id = p.id
  LEFT JOIN club_admins ca ON cm.club_id = ca.club_id AND cm.user_id = ca.user_id
  WHERE cm.club_id = ?
  ORDER BY 
    ca.admin_role IS NOT NULL DESC,
    CASE cm.membership_status 
      WHEN 'pending' THEN 1 
      WHEN 'active' THEN 2 
      WHEN 'suspended' THEN 3 
      WHEN 'expired' THEN 4 
    END,
    COALESCE(u.is_junior, 0) DESC,
    cm.created_at DESC
");
$stmt->execute([$clubId]);
$members = $stmt->fetchAll();

$pendingCount = 0;
$activeCount = 0;
foreach ($members as $m) {
  if ($m['membership_status'] === 'pending') $pendingCount++;
  if ($m['membership_status'] === 'active') $activeCount++;
}

$roleBadgeColors = [
  'owner' => 'bg-dark',
  'admin' => 'bg-primary',
  'chairperson' => 'bg-primary',
  'secretary' => 'bg-info',
  'treasurer' => 'bg-success',
  'pro' => 'bg-warning text-dark',
  'safety_officer' => 'bg-danger',
  'child_liaison_officer' => 'bg-secondary',
  'member' => 'bg-light text-dark',
];

$currentPage = 'members';
$pageTitle = 'Members';
$customStyles = '
  .member-card {
    border-left: 4px solid #dee2e6;
    transition: all 0.2s;
    border-radius: 8px;
  }
  .member-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  .member-card.pending {
    border-left-color: #ffc107;
    background: #fffbeb;
  }
  .member-card.active {
    border-left-color: #198754;
  }
  .member-card.suspended {
    border-left-color: #dc3545;
    background: #fff5f5;
  }
  .member-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
  }
  .role-select {
    max-width: 200px;
  }
  .committee-badge {
    font-size: 0.75rem;
    padding: 4px 8px;
  }
  .view-only-notice {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 20px;
  }
';

club_admin_shell_start($pdo, $club, ['title' => $pageTitle, 'page' => $currentPage, 'section' => 'Members']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="mb-1">Members</h1>
      <p class="text-muted mb-0"><?= e($club['name']) ?></p>
    </div>
    <div>
      <?php if ($pendingCount > 0): ?>
        <span class="badge bg-warning text-dark fs-6 me-2"><?= $pendingCount ?> Pending</span>
      <?php endif; ?>
      <span class="badge bg-success fs-6"><?= $activeCount ?> Active Members</span>
    </div>
  </div>

  <?php if (!$canAccept && !$canSetRole): ?>
    <div class="view-only-notice">
      <i class="bi bi-eye me-2"></i>
      <strong>View Only</strong> - You can view club members but cannot manage them with your current role.
    </div>
  <?php endif; ?>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($members)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <h5 class="text-muted">No membership requests yet</h5>
        <p class="text-muted">Share your club page to attract new members.</p>
        <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="btn btn-primary">View Club Page</a>
      </div>
    </div>
  <?php else: ?>
    
    <?php if ($pendingCount > 0 && ($canAccept || $canReject)): ?>
      <h5 class="mb-3">Pending Requests</h5>
      <?php foreach ($members as $member): ?>
        <?php if ($member['membership_status'] === 'pending'): ?>
          <?php
            $avatar = $member['profile_picture_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($member['name']) . '&size=60&background=6c757d&color=fff';
            $location = array_filter([$member['town'], $member['city'], $member['country']]);
          ?>
          <div class="card member-card pending mb-3">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-auto">
                  <img src="<?= e($avatar) ?>" alt="" class="member-avatar">
                </div>
                <div class="col">
                  <h5 class="mb-1">
                    <?= e($member['name']) ?>
                    <?php if ($member['is_junior']): ?>
                      <span class="badge bg-info">Junior</span>
                    <?php endif; ?>
                  </h5>
                  <?php if ($member['is_junior'] && $member['parent_name']): ?>
                    <div class="text-primary small fw-bold mb-1">
                      Parent/Guardian: <?= e($member['parent_name']) ?>
                    </div>
                  <?php endif; ?>
                  <div class="text-muted small">
                    <?= e($member['email']) ?>
                    <?php if ($member['phone']): ?>
                      &bull; <?= e($member['phone']) ?>
                    <?php endif; ?>
                  </div>
                  <?php if ($location): ?>
                    <div class="text-muted small"><?= e(implode(', ', $location)) ?></div>
                  <?php endif; ?>
                  <div class="text-muted small mt-1">
                    Requested: <?= date('M j, Y', strtotime($member['created_at'])) ?>
                  </div>
                </div>
                <div class="col-auto">
                  <?php if ($canAccept): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                      <input type="hidden" name="action" value="approve">
                      <button type="submit" class="btn btn-success">Approve</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($canReject): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                      <input type="hidden" name="action" value="reject">
                      <button type="submit" class="btn btn-outline-danger">Reject</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php elseif ($pendingCount > 0): ?>
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        There are <?= $pendingCount ?> pending membership requests. Contact a club admin to process them.
      </div>
    <?php endif; ?>

    <?php 
    $otherMembers = array_filter($members, fn($m) => $m['membership_status'] !== 'pending');
    if (!empty($otherMembers)): 
    ?>
      <h5 class="mb-3 mt-4">All Members</h5>
      <?php foreach ($otherMembers as $member): ?>
        <?php
          $avatar = $member['profile_picture_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($member['name']) . '&size=60&background=6c757d&color=fff';
          $location = array_filter([$member['town'], $member['city'], $member['country']]);
          $statusClass = $member['membership_status'];
          $isTargetAdmin = !empty($member['is_admin']);
          $currentRole = $member['committee_role'] ?? 'member';
          $displayRole = $currentRole;
          
          // Show admin role badge if they are an admin
          if ($isTargetAdmin) {
            $displayRole = $member['is_admin'];
          }
          
          $roleBadgeColor = $roleBadgeColors[$displayRole] ?? 'bg-light text-dark';
          
          // Check if this is the current user (for self-role assignment)
          $isSelf = ($member['user_id'] == $userId);
        ?>
        <div class="card member-card <?= $statusClass ?> mb-3">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-auto">
                <img src="<?= e($avatar) ?>" alt="" class="member-avatar">
              </div>
              <div class="col">
                <h5 class="mb-1">
                  <?= e($member['name']) ?>
                  <?php if ($isSelf): ?>
                    <span class="badge bg-secondary">You</span>
                  <?php endif; ?>
                  <?php if ($member['is_junior']): ?>
                    <span class="badge bg-info">Junior</span>
                  <?php endif; ?>
                  <?php if ($member['membership_status'] === 'active'): ?>
                    <span class="badge bg-success">Active</span>
                  <?php elseif ($member['membership_status'] === 'suspended'): ?>
                    <span class="badge bg-danger">Suspended</span>
                  <?php elseif ($member['membership_status'] === 'expired'): ?>
                    <span class="badge bg-secondary">Expired</span>
                  <?php endif; ?>
                  <?php if ($isTargetAdmin): ?>
                    <span class="badge committee-badge <?= $roleBadgeColor ?>"><?= e(get_role_display_name($displayRole)) ?></span>
                  <?php endif; ?>
                  <?php if ($currentRole !== 'member' && !$isTargetAdmin): ?>
                    <span class="badge committee-badge <?= $roleBadgeColors[$currentRole] ?? 'bg-light text-dark' ?>"><?= e(get_role_display_name($currentRole)) ?></span>
                  <?php endif; ?>
                </h5>
                <?php if ($member['is_junior'] && $member['parent_name']): ?>
                  <div class="text-primary small fw-bold mb-1">
                    Parent/Guardian: <?= e($member['parent_name']) ?>
                  </div>
                <?php endif; ?>
                <div class="text-muted small">
                  <?= e($member['email']) ?>
                  <?php if ($member['phone']): ?>
                    &bull; <?= e($member['phone']) ?>
                  <?php endif; ?>
                </div>
                <?php if ($location): ?>
                  <div class="text-muted small"><?= e(implode(', ', $location)) ?></div>
                <?php endif; ?>
                <div class="text-muted small mt-1">
                  Member since: <?= date('M j, Y', strtotime($member['joined_at'])) ?>
                </div>
              </div>
              <div class="col-auto">
                <?php 
                // Show role selector if:
                // 1. User has set_role permission AND target is not an admin (can set other's roles)
                // 2. OR user is an admin AND this is themselves (admin can assign themselves a committee role)
                $showRoleSelector = ($member['membership_status'] === 'active') && (
                  ($canSetRole && !$isTargetAdmin) || 
                  ($isSelf && $userRoles['is_admin'])
                );
                ?>
                <?php if ($showRoleSelector): ?>
                  <form method="post" class="d-flex align-items-center gap-2 mb-2">
                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                    <input type="hidden" name="action" value="set_role">
                    <select name="committee_role" class="form-select form-select-sm role-select">
                      <?php foreach ($committeeRoles as $roleKey => $roleLabel): ?>
                        <option value="<?= $roleKey ?>" <?= $currentRole === $roleKey ? 'selected' : '' ?>><?= $roleLabel ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Set Role</button>
                  </form>
                <?php endif; ?>
                
                <?php if (!$isTargetAdmin && !$isSelf && ($canSuspend || $canRemove)): ?>
                <div class="d-flex gap-1">
                  <?php if ($member['membership_status'] === 'active' && $canSuspend): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                      <input type="hidden" name="action" value="suspend">
                      <button type="submit" class="btn btn-outline-warning btn-sm">Suspend</button>
                    </form>
                  <?php elseif ($member['membership_status'] === 'suspended' && $canSuspend): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                      <input type="hidden" name="action" value="activate">
                      <button type="submit" class="btn btn-success btn-sm">Reactivate</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($canRemove): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Remove this member from the club?');">
                      <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                      <input type="hidden" name="action" value="remove">
                      <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                    </form>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    
  <?php endif; ?>

<?php
club_admin_shell_end();
