<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$slug = $_GET['slug'] ?? '';

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

if (!$club['is_public']) {
  $userId = current_user_id();
  if (!$userId) {
    http_response_code(403);
    exit('This club is private');
  }
  $stmt = $pdo->prepare("SELECT id FROM club_admins WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$club['id'], $userId]);
  if (!$stmt->fetch()) {
    http_response_code(403);
    exit('This club is private');
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

$isLoggedIn = (bool)current_user_id();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($club['name']) ?> - Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .club-header {
      background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
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
        <h1 class="display-5 fw-bold mb-2"><?= e($club['name']) ?></h1>
        <?php if ($club['town'] || $club['city']): ?>
          <p class="lead mb-0 opacity-75">
            📍 <?= e($club['town'] ?: $club['city']) ?><?= $club['county'] ? ', ' . e($club['county']) : '' ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<div class="container py-5">
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
                🎣 <?= e($fishingStyleLabels[$style] ?? ucfirst($style)) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
    
    <div class="col-lg-4">
      
      <?php if (!empty($address)): ?>
        <div class="card info-card mb-4">
          <div class="card-header bg-white">
            <h6 class="mb-0">📍 Location</h6>
          </div>
          <div class="card-body">
            <?php foreach ($address as $line): ?>
              <?= e($line) ?><br>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($club['contact_email']): ?>
        <div class="card info-card mb-4">
          <div class="card-header bg-white">
            <h6 class="mb-0">✉️ Contact</h6>
          </div>
          <div class="card-body">
            <a href="mailto:<?= e($club['contact_email']) ?>"><?= e($club['contact_email']) ?></a>
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

</body>
</html>
