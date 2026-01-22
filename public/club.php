<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout/member_shell.php';
require_once __DIR__ . '/../app/layout/public_shell.php';
require_once __DIR__ . '/../app/notifications.php';

$slug = $_GET['slug'] ?? '';
$message = '';
$messageType = '';

if (!$slug) {
  http_response_code(404);
  exit('Club not found');
}

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE slug = ?");
$stmt->execute([$slug]);
$club = $stmt->fetch();

if (!$club) {
  http_response_code(404);
  exit('Club not found');
}

$userId = current_user_id();
$isLoggedIn = (bool)$userId;
$isAdmin = false;
$isMember = false;
$membershipStatus = null;

if ($userId) {
  $stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$club['id'], $userId]);
  $adminRow = $stmt->fetch();
  $isAdmin = (bool)$adminRow;
  
  $stmt = $pdo->prepare("SELECT membership_status, committee_role FROM club_members WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$club['id'], $userId]);
  $memberRow = $stmt->fetch();
  if ($memberRow) {
    $membershipStatus = $memberRow['membership_status'];
    $isMember = ($membershipStatus === 'active');
    $userCommitteeRole = $memberRow['committee_role'] ?? 'member';
  } else {
    $userCommitteeRole = 'member';
  }
}

if (!$club['is_public'] && !$isAdmin && !$isMember) {
  if (!$userId) {
    http_response_code(403);
    exit('This club is private');
  }
  http_response_code(403);
  exit('This club is private');
}

