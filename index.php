<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/rss_helper.php';

$errors = [];
$email = '';
$isLoggedIn = (bool)current_user_id();

// Fetch IFI news feed
$ifiNews = fetch_rss_feed('https://fishinginireland.info/feed', 3);

// Fetch ISFC news feed
$isfcNews = fetch_rss_feed('https://specimenfish.ie/feed', 3);

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

// If already logged in, redirect to dashboard
// (Moved AFTER feed fetch for non-members, but actually if they are logged in they go to dashboard)
if ($isLoggedIn) {
  redirect('/public/dashboard.php');
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Angling Ireland - Find & Manage Fishing Clubs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/assets/css/enhancements.css" rel="stylesheet">
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
    .hover-up {
      transition: all 0.3s ease;
    }
    .hover-up:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
    }
    .tracking-wider {
      letter-spacing: 0.1em;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/">Angling Ireland</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm me-2" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Log In</a>
      <a class="btn btn-primary btn-sm" href="/public/auth/register.php">Sign Up</a>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <div class="mb-3">
          <span class="badge bg-success fs-6 px-3 py-2">
            <i class="bi bi-gift me-1"></i> Free During Beta - All Features Included
          </span>
        </div>
        <h1 class="display-4 fw-bold mb-3">The Central Digital Platform for Irish Angling</h1>
        <p class="lead mb-4">Empowering clubs, syndicates, fisheries, and guides with professional tools to manage, connect, and grow their community.</p>
        <a href="/public/auth/register.php" class="btn btn-light btn-lg me-2">Get Started Free</a>
        <a href="/public/clubs.php" class="btn btn-outline-light btn-lg">Browse Clubs</a>
        <p class="mt-3 small opacity-75">No credit card required. Premium features coming soon.</p>
      </div>
      <div class="col-lg-5 d-none d-lg-block text-center">
        <div class="display-1">🎣</div>
      </div>
    </div>
  </div>
</section>

<section class="features-section py-5">
  <div class="container">
    <div class="row g-4 mb-5">
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

    <?php if (!empty($ifiNews)): ?>
    <div class="row justify-content-center mt-4 mb-5">
      <div class="col-lg-12">
        <div class="text-center mb-5">
          <span class="text-primary text-uppercase fw-bold tracking-wider small">National Angling Updates</span>
          <h2 class="mt-2 fw-bold">From Around the Waters</h2>
          <div class="bg-primary mx-auto mt-3" style="width: 50px; height: 3px;"></div>
        </div>
        
        <div class="row g-4">
          <?php foreach ($ifiNews as $index => $news): ?>
            <div class="col-md-4">
              <div class="card h-100 border-0 shadow-sm hover-up overflow-hidden">
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1">IFI News</span>
                    <small class="text-muted"><?= date('d M Y', $news['timestamp']) ?></small>
                  </div>
                  <h5 class="card-title fw-bold mb-3">
                    <a href="#" class="text-dark text-decoration-none stretched-link" data-bs-toggle="modal" data-bs-target="#newsModal<?= $index ?>">
                      <?= e($news['title']) ?>
                    </a>
                  </h5>
                  <p class="card-text text-muted small mb-4">
                    <?= e(mb_strimwidth($news['description'], 0, 120, "...")) ?>
                  </p>
                  <div class="mt-auto d-flex align-items-center text-primary fw-bold small">
                    Read Story <i class="bi bi-arrow-right ms-2"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- News Modal -->
            <div class="modal fade" id="newsModal<?= $index ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body p-4 p-md-5 pt-0">
                    <div class="mb-3">
                      <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1">National Update</span>
                      <small class="text-muted ms-2"><?= date('d M Y', $news['timestamp']) ?></small>
                    </div>
                    <h2 class="fw-bold mb-4"><?= e($news['title']) ?></h2>
                    <div class="news-content text-muted mb-4" style="font-size: 1.1rem; line-height: 1.8;">
                      <?php 
                        // Try to get full content if possible, otherwise fallback to description
                        $fullContent = fetch_full_article_content($news['link']);
                        if ($fullContent && strlen($fullContent) > strlen($news['description'])) {
                            echo $fullContent;
                        } else {
                            echo nl2br(e($news['description'])); 
                        }
                      ?>
                    </div>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pt-4 border-top">
                      <div class="text-muted small">
                        Source: <span class="fw-bold">Inland Fisheries Ireland</span>
                      </div>
                      <a href="<?= e($news['link']) ?>" target="_blank" class="btn btn-primary px-4 py-2">
                        View Full Article on IFI <i class="bi bi-box-arrow-up-right ms-2"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($isfcNews)): ?>
    <div class="row justify-content-center mt-5">
      <div class="col-lg-12">
        <div class="text-center mb-5">
          <span class="text-success text-uppercase fw-bold tracking-wider small">Specimen Fish Reports</span>
          <h2 class="mt-2 fw-bold">Recent Trophy Catches & Records</h2>
          <div class="bg-success mx-auto mt-3" style="width: 50px; height: 3px;"></div>
        </div>
        
        <div class="row g-4">
          <?php foreach ($isfcNews as $index => $news): ?>
            <?php $isfcIndex = $index + 100; // Unique index for ISFC modals ?>
            <div class="col-md-4">
              <div class="card h-100 border-0 shadow-sm hover-up overflow-hidden">
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-light text-success border border-success-subtle px-2 py-1">ISFC Report</span>
                    <small class="text-muted"><?= date('d M Y', $news['timestamp']) ?></small>
                  </div>
                  <h5 class="card-title fw-bold mb-3">
                    <a href="#" class="text-dark text-decoration-none stretched-link" data-bs-toggle="modal" data-bs-target="#newsModal<?= $isfcIndex ?>">
                      <?= e($news['title']) ?>
                    </a>
                  </h5>
                  <p class="card-text text-muted small mb-4">
                    <?= e(mb_strimwidth($news['description'], 0, 120, "...")) ?>
                  </p>
                  <div class="mt-auto d-flex align-items-center text-success fw-bold small">
                    Read Report <i class="bi bi-arrow-right ms-2"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- News Modal -->
            <div class="modal fade" id="newsModal<?= $isfcIndex ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body p-4 p-md-5 pt-0">
                    <div class="mb-3">
                      <span class="badge bg-light text-success border border-success-subtle px-2 py-1">Specimen Report</span>
                      <small class="text-muted ms-2"><?= date('d M Y', $news['timestamp']) ?></small>
                    </div>
                    <h2 class="fw-bold mb-4"><?= e($news['title']) ?></h2>
                    <div class="news-content text-muted mb-4" style="font-size: 1.1rem; line-height: 1.8;">
                      <?php 
                        $fullContent = fetch_full_article_content($news['link']);
                        if ($fullContent && strlen($fullContent) > strlen($news['description'])) {
                            echo $fullContent;
                        } else {
                            echo nl2br(e($news['description'])); 
                        }
                      ?>
                    </div>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pt-4 border-top">
                      <div class="text-muted small">
                        Source: <span class="fw-bold">Irish Specimen Fish Committee</span>
                      </div>
                      <a href="<?= e($news['link']) ?>" target="_blank" class="btn btn-success px-4 py-2">
                        View Full Report on ISFC <i class="bi bi-box-arrow-up-right ms-2"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
          <p class="small text-muted mb-0">Official trophy catch verification data from the Irish Specimen Fish Committee.</p>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <h2 class="mb-4">Join Our Community</h2>
        <p class="lead text-muted mb-4">Register for free to discover angling clubs, browse upcoming competitions, and connect with fellow anglers in your area.</p>
        <div class="row g-4 mb-4">
          <div class="col-md-4">
            <div class="card h-100 border-0 bg-light">
              <div class="card-body text-center">
                <div class="display-4 mb-2">🎣</div>
                <h5>Find Clubs</h5>
                <p class="text-muted small mb-0">Discover local angling clubs and join communities that match your fishing style.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card h-100 border-0 bg-light">
              <div class="card-body text-center">
                <div class="display-4 mb-2">🏆</div>
                <h5>Competitions</h5>
                <p class="text-muted small mb-0">Browse and enter fishing competitions hosted by clubs near you.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card h-100 border-0 bg-light">
              <div class="card-body text-center">
                <div class="display-4 mb-2">📊</div>
                <h5>Track Results</h5>
                <p class="text-muted small mb-0">View competition results and see how you stack up against other anglers.</p>
              </div>
            </div>
          </div>
        </div>
        <a href="/public/auth/register.php" class="btn btn-primary btn-lg">Create Free Account</a>
      </div>
    </div>
  </div>
