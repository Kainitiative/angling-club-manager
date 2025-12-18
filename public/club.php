<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

require_login();

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
    try {
      $stmt = $pdo->prepare("INSERT INTO club_members (club_id, user_id, membership_status) VALUES (?, ?, 'pending')");
      $stmt->execute([$club['id'], $userId]);
      $membershipStatus = 'pending';
      $message = 'Your membership request has been submitted. The club admin will review it shortly.';
      $messageType = 'success';
    } catch (PDOException $e) {
      if (strpos($e->getMessage(), 'Duplicate') !== false || $e->getCode() == 23000) {
        $stmt = $pdo->prepare("SELECT membership_status FROM club_members WHERE club_id = ? AND user_id = ?");
        $stmt->execute([$club['id'], $userId]);
        $row = $stmt->fetch();
        $membershipStatus = $row['membership_status'] ?? null;
        $message = 'You already have a membership record with this club.';
        $messageType = 'warning';
      } else {
        throw $e;
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

// Fetch upcoming competitions for this club
// Open competitions are visible to everyone; private only to members/admins
$canSeePrivate = $isAdmin || $isMember;
if ($canSeePrivate) {
  $stmt = $pdo->prepare("
    SELECT * FROM competitions 
    WHERE club_id = ? AND competition_date >= CURDATE()
    ORDER BY competition_date ASC
    LIMIT 10
  ");
} else {
  $stmt = $pdo->prepare("
    SELECT * FROM competitions 
    WHERE club_id = ? AND competition_date >= CURDATE() AND visibility = 'open'
    ORDER BY competition_date ASC
    LIMIT 10
  ");
}
$stmt->execute([$club['id']]);
$clubCompetitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch past competitions with results
if ($canSeePrivate) {
  $stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM competition_results cr WHERE cr.competition_id = c.id) as result_count
    FROM competitions c
    WHERE c.club_id = ? AND c.competition_date < CURDATE()
    ORDER BY c.competition_date DESC
    LIMIT 10
  ");
} else {
  $stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM competition_results cr WHERE cr.competition_id = c.id) as result_count
    FROM competitions c
    WHERE c.club_id = ? AND c.competition_date < CURDATE() AND c.visibility = 'open'
    ORDER BY c.competition_date DESC
    LIMIT 10
  ");
}
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

$stmt = $pdo->prepare("SELECT * FROM club_membership_fees WHERE club_id = ? AND is_active = 1 ORDER BY display_order, id");
$stmt->execute([$club['id']]);
$membershipFees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM club_perks WHERE club_id = ? ORDER BY display_order, id");
$stmt->execute([$club['id']]);
$clubPerks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM club_gallery WHERE club_id = ? ORDER BY display_order, id");
$stmt->execute([$club['id']]);
$clubGallery = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($club['name']) ?> - Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
      padding: 60px 0;
    }
    .club-logo {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 12px;
      border: 4px solid white;
      background: white;
    }
    .club-logo-placeholder {
      width: 120px;
      height: 120px;
      border-radius: 12px;
      border: 4px solid white;
      background: rgba(255,255,255,0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
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
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <?php if ($isLoggedIn): ?>
        <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
        <a class="btn btn-outline-light btn-sm" href="/public/auth/logout.php">Logout</a>
      <?php else: ?>
        <a class="btn btn-outline-light btn-sm" href="/">Log In</a>
        <a class="btn btn-primary btn-sm" href="/public/auth/register.php">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<section class="club-header">
  <div class="container">
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
        <h1 class="display-5 fw-bold mb-2"><?= e($heroTitle ?: $club['name']) ?></h1>
        <?php if ($heroTagline): ?>
          <p class="lead mb-2 opacity-90"><?= e($heroTagline) ?></p>
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
        <?php elseif ($membershipStatus === 'pending'): ?>
          <span class="badge bg-info fs-6 p-2">Request Pending</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<div class="container py-5">
  
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
                <?php 
                  $cmAvatar = $cm['profile_picture_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($cm['name']) . '&size=50&background=1e3a5f&color=fff';
                ?>
                <div class="col-md-6">
                  <div class="d-flex align-items-center p-2 bg-light rounded">
                    <img src="<?= e($cmAvatar) ?>" alt="" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                    <div>
                      <div class="fw-semibold"><?= e($cm['name']) ?></div>
                      <small class="text-primary"><?= e($committeeRoleLabels[$cm['committee_role']] ?? ucfirst($cm['committee_role'])) ?></small>
                    </div>
                  </div>
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
              <a href="/public/admin/competitions.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-primary btn-sm">Manage</a>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              <?php foreach ($clubCompetitions as $comp): ?>
                <div class="list-group-item px-0">
                  <div class="d-flex w-100 justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">
                        <?= e($comp['title']) ?>
                        <?php if ($comp['visibility'] === 'private'): ?>
                          <span class="badge bg-secondary">Members Only</span>
                        <?php endif; ?>
                      </h6>
                      <div class="small text-muted">
                        <?= e($comp['venue_name']) ?>
                        <?php if ($comp['town']): ?>
                          &bull; <?= e($comp['town']) ?>
                        <?php endif; ?>
                      </div>
                      <div class="small">
                        <strong><?= date('l, j F Y', strtotime($comp['competition_date'])) ?></strong>
                        <?php if ($comp['start_time']): ?>
                          at <?= date('g:i A', strtotime($comp['start_time'])) ?>
                        <?php endif; ?>
                      </div>
                      <?php if ($comp['description']): ?>
                        <div class="small text-muted mt-1"><?= e($comp['description']) ?></div>
                      <?php endif; ?>
                    </div>
                    <div>
                      <?php if ($comp['latitude'] && $comp['longitude']): ?>
                        <a href="https://www.google.com/maps?q=<?= $comp['latitude'] ?>,<?= $comp['longitude'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                          View Map
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php elseif ($isAdmin): ?>
        <div class="card mb-4">
          <div class="card-body text-center py-4">
            <p class="text-muted mb-2">No upcoming competitions</p>
            <a href="/public/admin/competitions.php?club_id=<?= $club['id'] ?>" class="btn btn-primary btn-sm">Add Competition</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($pastCompetitions)): ?>
        <div class="card mb-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Past Competitions & Results</h5>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              <?php foreach ($pastCompetitions as $comp): ?>
                <div class="list-group-item px-0">
                  <div class="d-flex w-100 justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">
                        <?= e($comp['title']) ?>
                        <?php if ($comp['visibility'] === 'private'): ?>
                          <span class="badge bg-secondary">Members Only</span>
                        <?php endif; ?>
                      </h6>
                      <div class="small text-muted">
                        <?= e($comp['venue_name']) ?>
                        &bull; <?= date('j M Y', strtotime($comp['competition_date'])) ?>
                      </div>
                      <?php if ($comp['result_count'] > 0): ?>
                        <div class="small text-success mt-1">
                          <?= $comp['result_count'] ?> result<?= $comp['result_count'] > 1 ? 's' : '' ?> recorded
                        </div>
                      <?php else: ?>
                        <div class="small text-muted mt-1">No results yet</div>
                      <?php endif; ?>
                    </div>
                    <div>
                      <?php if ($comp['result_count'] > 0): ?>
                        <a href="/public/competition_results.php?id=<?= $comp['id'] ?>" class="btn btn-success btn-sm">
                          View Results
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
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
</div>

<footer class="bg-dark text-white py-4 mt-5">
  <div class="container text-center">
    <p class="mb-0">Powered by Angling Club Manager</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