$isInAnyClub = false;
$existingClubName = null;
if ($userId) {
  $stmt = $pdo->prepare("
    SELECT c.name FROM club_members cm 
    JOIN clubs c ON cm.club_id = c.id 
    WHERE cm.user_id = ? AND cm.membership_status IN ('active', 'pending') AND cm.club_id != ?
  ");
  $stmt->execute([$userId, $club['id']]);
  $existingClubRow = $stmt->fetch();
  if ($existingClubRow) {
    $isInAnyClub = true;
    $existingClubName = $existingClubRow['name'];
  }
  
  $stmt = $pdo->prepare("SELECT c.name FROM clubs c JOIN club_admins ca ON c.id = ca.club_id WHERE ca.user_id = ?");
  $stmt->execute([$userId]);
  $ownedClub = $stmt->fetch();
  if ($ownedClub) {
    $isInAnyClub = true;
    $existingClubName = $ownedClub['name'];
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if (!$userId) {
    redirect('/public/auth/login.php');
  }
  
  if ($_POST['action'] === 'request_join' && !$isAdmin && !$membershipStatus && !$isInAnyClub) {
    $stmt = $pdo->prepare("INSERT INTO club_members (club_id, user_id, membership_status) VALUES (?, ?, 'pending')");
    $stmt->execute([$club['id'], $userId]);
    $membershipStatus = 'pending';
    $message = 'Your membership request has been submitted.';
    $messageType = 'success';
    
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $applicantName = $stmt->fetchColumn() ?: 'A user';
    notify_membership_request($pdo, (int)$club['id'], $club['name'], $applicantName);
  }
  
  if ($_POST['action'] === 'join_junior' && !$isInAnyClub) {
    $juniorId = (int)$_POST['junior_id'];
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ? AND parent_id = ? AND is_junior = 1");
    $stmt->execute([$juniorId, $userId]);
    $juniorUser = $stmt->fetch();
    if ($juniorUser) {
        try {
            $stmt = $pdo->prepare("INSERT INTO club_members (club_id, user_id, parent_user_id, membership_status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$club['id'], $juniorId, $userId]);
            $message = 'Junior membership request submitted.';
            $messageType = 'success';
            
            $juniorName = $juniorUser['name'] ?: 'A junior member';
            notify_membership_request($pdo, (int)$club['id'], $club['name'], $juniorName . ' (Junior)');
        } catch (PDOException $e) {
            $message = 'Failed to submit junior request.';
            $messageType = 'danger';
        }
    }
  }
  
  if ($_POST['action'] === 'cancel_request' && $membershipStatus === 'pending') {
    $stmt = $pdo->prepare("DELETE FROM club_members WHERE club_id = ? AND user_id = ?");
    $stmt->execute([$club['id'], $userId]);
    $membershipStatus = null;
    $message = 'Your membership request has been cancelled.';
    $messageType = 'info';
  }
}

$fishingStyles = $club['fishing_styles'] ? json_decode($club['fishing_styles'], true) : [];

$fishingStyleLabels = [
  'coarse' => 'Coarse Fishing',
  'carp' => 'Carp Fishing',
  'match' => 'Match Fishing',
  'specimen' => 'Specimen Hunting',
  'fly' => 'Fly Fishing',
  'game' => 'Game Fishing',
  'sea' => 'Sea Fishing',
  'pike' => 'Pike Fishing',
  'predator' => 'Predator Fishing',
  'lure' => 'Lure Fishing',
];

$address = array_filter([
  $club['address_line1'] ?? null,
  $club['address_line2'] ?? null,
  $club['town'] ?? null,
  $club['county'] ?? null,
  $club['postcode'] ?? null,
  $club['country'] ?? null,
]);

$stmt = $pdo->prepare("
  SELECT * FROM competitions 
  WHERE club_id = ? AND competition_date >= CURRENT_DATE
  ORDER BY competition_date ASC
  LIMIT 10
");
$stmt->execute([$club['id']]);
$clubCompetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT c.*, 
         (SELECT COUNT(*) FROM competition_results cr WHERE cr.competition_id = c.id) as result_count
  FROM competitions c
  WHERE c.club_id = ? AND c.competition_date < CURRENT_DATE
  ORDER BY c.competition_date DESC
  LIMIT 10
");
$stmt->execute([$club['id']]);
$pastCompetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT cm.committee_role, u.name, u.profile_picture_url
  FROM club_members cm
  JOIN users u ON cm.user_id = u.id
  WHERE cm.club_id = ? AND cm.membership_status = 'active' AND cm.committee_role != 'member'
  ORDER BY 
    CASE cm.committee_role
      WHEN 'chairperson' THEN 1
      WHEN 'secretary' THEN 2
      WHEN 'treasurer' THEN 3
      WHEN 'pro' THEN 4
      WHEN 'safety_officer' THEN 5
      WHEN 'child_liaison_officer' THEN 6
    END
");
$stmt->execute([$club['id']]);
$committeeMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$committeeRoleLabels = [
  'chairperson' => 'Chairperson',
  'secretary' => 'Secretary',
  'treasurer' => 'Treasurer',
  'pro' => 'PRO',
  'safety_officer' => 'Safety Officer',
  'child_liaison_officer' => 'Child Liaison Officer',
];

$stmt = $pdo->prepare("SELECT * FROM club_profile_settings WHERE club_id = ?");
$stmt->execute([$club['id']]);
$profileSettings = $stmt->fetch();

$clubLeaderboards = [];
$isCommittee = in_array($userCommitteeRole ?? 'member', ['chairperson', 'secretary', 'treasurer', 'pro', 'safety_officer', 'child_liaison_officer']);
if ($isMember || $isAdmin) {
  $stmt = $pdo->prepare("
    SELECT cl.*, 
           (SELECT COUNT(*) FROM leaderboard_entries WHERE leaderboard_id = cl.id) as entry_count
    FROM club_leaderboards cl
    WHERE cl.club_id = ? AND cl.is_active = 1
    ORDER BY cl.display_order, cl.created_at DESC
    LIMIT 3
  ");
  $stmt->execute([$club['id']]);
  $clubLeaderboards = $stmt->fetchAll();
}

$membershipFees = [];
$clubPerks = [];
$clubGallery = [];

if (defined('DB_DRIVER') && DB_DRIVER === 'mysql') {
  try {
    $stmt = $pdo->prepare("SELECT * FROM club_membership_fees WHERE club_id = ? AND is_active = 1 ORDER BY display_order, id");
    $stmt->execute([$club['id']]);
    $membershipFees = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) { $membershipFees = []; }

  try {
    $stmt = $pdo->prepare("SELECT * FROM club_perks WHERE club_id = ? ORDER BY display_order, id");
    $stmt->execute([$club['id']]);
    $clubPerks = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) { $clubPerks = []; }

  try {
    $stmt = $pdo->prepare("SELECT * FROM club_gallery WHERE club_id = ? ORDER BY display_order, id");
    $stmt->execute([$club['id']]);
    $clubGallery = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) { $clubGallery = []; }
}

$stmt = $pdo->prepare("SELECT * FROM sponsors WHERE club_id = ? ORDER BY display_order, name");
$stmt->execute([$club['id']]);
$clubSponsors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasPinnedColumn = false;
try {
  $checkCol = $pdo->query("SELECT is_pinned FROM club_news LIMIT 1");
  $hasPinnedColumn = true;
} catch (PDOException $e) {
  $hasPinnedColumn = false;
}

$newsOrderBy = $hasPinnedColumn ? "n.is_pinned DESC, n.published_at DESC" : "n.published_at DESC";
$stmt = $pdo->prepare("
  SELECT n.*, u.name as author_name 
  FROM club_news n 
  JOIN users u ON n.author_id = u.id 
  WHERE n.club_id = ? AND n.published_at IS NOT NULL
  ORDER BY $newsOrderBy
  LIMIT 10
");
$stmt->execute([$club['id']]);
$clubNews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$canManageNews = false;
if ($isAdmin) {
  $canManageNews = true;
} elseif ($isMember && in_array($userCommitteeRole, ['chairperson', 'secretary'])) {
  $canManageNews = true;
}

function sanitizeHexColor(string $color, string $default = '#1e3a5f'): string {
  $color = trim($color);
  if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    return $color;
  }
  return $default;
}

function sanitizeUrl(?string $url): ?string {
  if (!$url) return null;
  $url = trim($url);
  if (preg_match('/^https?:\/\//i', $url) && !preg_match('/javascript:/i', $url)) {
    return filter_var($url, FILTER_SANITIZE_URL);
  }
  return null;
}

$primaryColor = sanitizeHexColor($profileSettings['primary_color'] ?? '', '#1e3a5f');
$secondaryColor = sanitizeHexColor($profileSettings['secondary_color'] ?? '', '#2d5a87');
$heroTitle = $profileSettings['hero_title'] ?? null;
$heroTagline = $profileSettings['hero_tagline'] ?? null;
$heroImage = sanitizeUrl($profileSettings['hero_image_url'] ?? '');
$whyJoinText = $profileSettings['why_join_text'] ?? null;

$billingPeriodLabels = [
  'one_time' => 'One-time',
  'monthly' => '/month',
  'quarterly' => '/quarter',
  'yearly' => '/year',
];

$pageTitle = e($club['name']);
$currentPage = 'club';
if ($isLoggedIn) {
  member_shell_start($pdo, ['title' => $pageTitle, 'page' => $currentPage, 'section' => 'Clubs']);
} else {
  public_shell_start($pageTitle);
}
?>
<style>
  :root {
    --primary-color: <?= e($primaryColor) ?>;
    --secondary-color: <?= e($secondaryColor) ?>;
  }
  .club-header {
    background: <?= $heroImage ? 'linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(' . e($heroImage) . ')' : 'linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%)' ?>;
    background-size: cover;
    background-position: center;
    color: white;
    padding: 40px 20px;
    margin: -1rem -1rem 2rem -1rem;
    border-radius: 8px;
  }
  .club-logo {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    border: 4px solid white;
    background: white;
  }
  .club-logo-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 12px;
    border: 4px solid white;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: bold;
  }
  .fishing-style-badge {
    background: #e9ecef;
    color: #495057;
    padding: 8px 16px;
    border-radius: 20px;
    display: inline-block;
    margin: 4px;
    font-size: 0.9rem;
  }
  .info-card {
    border-left: 4px solid #0d6efd;
  }
  .membership-card {
    border: 2px solid var(--primary-color);
  }
  .fee-card {
    border: 2px solid #dee2e6;
    transition: all 0.2s;
  }
  .fee-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  .fee-price {
    color: var(--primary-color);
  }
  .perk-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
  }
  .perk-item:last-child {
    border-bottom: none;
  }
  .perk-item::before {
    content: "✓";
    color: var(--primary-color);
    font-weight: bold;
    margin-right: 10px;
  }
  .gallery-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s;
  }
  .gallery-img:hover {
    transform: scale(1.02);
  }
  .social-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    margin-right: 8px;
  }
  .social-link:hover {
    background: var(--secondary-color);
    color: white;
  }
  .btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
  }
  .btn-primary:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
  }
