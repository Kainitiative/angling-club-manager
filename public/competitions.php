<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

require_login();

$userId = current_user_id();

$sql = "
  SELECT comp.*, c.name as club_name, c.slug as club_slug
  FROM competitions comp
  JOIN clubs c ON comp.club_id = c.id
  WHERE comp.status = 'upcoming'
    AND comp.competition_date >= CURRENT_DATE
  ORDER BY comp.competition_date ASC
  LIMIT 50
";

$stmt = $pdo->query($sql);
$competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Browse Competitions - Angling Ireland</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .competition-card { transition: transform 0.2s; }
    .competition-card:hover { transform: translateY(-2px); }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Ireland</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
      <a class="btn btn-light btn-sm ms-2" href="/public/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row mb-4">
    <div class="col">
      <h1><i class="bi bi-trophy me-2"></i>Browse Competitions</h1>
      <p class="text-muted">Find upcoming fishing competitions</p>
    </div>
  </div>

  <?php if (empty($competitions)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
        <h5 class="text-muted">No upcoming competitions</h5>
        <p class="text-muted">Check back later for new events.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($competitions as $comp): ?>
        <div class="col-md-6 col-lg-4 mb-4">
          <div class="card competition-card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title"><?= e($comp['name']) ?></h5>
              
              <div class="mb-2">
                <i class="bi bi-calendar-event text-primary me-1"></i>
                <strong><?= date('l, j F Y', strtotime($comp['competition_date'])) ?></strong>
              </div>
              
              <?php if (!empty($comp['location'])): ?>
                <div class="small text-muted mb-2">
                  <i class="bi bi-geo-alt me-1"></i><?= e($comp['location']) ?>
                </div>
              <?php endif; ?>
              
              <?php if (!empty($comp['description'])): ?>
                <p class="card-text small"><?= e(substr($comp['description'], 0, 150)) ?><?= strlen($comp['description']) > 150 ? '...' : '' ?></p>
              <?php endif; ?>
              
              <div class="small text-muted">
                <i class="bi bi-people me-1"></i>
                Hosted by <a href="/public/club.php?slug=<?= e($comp['club_slug']) ?>"><?= e($comp['club_name']) ?></a>
              </div>
            </div>
            <div class="card-footer bg-white border-top-0">
              <a href="/public/club.php?slug=<?= e($comp['club_slug']) ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye me-1"></i>View Club
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
    <p class="mb-0">Powered by Angling Ireland</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
