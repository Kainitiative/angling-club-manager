<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout/member_shell.php';

require_login();

$userId = current_user_id();
$slug = $_GET['slug'] ?? '';

if (!$slug) {
  header('Location: /public/dashboard.php');
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE slug = ?");
$stmt->execute([$slug]);
$club = $stmt->fetch();

if (!$club) {
  http_response_code(404);
  exit('Club not found');
}

$clubId = (int)$club['id'];

// Check if user is a member of this club
$stmt = $pdo->prepare("SELECT * FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
$stmt->execute([$clubId, $userId]);
$membership = $stmt->fetch();

if (!$membership) {
  header('Location: /public/club.php?slug=' . $slug);
  exit;
}

// Get user's roles for this club
$userPerms = get_user_permissions($pdo, $userId, $clubId);
$userRoles = $userPerms['roles'] ?? [];

// Get all active members
$stmt = $pdo->prepare("
  SELECT cm.*, u.id as user_id, u.name, u.profile_picture_url, u.town, u.city, u.country,
         u.is_junior, ca.admin_role as is_admin, cm.committee_role
  FROM club_members cm
  JOIN users u ON cm.user_id = u.id
  LEFT JOIN club_admins ca ON cm.club_id = ca.club_id AND cm.user_id = ca.user_id
  WHERE cm.club_id = ? AND cm.membership_status = 'active'
  ORDER BY 
    ca.admin_role IS NOT NULL DESC,
    CASE cm.committee_role
      WHEN 'chairperson' THEN 1
      WHEN 'secretary' THEN 2
      WHEN 'treasurer' THEN 3
      WHEN 'pro' THEN 4
      WHEN 'safety_officer' THEN 5
      WHEN 'child_liaison_officer' THEN 6
      ELSE 10
    END,
    u.name ASC
");
$stmt->execute([$clubId]);
$members = $stmt->fetchAll();

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

$pageTitle = 'Club Members';
$currentPage = 'members';

member_shell_start([
  'title' => $pageTitle . ' - ' . $club['name'],
  'page' => $currentPage,
  'club' => $club
]);
?>

<style>
  .member-card {
    transition: all 0.2s;
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  .member-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transform: translateY(-2px);
  }
  .member-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  .member-avatar-lg {
    width: 80px;
    height: 80px;
  }
  .committee-badge {
    font-size: 0.7rem;
    padding: 4px 10px;
    border-radius: 12px;
  }
  .member-name {
    font-weight: 600;
    color: #2c3e50;
  }
  .member-location {
    font-size: 0.85rem;
    color: #7f8c8d;
  }
  .committee-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
  }
  .committee-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .btn-message {
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 8px;
  }
</style>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1">Club Members</h1>
      <p class="text-muted mb-0"><?= e($club['name']) ?> &bull; <?= count($members) ?> members</p>
    </div>
    <a href="/public/club.php?slug=<?= e($slug) ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-arrow-left me-1"></i> Back to Club
    </a>
  </div>

  <?php
  // Separate committee members from regular members
  $committeeMembers = [];
  $regularMembers = [];
  
  foreach ($members as $member) {
    $isAdmin = !empty($member['is_admin']);
    $role = $member['committee_role'] ?? 'member';
    
    if ($isAdmin || ($role && $role !== 'member')) {
      $committeeMembers[] = $member;
    } else {
      $regularMembers[] = $member;
    }
  }
  ?>

  <?php if (!empty($committeeMembers)): ?>
    <div class="committee-section">
      <div class="committee-title">
        <i class="bi bi-people-fill text-primary"></i>
        Committee Members
      </div>
      <div class="row g-3">
        <?php foreach ($committeeMembers as $member): ?>
          <?php
            $avatar = $member['profile_picture_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($member['name']) . '&size=80&background=6c757d&color=fff';
            $location = array_filter([$member['town'], $member['city']]);
            $isAdmin = !empty($member['is_admin']);
            $displayRole = $isAdmin ? $member['is_admin'] : ($member['committee_role'] ?? 'member');
            $roleBadgeColor = $roleBadgeColors[$displayRole] ?? 'bg-secondary';
            $isSelf = ($member['user_id'] == $userId);
          ?>
          <div class="col-md-6 col-lg-4">
            <div class="card member-card h-100">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <img src="<?= e($avatar) ?>" alt="" class="member-avatar member-avatar-lg me-3">
                  <div class="flex-grow-1">
                    <div class="member-name mb-1">
                      <?= e($member['name']) ?>
                      <?php if ($isSelf): ?>
                        <span class="badge bg-secondary" style="font-size: 0.65rem;">You</span>
                      <?php endif; ?>
                    </div>
                    <span class="badge committee-badge <?= $roleBadgeColor ?>"><?= e(get_role_display_name($displayRole)) ?></span>
                    <?php if ($location): ?>
                      <div class="member-location mt-1">
                        <i class="bi bi-geo-alt me-1"></i><?= e(implode(', ', $location)) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <?php if (!$isSelf): ?>
                    <a href="/public/messages.php?to=<?= $member['user_id'] ?>" class="btn btn-outline-primary btn-message">
                      <i class="bi bi-chat-dots"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($regularMembers)): ?>
    <h5 class="mb-3">
      <i class="bi bi-person me-2 text-muted"></i>
      Members (<?= count($regularMembers) ?>)
    </h5>
    <div class="row g-3">
      <?php foreach ($regularMembers as $member): ?>
        <?php
          $avatar = $member['profile_picture_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($member['name']) . '&size=64&background=6c757d&color=fff';
          $location = array_filter([$member['town'], $member['city']]);
          $isSelf = ($member['user_id'] == $userId);
        ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
          <div class="card member-card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <img src="<?= e($avatar) ?>" alt="" class="member-avatar me-3">
                <div class="flex-grow-1">
                  <div class="member-name">
                    <?= e($member['name']) ?>
                    <?php if ($isSelf): ?>
                      <span class="badge bg-secondary" style="font-size: 0.65rem;">You</span>
                    <?php endif; ?>
                    <?php if ($member['is_junior']): ?>
                      <span class="badge bg-info" style="font-size: 0.65rem;">Junior</span>
                    <?php endif; ?>
                  </div>
                  <?php if ($location): ?>
                    <div class="member-location">
                      <i class="bi bi-geo-alt me-1"></i><?= e(implode(', ', $location)) ?>
                    </div>
                  <?php endif; ?>
                </div>
                <?php if (!$isSelf): ?>
                  <a href="/public/messages.php?to=<?= $member['user_id'] ?>" class="btn btn-outline-primary btn-message" title="Message">
                    <i class="bi bi-chat-dots"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (empty($members)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <h5 class="text-muted">No members yet</h5>
        <p class="text-muted">Be the first to join this club!</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php
member_shell_end();