</style>

<section class="club-header">
  <div class="row align-items-center">
    <div class="col-auto">
      <?php if ($club['logo_url']): ?>
        <img src="<?= e($club['logo_url']) ?>" alt="<?= e($club['name']) ?>" class="club-logo">
      <?php else: ?>
        <div class="club-logo-placeholder">
          <?= strtoupper(substr($club['name'], 0, 1)) ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="col">
      <h1 class="display-6 fw-bold mb-2"><?= e($club['name']) ?></h1>
      <?php if (!empty($club['tagline'])): ?>
        <p class="lead mb-2 opacity-90"><?= e($club['tagline']) ?></p>
      <?php endif; ?>
      <?php if ($club['town'] || $club['city']): ?>
        <p class="mb-0 opacity-75">
          <?= e($club['town'] ?: $club['city']) ?><?= $club['county'] ? ', ' . e($club['county']) : '' ?>
        </p>
      <?php endif; ?>
    </div>
    <div class="col-auto">
      <?php 
        $canEditProfile = $isAdmin || in_array($userCommitteeRole, ['chairperson', 'pro']);
      ?>
      <?php if ($isAdmin): ?>
        <span class="badge bg-warning text-dark fs-6 p-2">Admin</span>
        <a href="/public/admin/club_profile.php?club_id=<?= $club['id'] ?>" class="btn btn-light btn-sm ms-2">Edit Profile</a>
        <a href="/public/admin/members.php?club_id=<?= $club['id'] ?>" class="btn btn-light btn-sm ms-1">Members</a>
        <a href="/public/admin/finances.php?club_id=<?= $club['id'] ?>" class="btn btn-light btn-sm ms-1">Finances</a>
        <a href="/public/admin/news.php?club_id=<?= $club['id'] ?>" class="btn btn-light btn-sm ms-1">News</a>
      <?php elseif ($isMember): ?>
        <?php
          $committeeRolesForFinances = ['chairperson', 'secretary', 'treasurer', 'pro', 'safety_officer', 'child_liaison_officer'];
          $canViewFinances = in_array($userCommitteeRole ?? 'member', $committeeRolesForFinances);
        ?>
        <span class="badge bg-success fs-6 p-2">Member</span>
        <?php if ($canEditProfile): ?>
          <a href="/public/admin/club_profile.php?club_id=<?= $club['id'] ?>" class="btn btn-light btn-sm ms-2">Edit Profile</a>
        <?php endif; ?>
        <?php if ($canViewFinances): ?>
          <a href="/public/admin/finances.php?club_id=<?= $club['id'] ?>" class="btn btn-light btn-sm ms-1">Finances</a>
        <?php endif; ?>
        <?php if ($canManageNews): ?>
          <a href="/public/admin/news.php?club_id=<?= $club['id'] ?>" class="btn btn-light btn-sm ms-1">News</a>
        <?php endif; ?>
      <?php elseif ($membershipStatus === 'pending'): ?>
        <span class="badge bg-info fs-6 p-2">Request Pending</span>
      <?php endif; ?>

      <?php if ($userId && !$isAdmin && !$membershipStatus && !$isInAnyClub): ?>
        <form method="post" class="d-inline ms-2">
          <input type="hidden" name="action" value="request_join">
          <button type="submit" class="btn btn-primary btn-sm">Join Club</button>
        </form>
        
        <?php 
        $stmt = $pdo->prepare("
          SELECT u.id, u.name FROM users u 
          WHERE u.parent_id = ? AND u.is_junior = 1 
          AND NOT EXISTS (SELECT 1 FROM club_members cm WHERE cm.user_id = u.id AND cm.club_id = ?)
        ");
        $stmt->execute([$userId, $club['id']]);
        $juniorOptions = $stmt->fetchAll();
        ?>
        
        <?php if (!empty($juniorOptions)): ?>
          <button type="button" class="btn btn-outline-primary btn-sm ms-1" data-bs-toggle="modal" data-bs-target="#joinJuniorModal">
            Add Junior
          </button>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= e($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8">
    
    <?php if ($club['about_text']): ?>
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">About Us</h5>
        </div>
        <div class="card-body">
          <p class="mb-0"><?= nl2br(e($club['about_text'])) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($clubNews)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Club News</h5>
          <?php if ($canManageNews): ?>
            <a href="/public/admin/news.php?club_id=<?= $club['id'] ?>" class="btn btn-sm btn-outline-primary">Manage News</a>
          <?php endif; ?>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($clubNews as $news): ?>
            <div class="list-group-item">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <?php if (!empty($news['is_pinned'])): ?>
                    <span class="badge bg-warning text-dark me-1">Pinned</span>
                  <?php endif; ?>
                  <h6 class="mb-1"><?= e($news['title']) ?></h6>
                  <small class="text-muted">
                    <?= date('d M Y', strtotime($news['published_at'])) ?>
                    &bull; By <?= e($news['author_name']) ?>
                  </small>
                </div>
              </div>
              <p class="mb-0 mt-2"><?= nl2br(e($news['content'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($fishingStyles)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">Fishing Styles</h5>
        </div>
        <div class="card-body">
          <?php foreach ($fishingStyles as $style): ?>
            <span class="fishing-style-badge">
              <?= e($fishingStyleLabels[$style] ?? ucfirst($style)) ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($whyJoinText || !empty($clubPerks)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">Why Join <?= e($club['name']) ?>?</h5>
        </div>
        <div class="card-body">
          <?php if ($whyJoinText): ?>
            <p><?= nl2br(e($whyJoinText)) ?></p>
          <?php endif; ?>
          <?php if (!empty($clubPerks)): ?>
            <div class="mt-3">
              <?php foreach ($clubPerks as $perk): ?>
                <div class="perk-item"><?= e($perk['perk_text']) ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($membershipFees)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">Membership Fees</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php foreach ($membershipFees as $fee): ?>
              <div class="col-md-6">
                <div class="card fee-card h-100">
                  <div class="card-body text-center">
                    <h6 class="mb-2"><?= e($fee['fee_name']) ?></h6>
                    <div class="fs-3 fw-bold fee-price">
                      &euro;<?= number_format((float)$fee['amount'], 2) ?>
                      <small class="fs-6 text-muted fw-normal"><?= $billingPeriodLabels[$fee['billing_period']] ?? '' ?></small>
                    </div>
                    <?php if ($fee['description']): ?>
                      <p class="small text-muted mt-2 mb-0"><?= e($fee['description']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($clubGallery)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">Photo Gallery</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php foreach ($clubGallery as $photo): 
              $safeImageUrl = sanitizeUrl($photo['image_url']);
              if (!$safeImageUrl) continue;
            ?>
              <div class="col-md-4 col-6">
                <a href="<?= e($safeImageUrl) ?>" target="_blank" rel="noopener noreferrer">
                  <img src="<?= e($safeImageUrl) ?>" alt="<?= e($photo['caption'] ?? '') ?>" class="gallery-img">
                </a>
                <?php if ($photo['caption']): ?>
                  <p class="small text-muted text-center mt-1 mb-0"><?= e($photo['caption']) ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($committeeMembers)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">Club Committee</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php foreach ($committeeMembers as $cm): ?>
              <div class="col-md-4 col-6 text-center">
                <?php if ($cm['profile_picture_url']): ?>
                  <img src="<?= e($cm['profile_picture_url']) ?>" alt="" class="rounded-circle mb-2" style="width: 60px; height: 60px; object-fit: cover;">
                <?php else: ?>
                  <div class="rounded-circle bg-secondary mx-auto mb-2 d-flex align-items-center justify-content-center text-white" style="width: 60px; height: 60px;">
                    <i class="bi bi-person fs-4"></i>
                  </div>
                <?php endif; ?>
                <h6 class="mb-0"><?= e($cm['name']) ?></h6>
                <small class="text-muted"><?= e($committeeRoleLabels[$cm['committee_role']] ?? $cm['committee_role']) ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($clubCompetitions)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Upcoming Competitions</h5>
          <?php if ($isAdmin): ?>
            <a href="/public/admin/competitions.php?club_id=<?= $club['id'] ?>" class="btn btn-sm btn-outline-primary">Manage</a>
          <?php endif; ?>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($clubCompetitions as $comp): ?>
            <div class="list-group-item">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <strong><?= e($comp['title']) ?></strong><br>
                  <small class="text-muted">
                    <?= date('D, j M Y', strtotime($comp['competition_date'])) ?>
                    <?php if ($comp['venue_name']): ?> at <?= e($comp['venue_name']) ?><?php endif; ?>
                  </small>
                </div>
                <span class="badge bg-primary"><?= e($comp['competition_type']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($pastCompetitions)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">Recent Results</h5>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($pastCompetitions as $comp): ?>
            <div class="list-group-item">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <strong><?= e($comp['title']) ?></strong><br>
                  <small class="text-muted"><?= date('j M Y', strtotime($comp['competition_date'])) ?></small>
                </div>
                <?php if ($comp['result_count'] > 0): ?>
                  <a href="/public/competition_results.php?id=<?= $comp['id'] ?>" class="btn btn-outline-success btn-sm">View Results</a>
                <?php else: ?>
                  <span class="badge bg-secondary">No results</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($clubLeaderboards)): ?>
      <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Club Leaderboards</h5>
          <?php if ($isAdmin): ?>
            <a href="/public/admin/leaderboards.php?club_id=<?= $club['id'] ?>" class="btn btn-sm btn-outline-primary">Manage</a>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php
            $metricLabels = [
              'competition_points' => 'Points',
              'total_catches' => 'Catches',
              'total_weight' => 'Weight',
              'biggest_fish' => 'Biggest',
              'species_count' => 'Species'
            ];
            $metricIcons = [
              'competition_points' => 'trophy',
              'total_catches' => 'water',
              'total_weight' => 'speedometer2',
              'biggest_fish' => 'award',
              'species_count' => 'collection'
            ];
            foreach ($clubLeaderboards as $lb): 
            ?>
              <div class="col-12">
                <a href="/public/club_leaderboard.php?id=<?= $lb['id'] ?>" class="text-decoration-none">
                  <div class="card border h-100" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <div class="card-body p-3">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <h6 class="mb-1 text-dark"><?= e($lb['name']) ?></h6>
                          <small class="text-muted">
                            <i class="bi bi-<?= $metricIcons[$lb['metric_type']] ?? 'graph-up' ?> me-1"></i>
                            <?= $metricLabels[$lb['metric_type']] ?? $lb['metric_type'] ?>
                            &bull; <?= $lb['entry_count'] ?> members
                          </small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                      </div>
                    </div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php elseif ($isAdmin || $isCommittee): ?>
      <div class="card mb-4">
        <div class="card-body text-center py-4">
          <i class="bi bi-trophy fs-2 text-muted mb-2"></i>
          <p class="text-muted mb-2">Create leaderboards to track member rankings</p>
          <a href="/public/admin/leaderboards.php?club_id=<?= $club['id'] ?>" class="btn btn-primary btn-sm">Create Leaderboard</a>
        </div>
      </div>
    <?php endif; ?>

  </div>
  
  <div class="col-lg-4">
    
    <?php if (!$isAdmin && !$isMember): ?>
      <div class="card membership-card mb-4">
        <div class="card-body text-center">
          <?php if (!$isLoggedIn): ?>
            <h5 class="mb-3">Want to join this club?</h5>
            <p class="text-muted">Sign in or create an account to request membership.</p>
            <a href="/" class="btn btn-primary">Log In</a>
            <a href="/public/auth/register.php" class="btn btn-outline-primary">Sign Up</a>
          <?php elseif ($membershipStatus === 'pending'): ?>
            <h5 class="mb-3">Request Pending</h5>
            <p class="text-muted">Your membership request is being reviewed by the club admin.</p>
            <form method="post">
              <input type="hidden" name="action" value="cancel_request">
              <button type="submit" class="btn btn-outline-secondary btn-sm">Cancel Request</button>
            </form>
          <?php elseif ($membershipStatus === 'suspended'): ?>
            <h5 class="mb-3">Membership Suspended</h5>
            <p class="text-muted">Your membership has been suspended. Contact the club admin for more information.</p>
          <?php elseif ($membershipStatus === 'expired'): ?>
            <h5 class="mb-3">Membership Expired</h5>
            <p class="text-muted">Your membership has expired. Contact the club admin to renew.</p>
          <?php elseif ($isInAnyClub): ?>
            <h5 class="mb-3">Already a Member</h5>
            <p class="text-muted">You are already a member of <strong><?= e($existingClubName) ?></strong>. You can only belong to one club at a time.</p>
          <?php else: ?>
            <h5 class="mb-3">Join This Club</h5>
            <p class="text-muted">Request to become a member of this angling club.</p>
            <form method="post">
              <input type="hidden" name="action" value="request_join">
              <button type="submit" class="btn btn-primary btn-lg w-100">Request to Join</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($address)): ?>
      <div class="card info-card mb-4">
        <div class="card-header bg-white">
          <h6 class="mb-0">Location</h6>
        </div>
        <div class="card-body">
          <?php foreach ($address as $line): ?>
            <?= e($line) ?><br>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php 
      $hasContact = ($profileSettings['contact_email'] ?? '') || ($profileSettings['contact_phone'] ?? '') || ($club['contact_email'] ?? '');
      $hasSocial = ($profileSettings['facebook_url'] ?? '') || ($profileSettings['instagram_url'] ?? '') || ($profileSettings['twitter_url'] ?? '') || ($profileSettings['website_url'] ?? '');
    ?>
    <?php if ($hasContact || $hasSocial): ?>
      <div class="card info-card mb-4">
        <div class="card-header bg-white">
          <h6 class="mb-0">Contact & Social</h6>
        </div>
        <div class="card-body">
          <?php if ($profileSettings['contact_email'] ?? $club['contact_email']): ?>
            <div class="mb-2">
              <strong>Email:</strong><br>
              <a href="mailto:<?= e($profileSettings['contact_email'] ?? $club['contact_email']) ?>"><?= e($profileSettings['contact_email'] ?? $club['contact_email']) ?></a>
            </div>
          <?php endif; ?>
          <?php if ($profileSettings['contact_phone'] ?? ''): ?>
            <div class="mb-2">
              <strong>Phone:</strong><br>
              <?= e($profileSettings['contact_phone']) ?>
            </div>
          <?php endif; ?>
          <?php if ($hasSocial): ?>
            <div class="mt-3">
              <?php if ($profileSettings['facebook_url'] ?? ''): ?>
                <a href="<?= e($profileSettings['facebook_url']) ?>" target="_blank" class="social-link" title="Facebook">f</a>
              <?php endif; ?>
              <?php if ($profileSettings['instagram_url'] ?? ''): ?>
                <a href="<?= e($profileSettings['instagram_url']) ?>" target="_blank" class="social-link" title="Instagram">ig</a>
              <?php endif; ?>
              <?php if ($profileSettings['twitter_url'] ?? ''): ?>
                <a href="<?= e($profileSettings['twitter_url']) ?>" target="_blank" class="social-link" title="Twitter/X">X</a>
              <?php endif; ?>
              <?php if ($profileSettings['website_url'] ?? ''): ?>
                <a href="<?= e($profileSettings['website_url']) ?>" target="_blank" class="social-link" title="Website">W</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php 
      $hasPoliciesLocal = !empty(trim($club['constitution'] ?? '')) || 
                     !empty(trim($club['rules_policies'] ?? '')) || 
                     !empty(trim($club['privacy_policy'] ?? '')) || 
                     !empty(trim($club['membership_terms'] ?? ''));
      $canEditPolicies = $isAdmin || in_array($userCommitteeRole ?? '', ['chairperson', 'secretary', 'pro']);
    ?>
    <?php if ($hasPoliciesLocal || $canEditPolicies): ?>
      <div class="card info-card mb-4">
        <div class="card-header bg-white">
          <h6 class="mb-0">Club Documents</h6>
        </div>
        <div class="card-body">
          <a href="/public/policies.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-primary btn-sm w-100">
            View Policies & Constitution
          </a>
          <?php if ($isAdmin || in_array($userCommitteeRole ?? '', ['chairperson', 'secretary', 'pro'])): ?>
            <a href="/public/admin/policies.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2">
              Edit Policies
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($clubSponsors)): ?>
      <div class="card info-card mb-4">
        <div class="card-header bg-white">
          <h6 class="mb-0">Our Sponsors & Supporters</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php foreach ($clubSponsors as $sponsor): ?>
              <div class="col-6">
                <?php if ($sponsor['website']): ?>
                  <a href="<?= e($sponsor['website']) ?>" target="_blank" class="text-decoration-none d-block text-center">
                <?php else: ?>
                  <div class="text-center">
                <?php endif; ?>
                  <?php if ($sponsor['logo_url']): ?>
                    <img src="<?= e($sponsor['logo_url']) ?>" alt="<?= e($sponsor['name']) ?>" class="img-fluid mb-2" style="max-height: 60px;">
                  <?php else: ?>
                    <div class="bg-light rounded p-2 mb-2 d-flex align-items-center justify-content-center" style="height: 60px;">
                      <i class="bi bi-building text-muted"></i>
                    </div>
                  <?php endif; ?>
                  <div class="small text-dark"><?= e($sponsor['name']) ?></div>
                  <?php if ($sponsor['company'] && $sponsor['company'] !== $sponsor['name']): ?>
                    <div class="small text-muted"><?= e($sponsor['company']) ?></div>
                  <?php endif; ?>
                <?php if ($sponsor['website']): ?>
                  </a>
                <?php else: ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-body text-center">
        <p class="text-muted mb-2">Share this club</p>
        <input type="text" class="form-control form-control-sm text-center" 
               value="<?= e((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/public/club.php?slug=' . $club['slug']) ?>" 
               readonly onclick="this.select()">
      </div>
    </div>

  </div>
</div>

<?php if ($userId && !$isAdmin && !$membershipStatus && !$isInAnyClub && !empty($juniorOptions)): ?>
<div class="modal fade" id="joinJuniorModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Junior Member</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <div class="modal-body">
          <input type="hidden" name="action" value="join_junior">
          <div class="mb-3">
            <label class="form-label">Select Junior</label>
            <select name="junior_id" class="form-select" required>
              <option value="">Choose...</option>
              <?php foreach ($juniorOptions as $junior): ?>
                <option value="<?= $junior['id'] ?>"><?= e($junior['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php 
if ($isLoggedIn) {
  member_shell_end();
} else {
  public_shell_end();
}
?>
