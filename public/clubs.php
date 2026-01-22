<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout/member_shell.php';

require_login();

$userId = current_user_id();
$isLoggedIn = true;

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
$filterType = $_GET['type'] ?? '';

$stmt = $pdo->query("SELECT DISTINCT country FROM clubs WHERE country IS NOT NULL AND country != '' AND is_public = 1 ORDER BY country");
$countries = $stmt->fetchAll(PDO::FETCH_COLUMN);

$towns = [];
if ($filterCountry !== '') {
  $stmt = $pdo->prepare("SELECT DISTINCT town FROM clubs WHERE country = ? AND town IS NOT NULL AND town != '' AND is_public = 1 ORDER BY town");
  $stmt->execute([$filterCountry]);
  $towns = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$sql = "
  SELECT c.*, 
         (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.membership_status = 'active') as member_count
  FROM clubs c
  WHERE c.is_public = 1
";
$params = [];

if ($filterCountry !== '') {
  $sql .= " AND c.country = ?";
  $params[] = $filterCountry;
}

if ($filterTown !== '') {
  $sql .= " AND c.town = ?";
  $params[] = $filterTown;
}

if ($filterType === 'commercial') {
  $sql .= " AND c.club_type IN ('commercial_fishery', 'angling_guide', 'charter_boat')";
} elseif ($filterType !== '') {
  $sql .= " AND c.club_type = ?";
  $params[] = $filterType;
}

$sql .= " ORDER BY c.name ASC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fishingStyleLabels = [
  'coarse' => 'Coarse',
  'carp' => 'Carp',
  'match' => 'Match',
  'specimen' => 'Specimen',
  'fly' => 'Fly',
  'game' => 'Game',
  'sea' => 'Sea',
  'pike' => 'Pike',
  'predator' => 'Predator',
  'lure' => 'Lure',
];

member_shell_start($pdo, ['title' => 'Browse Clubs', 'page' => 'clubs', 'section' => 'Main']);
?>
<style>
  .club-card { transition: transform 0.2s, box-shadow 0.2s; }
  .club-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
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
  .fishing-badge {
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 12px;
    background: #e9ecef;
    color: #495057;
    margin: 2px;
    display: inline-block;
  }
</style>

<div class="row mb-4">
  <div class="col">
    <h1>Browse Clubs</h1>
    <p class="text-muted">Find angling clubs near you</p>
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
            <label class="form-label">Type</label>
            <select name="type" class="form-select" onchange="this.form.submit()">
              <option value="">All Types</option>
              <option value="angling_club" <?= $filterType === 'angling_club' ? 'selected' : '' ?>>Angling Club</option>
              <option value="syndicate" <?= $filterType === 'syndicate' ? 'selected' : '' ?>>Syndicate</option>
              <option value="commercial" <?= $filterType === 'commercial' ? 'selected' : '' ?>>Guides & Boats</option>
              <option value="commercial_fishery" <?= $filterType === 'commercial_fishery' ? 'selected' : '' ?>>Fishery</option>
            </select>
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="/public/clubs.php" class="btn btn-outline-secondary">Clear</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if (empty($clubs)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <h5 class="text-muted">No clubs found</h5>
      <p class="text-muted">Try adjusting your filters or check back later.</p>
      <a href="/public/create_club.php" class="btn btn-primary">Create a Club</a>
    </div>
  </div>
<?php else: ?>
  <div class="row">
    <?php foreach ($clubs as $club): ?>
      <?php $styles = $club['fishing_styles'] ? json_decode($club['fishing_styles'], true) : []; ?>
      <div class="col-md-6 col-lg-4 mb-4">
        <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="text-decoration-none">
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
                  <h5 class="card-title mb-1 text-dark"><?= e($club['name']) ?></h5>
                  <?php if (!empty($club['tagline'])): ?>
                    <div class="text-primary small mb-1"><?= e($club['tagline']) ?></div>
                  <?php endif; ?>
                  <?php if ($club['town'] || $club['city']): ?>
                    <small class="text-muted">
                      <?= e($club['town'] ?: $club['city']) ?><?= $club['county'] ? ', ' . e($club['county']) : '' ?>
                    </small>
                  <?php endif; ?>
                </div>
              </div>
              
              <?php if ($club['about_text']): ?>
                <p class="card-text text-muted small mb-2">
                  <?= e(substr($club['about_text'], 0, 100)) ?><?= strlen($club['about_text']) > 100 ? '...' : '' ?>
                </p>
              <?php endif; ?>
              
              <?php if (!empty($styles)): ?>
                <div class="mb-2">
                  <?php foreach (array_slice($styles, 0, 3) as $style): ?>
                    <span class="fishing-badge"><?= e($fishingStyleLabels[$style] ?? ucfirst($style)) ?></span>
                  <?php endforeach; ?>
                  <?php if (count($styles) > 3): ?>
                    <span class="fishing-badge">+<?= count($styles) - 3 ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              
              <div class="small text-muted">
                <?= $club['member_count'] ?> member<?= $club['member_count'] != 1 ? 's' : '' ?>
              </div>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php member_shell_end(); ?>
