<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout/member_shell.php';

require_login();

$userId = current_user_id();
$errors = [];
$success = false;
$newClubId = null;

$stmt = $pdo->prepare("SELECT c.id, c.name, c.slug FROM clubs c JOIN club_admins ca ON c.id = ca.club_id WHERE ca.user_id = ? AND ca.admin_role = 'owner'");
$stmt->execute([$userId]);
$existingClub = $stmt->fetch();

if ($existingClub) {
  header('Location: /public/dashboard.php');
  exit;
}

$stmt = $pdo->prepare("SELECT cm.id FROM club_members cm WHERE cm.user_id = ? AND cm.membership_status IN ('active', 'pending')");
$stmt->execute([$userId]);
$existingMembership = $stmt->fetch();

if ($existingMembership) {
  header('Location: /public/dashboard.php');
  exit;
}

$fishingStyleOptions = [
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

$clubTypeOptions = [
  'angling_club' => 'Angling Club',
  'syndicate' => 'Syndicate',
  'commercial_fishery' => 'Commercial Fishery',
  'angling_guide' => 'Angling Guide',
  'charter_boat' => 'Charter Boat',
];

$formData = [
  'name' => '',
  'club_type' => 'angling_club',
  'tagline' => '',
  'address_line1' => '',
  'address_line2' => '',
  'town' => '',
  'county' => '',
  'postcode' => '',
  'country' => 'United Kingdom',
  'fishing_styles' => [],
  'about_text' => '',
  'contact_email' => '',
];

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $formData['name'] = trim($_POST['name'] ?? '');
  $formData['club_type'] = trim($_POST['club_type'] ?? 'angling_club');
  $formData['tagline'] = trim($_POST['tagline'] ?? '');
  $formData['address_line1'] = trim($_POST['address_line1'] ?? '');
  $formData['address_line2'] = trim($_POST['address_line2'] ?? '');
  $formData['town'] = trim($_POST['town'] ?? '');
  $formData['county'] = trim($_POST['county'] ?? '');
  $formData['postcode'] = trim($_POST['postcode'] ?? '');
  $formData['country'] = trim($_POST['country'] ?? 'United Kingdom');
  $formData['fishing_styles'] = $_POST['fishing_styles'] ?? [];
  $formData['about_text'] = trim($_POST['about_text'] ?? '');
  $formData['contact_email'] = trim($_POST['contact_email'] ?? '');

  if ($formData['name'] === '') {
    $errors[] = "Club name is required.";
  }

  if ($formData['address_line1'] === '') {
    $errors[] = "Address line 1 is required.";
  }

  if ($formData['town'] === '') {
    $errors[] = "Town is required.";
  }

  if ($formData['postcode'] === '') {
    $errors[] = "Postcode is required.";
  }

  if (empty($formData['fishing_styles'])) {
    $errors[] = "Please select at least one fishing style.";
  }

  if (!$errors) {
    try {
      $pdo->beginTransaction();

      $slug = generate_slug($formData['name']);
      $baseSlug = $slug;
      $counter = 1;
      $checkStmt = $pdo->prepare("SELECT id FROM clubs WHERE slug = ?");
      $checkStmt->execute([$slug]);
      while ($checkStmt->fetch()) {
        $slug = $baseSlug . '-' . $counter++;
        $checkStmt->execute([$slug]);
      }

      $today = date('Y-m-d');
      $trialEnd = date('Y-m-d', strtotime('+30 days'));

      $fishingStylesJson = json_encode($formData['fishing_styles']);

      $locationText = implode(', ', array_filter([
        $formData['town'],
        $formData['county'],
        $formData['postcode']
      ]));

      $stmt = $pdo->prepare("
        INSERT INTO clubs (
          name, slug, contact_email, about_text,
          address_line1, address_line2, town, county, postcode, country,
          location_text, city, fishing_styles,
          trial_start_date, trial_end_date, access_until
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([
        $formData['name'],
        $slug,
        $formData['contact_email'] ?: null,
        $formData['about_text'] ?: null,
        $formData['address_line1'],
        $formData['address_line2'] ?: null,
        $formData['town'],
        $formData['county'] ?: null,
        $formData['postcode'],
        $formData['country'],
        $locationText,
        $formData['town'],
        $fishingStylesJson,
        $today,
        $trialEnd,
        $trialEnd
      ]);

      $newClubId = $pdo->lastInsertId();
      $newClubSlug = $slug;

      $stmt = $pdo->prepare("
        INSERT INTO club_admins (club_id, user_id, admin_role)
        VALUES (?, ?, 'owner')
      ");
      $stmt->execute([$newClubId, $userId]);

      $pdo->commit();
      $success = true;

    } catch (Throwable $ex) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = "Failed to create club: " . $ex->getMessage();
    }
  }
}

member_shell_start($pdo, ['title' => 'Create Club', 'page' => 'create_club', 'section' => 'Main']);
?>
<style>
  .fishing-style-check {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 8px;
    transition: background 0.2s;
  }
  .fishing-style-check:hover {
    background: #e9ecef;
  }
  .fishing-style-check input:checked + label {
    font-weight: 600;
    color: #0d6efd;
  }
</style>

<div class="row justify-content-center">
  <div class="col-lg-8">
    
    <?php if ($success): ?>
      <div class="card shadow">
        <div class="card-body text-center py-5">
          <div class="display-1 mb-3">🎉</div>
          <h2 class="mb-3">Club Created Successfully!</h2>
          <p class="text-muted mb-4">Your club "<strong><?= e($formData['name']) ?></strong>" is now ready. You are the club owner and administrator.</p>
          
          <div class="mb-4">
            <label class="form-label text-muted">Share your club's public page:</label>
            <div class="input-group mx-auto" style="max-width: 500px;">
              <input type="text" class="form-control text-center" 
                     value="<?= e((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/public/club.php?slug=' . $newClubSlug) ?>" 
                     readonly onclick="this.select()" id="clubUrl">
              <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('clubUrl').value); this.textContent='Copied!';">Copy</button>
            </div>
          </div>
          
          <div class="d-flex justify-content-center gap-3">
            <a href="/public/club.php?slug=<?= e($newClubSlug) ?>" class="btn btn-outline-primary btn-lg">View Club Page</a>
            <a href="/public/dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
          </div>
        </div>
      </div>
    <?php else: ?>
      
      <div class="d-flex align-items-center mb-4">
        <a href="/public/dashboard.php" class="btn btn-outline-secondary me-3">&larr; Back</a>
        <h1 class="mb-0">Create a New Club</h1>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="card shadow mb-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Club Details</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Type of Entity <span class="text-danger">*</span></label>
              <select name="club_type" class="form-select" required>
                <?php foreach ($clubTypeOptions as $value => $label): ?>
                  <option value="<?= e($value) ?>" <?= $formData['club_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= e($formData['name']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Tagline</label>
              <input type="text" name="tagline" class="form-control" value="<?= e($formData['tagline']) ?>" placeholder="e.g. Expert Brown Trout Guiding on the River Boyne">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Contact Email</label>
              <input type="email" name="contact_email" class="form-control" value="<?= e($formData['contact_email']) ?>" placeholder="Optional - public contact email">
            </div>
            
            <div class="mb-3">
              <label class="form-label">About the Club</label>
              <textarea name="about_text" class="form-control" rows="3" placeholder="Tell people about your club..."><?= e($formData['about_text']) ?></textarea>
            </div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Club Address</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Address Line 1 <span class="text-danger">*</span></label>
              <input type="text" name="address_line1" class="form-control" value="<?= e($formData['address_line1']) ?>" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Address Line 2</label>
              <input type="text" name="address_line2" class="form-control" value="<?= e($formData['address_line2']) ?>">
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Town/City <span class="text-danger">*</span></label>
                <input type="text" name="town" class="form-control" value="<?= e($formData['town']) ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">County</label>
                <input type="text" name="county" class="form-control" value="<?= e($formData['county']) ?>">
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Postcode <span class="text-danger">*</span></label>
                <input type="text" name="postcode" class="form-control" value="<?= e($formData['postcode']) ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Country</label>
                <input type="text" name="country" class="form-control" value="<?= e($formData['country']) ?>">
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Fishing Styles <span class="text-danger">*</span></h5>
            <small class="text-muted">Select all types of fishing your club offers</small>
          </div>
          <div class="card-body">
            <div class="row">
              <?php foreach ($fishingStyleOptions as $value => $label): ?>
                <div class="col-md-6">
                  <div class="fishing-style-check">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" 
                             name="fishing_styles[]" 
                             value="<?= e($value) ?>" 
                             id="style_<?= e($value) ?>"
                             <?= in_array($value, $formData['fishing_styles']) ? 'checked' : '' ?>>
                      <label class="form-check-label" for="style_<?= e($value) ?>">
                        <?= e($label) ?>
                      </label>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg">Create Club</button>
        </div>
      </form>

    <?php endif; ?>
    
  </div>
</div>

<?php member_shell_end(); ?>
