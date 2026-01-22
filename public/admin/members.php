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

$stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
$stmt->execute([$clubId, $userId]);
$adminRow = $stmt->fetch();

if (!$adminRow) {
  http_response_code(403);
  exit('You are not an admin of this club');
}

$adminRole = $adminRow['admin_role'];

$committeeRoles = [
  'member' => 'Member',
  'owner' => 'Owner',
  'admin' => 'Admin',
  'chairperson' => 'Chairperson',
  'secretary' => 'Secretary',
  'treasurer' => 'Treasurer',
  'pro' => 'PRO',
  'safety_officer' => 'Safety Officer',
  'child_liaison_officer' => 'Child Liaison Officer',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['member_id'])) {
  $memberId = (int)$_POST['member_id'];
  $action = $_POST['action'];
  
  $stmt = $pdo->prepare("SELECT cm.*, u.name, u.email FROM club_members cm JOIN users u ON cm.user_id = u.id WHERE cm.id = ? AND cm.club_id = ?");
  $stmt->execute([$memberId, $clubId]);
  $member = $stmt->fetch();
  
  if ($member) {
    if ($action === 'approve') {
      $stmt = $pdo->prepare("UPDATE club_members SET membership_status = 'active', updated_at = NOW() WHERE id = ?");
      $stmt->execute([$memberId]);
      notify_membership_approved($pdo, (int)$member['user_id'], $clubId, $club['name'], $club['slug']);
      $message = "Approved membership for {$member['name']}.";
      $messageType = 'success';
    } elseif ($action === 'reject') {
      notify_membership_rejected($pdo, (int)$member['user_id'], $clubId, $club['name']);
      $stmt = $pdo->prepare("DELETE FROM club_members WHERE id = ?");
      $stmt->execute([$memberId]);
      $message = "Rejected membership request from {$member['name']}.";
      $messageType = 'warning';
    } elseif ($action === 'suspend') {
      $stmt = $pdo->prepare("UPDATE club_members SET membership_status = 'suspended', updated_at = NOW() WHERE id = ?");
      $stmt->execute([$memberId]);
      $message = "Suspended membership for {$member['name']}.";
      $messageType = 'warning';
    } elseif ($action === 'activate') {
      $stmt = $pdo->prepare("UPDATE club_members SET membership_status = 'active', updated_at = NOW() WHERE id = ?");
      $stmt->execute([$memberId]);
      $message = "Reactivated membership for {$member['name']}.";
      $messageType = 'success';
    } elseif ($action === 'remove') {
      $stmt = $pdo->prepare("DELETE FROM club_members WHERE id = ?");
      $stmt->execute([$memberId]);
      $message = "Removed {$member['name']} from the club.";
      $messageType = 'info';
    } elseif ($action === 'set_role') {
      $newRole = $_POST['committee_role'] ?? 'member';
      if (array_key_exists($newRole, $committeeRoles)) {
        $stmt = $pdo->prepare("UPDATE club_members SET committee_role = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newRole, $memberId]);
        $message = "Updated role for {$member['name']} to {$committeeRoles[$newRole]}.";
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
';

club_admin_shell_start($pdo, $club, ['title' => $pageTitle, 'page' => $currentPage, 'section' => 'Members']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="mb-1">Manage Members</h1>
      <p class="text-muted mb-0"><?= e($club['name']) ?></p>
    </div>
    <div>
      <?php if ($pendingCount > 0): ?>
        <span class="badge bg-warning text-dark fs-6 me-2"><?= $pendingCount ?> Pending</span>
      <?php endif; ?>
      <span class="badge bg-success fs-6"><?= $activeCount ?> Active Members</span>
    </div>
  </div>

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
    
    <?php if ($pendingCount > 0): ?>
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
                  <form method="post" class="d-inline">
                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success">Approve</button>
                  </form>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-outline-danger">Reject</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
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
          $isClubAdmin = !empty($member['is_admin']);
          $currentRole = $member['committee_role'] ?? 'member';
          if ($isClubAdmin) {
            $currentRole = $member['is_admin'];
          }
          $roleBadgeColor = $roleBadgeColors[$currentRole] ?? 'bg-light text-dark';
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
                  <?php if ($currentRole !== 'member'): ?>
                    <span class="badge committee-badge <?= $roleBadgeColor ?>"><?= e($committeeRoles[$currentRole]) ?></span>
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
                <?php if ($member['membership_status'] === 'active' && !$isClubAdmin): ?>
                  <form method="post" class="d-flex align-items-center gap-2 mb-2">
                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                    <input type="hidden" name="action" value="set_role">
                    <select name="committee_role" class="form-select form-select-sm role-select">
                      <?php foreach ($committeeRoles as $roleKey => $roleLabel): ?>
                        <?php if (!in_array($roleKey, ['owner', 'admin'])): ?>
                          <option value="<?= $roleKey ?>" <?= $currentRole === $roleKey ? 'selected' : '' ?>><?= $roleLabel ?></option>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Set Role</button>
                  </form>
                <?php endif; ?>
                <?php if (!$isClubAdmin): ?>
                <div class="d-flex gap-1">
                  <?php if ($member['membership_status'] === 'active'): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                      <input type="hidden" name="action" value="suspend">
                      <button type="submit" class="btn btn-outline-warning btn-sm">Suspend</button>
                    </form>
                  <?php elseif ($member['membership_status'] === 'suspended'): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                      <input type="hidden" name="action" value="activate">
                      <button type="submit" class="btn btn-success btn-sm">Reactivate</button>
                    </form>
                  <?php endif; ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('Remove this member from the club?');">
                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                    <input type="hidden" name="action" value="remove">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                  </form>
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
