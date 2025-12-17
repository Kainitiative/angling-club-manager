<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$errors = [];
$email = '';
$isLoggedIn = (bool)current_user_id();

// If already logged in, redirect to dashboard
if ($isLoggedIn) {
  redirect('/public/dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $password = (string)($_POST['password'] ?? '');

  $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    $errors[] = "Invalid email or password.";
  } else {
    $_SESSION['user_id'] = (int)$user['id'];
    redirect('/public/dashboard.php');
  }
}

// Fetch public clubs to display on landing page
$stmt = $pdo->query("
  SELECT c.id, c.name, c.slug, c.about_text, c.location_text, c.city, c.logo_url,
         (SELECT COUNT(*) FROM club_admins ca WHERE ca.club_id = c.id) as admin_count
  FROM clubs c
  WHERE c.is_public = 1
  ORDER BY c.created_at DESC
  LIMIT 12
");
$publicClubs = $stmt->fetchAll();

// Fetch upcoming open competitions
$stmt = $pdo->query("
  SELECT comp.*, c.name as club_name, c.slug as club_slug
  FROM competitions comp
  JOIN clubs c ON comp.club_id = c.id
  WHERE comp.visibility = 'open'
    AND comp.competition_date >= CURDATE()
  ORDER BY comp.competition_date ASC
  LIMIT 6
");
$upcomingCompetitions = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Angling Club Manager - Find & Manage Fishing Clubs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .hero {
      background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
      color: white;
      padding: 80px 0;
    }
    .club-card {
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .club-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .club-logo {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      background: #e9ecef;
    }
    .club-logo-placeholder {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 1.5rem;
    }
    .features-section {
      background: #f8f9fa;
    }
    .feature-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      margin: 0 auto 1rem;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm me-2" href="#login-section">Log In</a>
      <a class="btn btn-primary btn-sm" href="/public/auth/register.php">Sign Up</a>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <h1 class="display-4 fw-bold mb-3">Manage Your Fishing Club With Ease</h1>
        <p class="lead mb-4">The complete platform for angling clubs. Manage members, organize competitions, schedule meetings, and grow your community.</p>
        <a href="/public/auth/register.php" class="btn btn-light btn-lg me-2">Get Started Free</a>
        <a href="#clubs-section" class="btn btn-outline-light btn-lg">Browse Clubs</a>
      </div>
      <div class="col-lg-5 d-none d-lg-block text-center">
        <div class="display-1">🎣</div>
      </div>
    </div>
  </div>
</section>

<section class="features-section py-5">
  <div class="container">
    <h2 class="text-center mb-5">Everything You Need to Run Your Club</h2>
    <div class="row g-4">
      <div class="col-md-4 text-center">
        <div class="feature-icon">👥</div>
        <h5>Member Management</h5>
        <p class="text-muted">Track memberships, manage renewals, and keep member records organized.</p>
      </div>
      <div class="col-md-4 text-center">
        <div class="feature-icon">🏆</div>
        <h5>Competitions</h5>
        <p class="text-muted">Organize fishing competitions, track results, and maintain leaderboards.</p>
      </div>
      <div class="col-md-4 text-center">
        <div class="feature-icon">📅</div>
        <h5>Events & Meetings</h5>
        <p class="text-muted">Schedule club meetings, outings, and events with easy RSVP tracking.</p>
      </div>
    </div>
  </div>
</section>

<section id="clubs-section" class="py-5">
  <div class="container">
    <h2 class="text-center mb-2">Discover Angling Clubs</h2>
    <p class="text-center text-muted mb-5">Find and join fishing clubs in your area</p>
    
    <?php if (empty($publicClubs)): ?>
      <div class="text-center py-5">
        <p class="text-muted mb-3">No public clubs yet. Be the first to create one!</p>
        <a href="/public/auth/register.php" class="btn btn-primary">Create Your Club</a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($publicClubs as $club): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card club-card h-100">
              <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                  <?php if ($club['logo_url']): ?>
                    <img src="<?= e($club['logo_url']) ?>" alt="<?= e($club['name']) ?>" class="club-logo me-3">
                  <?php else: ?>
                    <div class="club-logo-placeholder me-3">
                      <?= strtoupper(substr($club['name'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                  <div>
                    <h5 class="card-title mb-1"><?= e($club['name']) ?></h5>
                    <?php if ($club['city'] || $club['location_text']): ?>
                      <small class="text-muted">
                        📍 <?= e($club['city'] ?: $club['location_text']) ?>
                      </small>
                    <?php endif; ?>
                  </div>
                </div>
                <?php if ($club['about_text']): ?>
                  <p class="card-text text-muted small">
                    <?= e(substr($club['about_text'], 0, 120)) ?><?= strlen($club['about_text']) > 120 ? '...' : '' ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($upcomingCompetitions)): ?>
<section id="competitions-section" class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-2">Upcoming Competitions</h2>
    <p class="text-center text-muted mb-5">Open fishing competitions you can join</p>
    
    <div class="row g-4">
      <?php foreach ($upcomingCompetitions as $comp): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card club-card h-100">
            <div class="card-body">
              <h5 class="card-title"><?= e($comp['title']) ?></h5>
              <h6 class="card-subtitle mb-2 text-muted"><?= e($comp['venue_name']) ?></h6>
              
              <div class="mb-2">
                <strong><?= date('l, j F Y', strtotime($comp['competition_date'])) ?></strong>
                <?php if ($comp['start_time']): ?>
                  <br><small class="text-muted">Start: <?= date('g:i A', strtotime($comp['start_time'])) ?></small>
                <?php endif; ?>
              </div>
              
              <div class="small text-muted mb-2">
                <?php if ($comp['town']): ?>
                  <?= e($comp['town']) ?>
                <?php endif; ?>
                <?php if ($comp['country']): ?>
                  , <?= e($comp['country']) ?>
                <?php endif; ?>
              </div>
              
              <div class="small text-muted">
                Hosted by <a href="/public/club.php?slug=<?= e($comp['club_slug']) ?>"><?= e($comp['club_name']) ?></a>
              </div>
            </div>
            <div class="card-footer bg-white border-top-0">
              <?php if ($comp['latitude'] && $comp['longitude']): ?>
                <a href="https://www.google.com/maps?q=<?= $comp['latitude'] ?>,<?= $comp['longitude'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                  View Map
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
    
    <div class="text-center mt-4">
      <a href="/public/competitions.php" class="btn btn-primary">Browse All Competitions</a>
    </div>
  </div>
</section>
<?php endif; ?>

<section id="login-section" class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow">
          <div class="card-body p-4">
            <h2 class="card-title text-center mb-4">Log In</h2>

            <?php if ($errors): ?>
              <div class="alert alert-danger">
                <?= e($errors[0]) ?>
              </div>
            <?php endif; ?>

            <form method="post" action="/">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Log In</button>
            </form>

            <p class="text-center mt-3 mb-0">
              New here? <a href="/public/auth/register.php">Create an account</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="bg-dark text-white py-4">
  <div class="container text-center">
    <p class="mb-0">Angling Club Manager</p>
  </div>
</footer>

</body>
</html>
