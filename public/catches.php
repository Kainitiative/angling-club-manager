<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/image_upload.php';

$slug = $_GET['slug'] ?? '';
$message = '';
$messageType = '';

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

$userId = current_user_id();
$isLoggedIn = (bool)$userId;
$isMember = false;
$isAdmin = false;

if ($userId) {
  $stmt = $pdo->prepare("SELECT * FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
  $stmt->execute([$club['id'], $userId]);
  $memberRow = $stmt->fetch();
  $isMember = (bool)$memberRow;
  
  $stmt = $pdo->prepare("SELECT * FROM club_admins WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$club['id'], $userId]);
  $isAdmin = (bool)$stmt->fetch();
}

if (!$isMember && !$isAdmin) {
  if (!$userId) {
    header('Location: /?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
  }
  http_response_code(403);
  exit('Access restricted to club members only.');
}

$stmt = $pdo->prepare("SELECT * FROM fish_species ORDER BY display_order, name");
$stmt->execute();
$fishSpecies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$validSpeciesNames = array_column($fishSpecies, 'name');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($isMember || $isAdmin)) {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'log_catch') {
    $species = trim($_POST['species'] ?? '');
    $customSpecies = trim($_POST['custom_species'] ?? '');
    $weightKg = $_POST['weight_kg'] ? (float)$_POST['weight_kg'] : null;
    $lengthCm = $_POST['length_cm'] ? (float)$_POST['length_cm'] : null;
    $location = trim($_POST['location_description'] ?? '');
    $catchDate = $_POST['catch_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    $photoUrl = null;
    
    if ($species === 'Other' && $customSpecies) {
      $species = $customSpecies;
    }
    
    if (!$species) {
      $message = 'Please select a species';
      $messageType = 'danger';
    } elseif ($_POST['species'] !== 'Other' && !in_array($species, $validSpeciesNames, true)) {
      $message = 'Invalid species selected';
      $messageType = 'danger';
    } elseif ($_POST['species'] === 'Other' && !$customSpecies) {
      $message = 'Please enter the fish name';
      $messageType = 'danger';
    } elseif ($_POST['species'] === 'Other' && strlen($customSpecies) > 100) {
      $message = 'Fish name is too long (max 100 characters)';
      $messageType = 'danger';
    } else {
      if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
          $uploadDir = __DIR__ . '/../uploads/catches';
          $result = processCatchUpload($_FILES['photo'], $uploadDir, 800, 80);
          $photoUrl = $result['url'];
        } catch (Exception $e) {
          $message = 'Photo upload failed: ' . $e->getMessage();
          $messageType = 'warning';
        }
      }
      
      $stmt = $pdo->prepare("
        INSERT INTO catch_logs (club_id, user_id, species, weight_kg, length_cm, location_description, catch_date, photo_url, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$club['id'], $userId, $species, $weightKg, $lengthCm, $location ?: null, $catchDate, $photoUrl, $notes ?: null]);
      $catchId = (int)$pdo->lastInsertId();
      
      if ($weightKg) {
        $stmt = $pdo->prepare("SELECT * FROM personal_bests WHERE club_id = ? AND user_id = ? AND species = ?");
        $stmt->execute([$club['id'], $userId, $species]);
        $pb = $stmt->fetch();
        
        if (!$pb || $weightKg > (float)$pb['weight_kg']) {
          if ($pb) {
            $stmt = $pdo->prepare("UPDATE personal_bests SET weight_kg = ?, catch_log_id = ?, achieved_date = ? WHERE club_id = ? AND user_id = ? AND species = ?");
            $stmt->execute([$weightKg, $catchId, $catchDate, $club['id'], $userId, $species]);
          } else {
            $stmt = $pdo->prepare("INSERT INTO personal_bests (club_id, user_id, species, weight_kg, catch_log_id, achieved_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$club['id'], $userId, $species, $weightKg, $catchId, $catchDate]);
          }
          
          $stmt = $pdo->prepare("UPDATE catch_logs SET is_personal_best = 1 WHERE id = ?");
          $stmt->execute([$catchId]);
          
          $message = 'Catch logged - New Personal Best!';
          $messageType = 'success';
        }
        
        $stmt = $pdo->prepare("SELECT * FROM club_records WHERE club_id = ? AND species = ?");
        $stmt->execute([$club['id'], $species]);
        $record = $stmt->fetch();
        
        if (!$record || $weightKg > (float)$record['weight_kg']) {
          if ($record) {
            $stmt = $pdo->prepare("UPDATE club_records SET weight_kg = ?, user_id = ?, catch_log_id = ?, achieved_date = ? WHERE club_id = ? AND species = ?");
            $stmt->execute([$weightKg, $userId, $catchId, $catchDate, $club['id'], $species]);
          } else {
            $stmt = $pdo->prepare("INSERT INTO club_records (club_id, species, weight_kg, user_id, catch_log_id, achieved_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$club['id'], $species, $weightKg, $userId, $catchId, $catchDate]);
          }
          
          $stmt = $pdo->prepare("UPDATE catch_logs SET is_club_record = 1 WHERE id = ?");
          $stmt->execute([$catchId]);
          
          $message = 'Catch logged - New Club Record!';
          $messageType = 'success';
        }
      }
      
      if (!$message) {
        $message = 'Catch logged successfully!';
        $messageType = 'success';
      }
    }
  } elseif ($action === 'delete_catch') {
    $catchId = (int)($_POST['catch_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM catch_logs WHERE id = ? AND user_id = ?");
    $stmt->execute([$catchId, $userId]);
    $message = 'Catch removed';
    $messageType = 'info';
  } elseif ($action === 'set_catch_of_month' && $isAdmin) {
    $catchId = (int)($_POST['catch_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE catch_logs SET is_catch_of_month = 0 WHERE club_id = ?");
    $stmt->execute([$club['id']]);
    $stmt = $pdo->prepare("UPDATE catch_logs SET is_catch_of_month = 1 WHERE id = ? AND club_id = ?");
    $stmt->execute([$catchId, $club['id']]);
    $message = 'Catch of the Month updated!';
    $messageType = 'success';
  } elseif ($action === 'clear_catch_of_month' && $isAdmin) {
    $stmt = $pdo->prepare("UPDATE catch_logs SET is_catch_of_month = 0 WHERE club_id = ?");
    $stmt->execute([$club['id']]);
    $message = 'Catch of the Month cleared';
    $messageType = 'info';
  }
}

$stmt = $pdo->prepare("
  SELECT cl.*, u.name as angler_name, u.profile_picture_url
  FROM catch_logs cl
  JOIN users u ON cl.user_id = u.id
  WHERE cl.club_id = ?
  ORDER BY cl.catch_date DESC, cl.created_at DESC
  LIMIT 50
");
$stmt->execute([$club['id']]);
$recentCatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT cl.*, u.name as angler_name, u.profile_picture_url
  FROM catch_logs cl
  JOIN users u ON cl.user_id = u.id
  WHERE cl.club_id = ? AND cl.is_catch_of_month = 1
  ORDER BY cl.catch_date DESC
  LIMIT 1
");
$stmt->execute([$club['id']]);
$catchOfMonth = $stmt->fetch();

$stmt = $pdo->prepare("
  SELECT cr.*, u.name as angler_name
  FROM club_records cr
  JOIN users u ON cr.user_id = u.id
  WHERE cr.club_id = ?
  ORDER BY cr.species
");
$stmt->execute([$club['id']]);
$clubRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

$myCatches = [];
$myPersonalBests = [];
if ($userId && ($isMember || $isAdmin)) {
  $stmt = $pdo->prepare("
    SELECT * FROM catch_logs 
    WHERE club_id = ? AND user_id = ?
    ORDER BY catch_date DESC
    LIMIT 20
  ");
  $stmt->execute([$club['id'], $userId]);
  $myCatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  $stmt = $pdo->prepare("SELECT * FROM personal_bests WHERE club_id = ? AND user_id = ? ORDER BY species");
  $stmt->execute([$club['id'], $userId]);
  $myPersonalBests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catch Log - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .catch-card { transition: transform 0.2s; }
    .catch-card:hover { transform: translateY(-2px); }
    .catch-photo { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; }
    .badge-pb { background: linear-gradient(135deg, #ffd700, #ffed4a); color: #333; }
    .badge-record { background: linear-gradient(135deg, #ff6b6b, #ee5a5a); color: white; }
    .species-icon { font-size: 2rem; }
    .leaderboard-item { border-left: 3px solid #1e3a5f; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/club.php?slug=<?= e($club['slug']) ?>">Back to Club</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="mb-1">Catch Log</h1>
      <p class="text-muted mb-0"><?= e($club['name']) ?></p>
    </div>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="row">
    <div class="col-lg-8">
      
      <?php if ($catchOfMonth): ?>
        <div class="card mb-4 border-warning">
          <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Catch of the Month</h5>
            <?php if ($isAdmin): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="action" value="clear_catch_of_month">
                <button type="submit" class="btn btn-sm btn-outline-dark">Clear</button>
              </form>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="row align-items-center">
              <?php if ($catchOfMonth['photo_url']): ?>
                <div class="col-md-4">
                  <img src="<?= e($catchOfMonth['photo_url']) ?>" alt="" class="catch-photo">
                </div>
              <?php endif; ?>
              <div class="col">
                <h4><?= e($catchOfMonth['species']) ?></h4>
                <p class="fs-3 fw-bold text-primary mb-1">
                  <?= number_format((float)$catchOfMonth['weight_kg'], 3) ?> kg
                  <?php if ($catchOfMonth['length_cm']): ?>
                    <small class="text-muted">/ <?= number_format((float)$catchOfMonth['length_cm'], 1) ?> cm</small>
                  <?php endif; ?>
                </p>
                <p class="mb-0">
                  Caught by <strong><?= e($catchOfMonth['angler_name']) ?></strong>
                  on <?= date('j F Y', strtotime($catchOfMonth['catch_date'])) ?>
                </p>
                <?php if ($catchOfMonth['location_description']): ?>
                  <p class="text-muted mb-0"><small><?= e($catchOfMonth['location_description']) ?></small></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
      
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">Recent Catches</h5>
        </div>
        <div class="card-body">
          <?php if (empty($recentCatches)): ?>
            <p class="text-muted text-center mb-0">No catches logged yet. Be the first!</p>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($recentCatches as $catch): ?>
                <div class="col-md-6">
                  <div class="card catch-card h-100">
                    <?php if ($catch['photo_url']): ?>
                      <img src="<?= e($catch['photo_url']) ?>" alt="" class="card-img-top" style="height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0"><?= e($catch['species']) ?></h6>
                        <div>
                          <?php if ($catch['is_personal_best']): ?>
                            <span class="badge badge-pb">PB</span>
                          <?php endif; ?>
                          <?php if ($catch['is_club_record']): ?>
                            <span class="badge badge-record">Record</span>
                          <?php endif; ?>
                          <?php if (!empty($catch['is_catch_of_month'])): ?>
                            <span class="badge bg-warning text-dark">COTM</span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <?php if ($catch['weight_kg']): ?>
                        <p class="fs-5 fw-bold text-primary mb-1"><?= number_format((float)$catch['weight_kg'], 3) ?> kg</p>
                      <?php endif; ?>
                      <p class="small text-muted mb-2">
                        <?= e($catch['angler_name']) ?> &bull; <?= date('j M Y', strtotime($catch['catch_date'])) ?>
                      </p>
                      <?php if ($isAdmin): ?>
                        <form method="post" class="d-inline">
                          <input type="hidden" name="action" value="set_catch_of_month">
                          <input type="hidden" name="catch_id" value="<?= $catch['id'] ?>">
                          <button type="submit" class="btn btn-outline-warning btn-sm" title="Set as Catch of the Month">Set COTM</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      
    </div>
    
    <div class="col-lg-4">
      
      <?php if ($isMember || $isAdmin): ?>
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Log a Catch</h5>
          </div>
          <div class="card-body">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="log_catch">
              
              <div class="mb-3">
                <label for="species" class="form-label">Species <span class="text-danger">*</span></label>
                <select class="form-select" id="species" name="species" required onchange="toggleCustomSpecies(this)">
                  <option value="">Select species...</option>
                  <?php 
                  $currentCategory = '';
                  foreach ($fishSpecies as $sp): 
                    if ($sp['category'] !== $currentCategory):
                      if ($currentCategory) echo '</optgroup>';
                      $currentCategory = $sp['category'];
                      echo '<optgroup label="' . e($currentCategory) . ' Fish">';
                    endif;
                  ?>
                    <option value="<?= e($sp['name']) ?>"><?= e($sp['name']) ?></option>
                  <?php endforeach; ?>
                  <?php if ($currentCategory) echo '</optgroup>'; ?>
                </select>
              </div>
              
              <div class="mb-3" id="custom-species-container" style="display: none;">
                <label for="custom_species" class="form-label">Fish Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="custom_species" name="custom_species" placeholder="Enter fish name" maxlength="100">
              </div>
              
              <div class="row mb-3">
                <div class="col-6">
                  <label for="weight_kg" class="form-label">Weight (kg)</label>
                  <input type="number" class="form-control" id="weight_kg" name="weight_kg" step="0.001" min="0" placeholder="0.000">
                </div>
                <div class="col-6">
                  <label for="length_cm" class="form-label">Length (cm)</label>
                  <input type="number" class="form-control" id="length_cm" name="length_cm" step="0.1" min="0" placeholder="0.0">
                </div>
              </div>
              
              <div class="mb-3">
                <label for="catch_date" class="form-label">Date</label>
                <input type="date" class="form-control" id="catch_date" name="catch_date" value="<?= date('Y-m-d') ?>">
              </div>
              
              <div class="mb-3">
                <label for="location_description" class="form-label">Location</label>
                <input type="text" class="form-control" id="location_description" name="location_description" placeholder="e.g. North shore, Peg 5">
              </div>
              
              <div class="mb-3">
                <label for="photo" class="form-label">Photo</label>
                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
              </div>
              
              <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Bait, conditions, etc."></textarea>
              </div>
              
              <button type="submit" class="btn btn-primary w-100">Log Catch</button>
            </form>
          </div>
        </div>
      <?php elseif (!$isLoggedIn): ?>
        <div class="card mb-4">
          <div class="card-body text-center">
            <h5>Log Your Catches</h5>
            <p class="text-muted">Sign in and join the club to log your catches.</p>
            <a href="/" class="btn btn-primary">Sign In</a>
          </div>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($clubRecords)): ?>
        <div class="card mb-4">
          <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Club Records</h5>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              <?php foreach ($clubRecords as $record): ?>
                <div class="list-group-item leaderboard-item">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <strong><?= e($record['species']) ?></strong><br>
                      <small class="text-muted"><?= e($record['angler_name']) ?></small>
                    </div>
                    <div class="text-end">
                      <span class="fw-bold text-primary"><?= number_format((float)$record['weight_kg'], 3) ?> kg</span><br>
                      <small class="text-muted"><?= date('M Y', strtotime($record['achieved_date'])) ?></small>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($myPersonalBests)): ?>
        <div class="card mb-4">
          <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">My Personal Bests</h5>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              <?php foreach ($myPersonalBests as $pb): ?>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <span><?= e($pb['species']) ?></span>
                    <strong><?= number_format((float)$pb['weight_kg'], 3) ?> kg</strong>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
      
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleCustomSpecies(select) {
  var container = document.getElementById('custom-species-container');
  var input = document.getElementById('custom_species');
  if (select.value === 'Other') {
    container.style.display = 'block';
    input.required = true;
  } else {
    container.style.display = 'none';
    input.required = false;
    input.value = '';
  }
}
</script>
</body>
</html>