</section>

<footer class="bg-dark text-white py-4">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-3 mb-md-0">
        <h6 class="text-white mb-2">Angling Ireland</h6>
        <p class="text-muted small mb-1">&copy; <?= date('Y') ?> Patrick Ryan Digital Design</p>
        <p class="text-muted small mb-0">
          <span class="badge bg-success">Free During Beta</span>
        </p>
      </div>
      <div class="col-md-4 mb-3 mb-md-0">
        <h6 class="text-white mb-2">Explore</h6>
        <a href="/public/clubs.php" class="text-muted text-decoration-none d-block small mb-1">Browse Clubs</a>
        <a href="/public/competitions.php" class="text-muted text-decoration-none d-block small">Competitions</a>
      </div>
      <div class="col-md-4">
        <h6 class="text-white mb-2">Legal</h6>
        <a href="/public/legal/privacy.php" class="text-muted text-decoration-none d-block small mb-1">Privacy Policy</a>
        <a href="/public/legal/terms.php" class="text-muted text-decoration-none d-block small mb-1">Terms & Conditions</a>
        <a href="/public/legal/cookies.php" class="text-muted text-decoration-none d-block small">Cookie Policy</a>
      </div>
    </div>
  </div>
</footer>

<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" id="loginModalLabel">Log In</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 pb-4">
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

<?php if ($errors): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
  });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/enhancements.js"></script>
</body>
</html>
