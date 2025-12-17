<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$userId = current_user_id();
$isLoggedIn = (bool)$userId;

$userCountry = '';
$userTown = '';
if ($isLoggedIn) {
  $stmt = $pdo->prepare("SELECT town, country FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $userData = $stmt->fetch();
  $userCountry = $userData['country'] ?? '';
  $userTown = $userData['town'] ?? '';
}

$filterCountry = $_GET['country'] ?? $userCountry;
$filterTown = $_GET['town'] ?? '';

$stmt = $pdo->query("SELECT DISTINCT country FROM competitions WHERE country IS NOT NULL AND country != '' ORDER BY country");
$countries = $stmt->fetchAll(PDO::FETCH_COLUMN);

$towns = [];
if ($filterCountry !== '') {
  $stmt = $pdo->prepare("SELECT DISTINCT town FROM competitions WHERE country = ? AND town IS NOT NULL AND town != '' ORDER BY town");
  $stmt->execute([$filterCountry]);
  $towns = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$sql = "
  SELECT comp.*, c.name as club_name, c.slug as club_slug
  FROM competitions comp
  JOIN clubs c ON comp.club_id = c.id
  WHERE comp.visibility = 'open'
    AND comp.competition_date >= CURDATE()
";
$params = [];

if ($filterCountry !== '') {
  $sql .= " AND comp.country = ?";
  $params[] = $filterCountry;
}

if ($filterTown !== '') {
  $sql .= " AND comp.town = ?";
  $params[] = $filterTown;
}

$sql .= " ORDER BY comp.competition_date ASC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Browse Competitions - Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .competition-card { transition: transform 0.2s; }
    .competition-card:hover { transform: translateY(-2px); }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <?php if ($isLoggedIn): ?>
        <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
        <a class="btn btn-light btn-sm ms-2" href="/public/auth/logout.php">Logout</a>
      <?php else: ?>
        <a class="btn btn-outline-light btn-sm" href="/public/auth/login.php">Login</a>
        <a class="btn btn-light btn-sm ms-2" href="/public/auth/register.php">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row mb-4">
    <div class="col">
      <h1>Browse Competitions</h1>
      <p class="text-muted">Find open fishing competitions near you</p>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
              <label class="form-label">Country</label>
              <select name="country" class="form-select" onchange="this.form.submit()">
                <option value="">All Countries</option>
                <?php foreach ($countries as $c): ?>
                  <option value="<?= e($c) ?>" <?= $filterCountry === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Town/City</label>
              <select name="town" class="form-select">
                <option value="">All Towns</option>
                <?php foreach ($towns as $t): ?>
                  <option value="<?= e($t) ?>" <?= $filterTown === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <button type="submit" class="btn btn-primary">Filter</button>
              <a href="/public/competitions.php" class="btn btn-outline-secondary">Clear</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($competitions)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <h5 class="text-muted">No competitions found</h5>
        <p class="text-muted">Try adjusting your filters or check back later.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($competitions as $comp): ?>
        <div class="col-md-6 col-lg-4 mb-4">
          <div class="card competition-card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title"><?= e($comp['title']) ?></h5>
              <h6 class="card-subtitle mb-2 text-muted"><?= e($comp['venue_name']) ?></h6>
              
              <div class="mb-2">
                <strong><?= date('l, j F Y', strtotime($comp['competition_date'])) ?></strong>
                <?php if ($comp['start_time']): ?>
                  <br><span class="text-muted">Start: <?= date('g:i A', strtotime($comp['start_time'])) ?></span>
                <?php endif; ?>
              </div>
              
              <div class="small text-muted mb-2">
                <?php if ($comp['town']): ?>
                  <?= e($comp['town']) ?>
                <?php endif; ?>
                <?php if ($comp['county']): ?>
                  , <?= e($comp['county']) ?>
                <?php endif; ?>
                <?php if ($comp['country']): ?>
                  <br><?= e($comp['country']) ?>
                <?php endif; ?>
              </div>
              
              <?php if ($comp['description']): ?>
                <p class="card-text small"><?= e($comp['description']) ?></p>
              <?php endif; ?>
              
              <div class="small text-muted">
                Hosted by <a href="/public/club.php?slug=<?= e($comp['club_slug']) ?>"><?= e($comp['club_name']) ?></a>
              </div>
            </div>
            <div class="card-footer bg-white border-top-0">
              <?php if ($comp['latitude'] && $comp['longitude']): ?>
                <a href="https://www.google.com/maps?q=<?= $comp['latitude'] ?>,<?= $comp['longitude'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                  View on Map
                </a>
              <?php endif; ?>
              <a href="/public/club.php?slug=<?= e($comp['club_slug']) ?>" class="btn btn-outline-secondary btn-sm">
                View Club
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<footer class="bg-dark text-white py-4 mt-5">
  <div class="container text-center">
    <p class="mb-0">Powered by Angling Club Manager</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
