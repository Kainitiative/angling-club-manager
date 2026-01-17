<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/image_upload.php';

require_login();

$userId = current_user_id();
$clubId = (int)($_GET['club_id'] ?? 0);
$message = '';
$messageType = '';

if (!$clubId) {
  http_response_code(400);
  exit('Club ID required');
}

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
$stmt->execute([$clubId]);
$club = $stmt->fetch();

if (!$club) {
  http_response_code(404);
  exit('Club not found');
}

$stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
$stmt->execute([$clubId, $userId]);
$adminRow = $stmt->fetch();

if (!$adminRow) {
  http_response_code(403);
  exit('You are not an admin of this club');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $logoUrl = '';
    
    if ($name === '') {
      $message = 'Sponsor name is required.';
      $messageType = 'danger';
    } else {
      if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
          $uploadDir = __DIR__ . '/../../uploads/sponsors';
          if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
          }
          $result = processLogoUpload($_FILES['logo'], $uploadDir, 300, 85);
          $logoUrl = $result['url'];
        } catch (Exception $e) {
          $message = 'Logo upload failed: ' . $e->getMessage();
          $messageType = 'danger';
        }
      }
      
      if ($messageType !== 'danger') {
        $stmt = $pdo->prepare("
          INSERT INTO sponsors (club_id, name, company, logo_url, website, description, display_order)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$clubId, $name, $company ?: null, $logoUrl ?: null, $website ?: null, $description ?: null, $displayOrder]);
        $message = 'Sponsor added successfully.';
        $messageType = 'success';
      }
    }
  }
  
  if ($action === 'update' && isset($_POST['sponsor_id'])) {
    $sponsorId = (int)$_POST['sponsor_id'];
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    
    if ($name === '') {
      $message = 'Sponsor name is required.';
      $messageType = 'danger';
    } else {
      $stmt = $pdo->prepare("SELECT logo_url FROM sponsors WHERE id = ? AND club_id = ?");
      $stmt->execute([$sponsorId, $clubId]);
      $existing = $stmt->fetch();
      $logoUrl = $existing['logo_url'] ?? '';
      
      if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
          $uploadDir = __DIR__ . '/../../uploads/sponsors';
          if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
          }
          $result = processLogoUpload($_FILES['logo'], $uploadDir, 300, 85);
          $logoUrl = $result['url'];
        } catch (Exception $e) {
          $message = 'Logo upload failed: ' . $e->getMessage();
          $messageType = 'danger';
        }
      }
      
      if ($messageType !== 'danger') {
        $stmt = $pdo->prepare("
          UPDATE sponsors SET name = ?, company = ?, logo_url = ?, website = ?, description = ?, display_order = ?, updated_at = CURRENT_TIMESTAMP
          WHERE id = ? AND club_id = ?
        ");
        $stmt->execute([$name, $company ?: null, $logoUrl ?: null, $website ?: null, $description ?: null, $displayOrder, $sponsorId, $clubId]);
        $message = 'Sponsor updated successfully.';
        $messageType = 'success';
      }
    }
  }
  
  if ($action === 'delete' && isset($_POST['sponsor_id'])) {
    $sponsorId = (int)$_POST['sponsor_id'];
    $stmt = $pdo->prepare("DELETE FROM sponsors WHERE id = ? AND club_id = ?");
    $stmt->execute([$sponsorId, $clubId]);
    $message = 'Sponsor removed.';
    $messageType = 'info';
  }
}

$stmt = $pdo->prepare("SELECT * FROM sponsors WHERE club_id = ? ORDER BY display_order, name");
$stmt->execute([$clubId]);
$sponsors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Sponsors & Supporters - ' . e($club['name']);
include __DIR__ . '/../../app/layout/admin_shell.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="mb-1">Sponsors & Supporters</h2>
    <p class="text-muted mb-0">Manage sponsors and supporters for <?= e($club['name']) ?></p>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSponsorModal">
    <i class="bi bi-plus-lg me-1"></i> Add Sponsor
  </button>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= e($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (empty($sponsors)): ?>
  <div class="text-center py-5">
    <div class="mb-3"><i class="bi bi-building text-muted" style="font-size: 3rem;"></i></div>
    <h5>No Sponsors Yet</h5>
    <p class="text-muted">Add sponsors and supporters to showcase them on your club's public page.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSponsorModal">
      <i class="bi bi-plus-lg me-1"></i> Add Your First Sponsor
    </button>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($sponsors as $sponsor): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
          <?php if ($sponsor['logo_url']): ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
              <img src="<?= e($sponsor['logo_url']) ?>" alt="<?= e($sponsor['name']) ?>" class="img-fluid" style="max-height: 120px; max-width: 90%;">
            </div>
          <?php else: ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
              <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
            </div>
          <?php endif; ?>
          <div class="card-body">
            <h5 class="card-title mb-1"><?= e($sponsor['name']) ?></h5>
            <?php if ($sponsor['company']): ?>
              <p class="text-muted small mb-2"><?= e($sponsor['company']) ?></p>
            <?php endif; ?>
            <?php if ($sponsor['description']): ?>
              <p class="card-text small"><?= e(mb_strimwidth($sponsor['description'], 0, 100, '...')) ?></p>
            <?php endif; ?>
            <?php if ($sponsor['website']): ?>
              <a href="<?= e($sponsor['website']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-globe me-1"></i> Website
              </a>
            <?php endif; ?>
          </div>
          <div class="card-footer bg-transparent border-0">
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-outline-secondary flex-grow-1" 
                      data-bs-toggle="modal" 
                      data-bs-target="#editSponsor<?= $sponsor['id'] ?>">
                <i class="bi bi-pencil me-1"></i> Edit
              </button>
              <form method="post" class="d-inline" onsubmit="return confirm('Remove this sponsor?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="sponsor_id" value="<?= $sponsor['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal fade" id="editSponsor<?= $sponsor['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="sponsor_id" value="<?= $sponsor['id'] ?>">
              <div class="modal-header">
                <h5 class="modal-title">Edit Sponsor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label">Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" value="<?= e($sponsor['name']) ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Company</label>
                  <input type="text" name="company" class="form-control" value="<?= e($sponsor['company'] ?? '') ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Logo</label>
                  <?php if ($sponsor['logo_url']): ?>
                    <div class="mb-2">
                      <img src="<?= e($sponsor['logo_url']) ?>" alt="Current logo" style="max-height: 60px;">
                    </div>
                  <?php endif; ?>
                  <input type="file" name="logo" class="form-control" accept="image/*">
                  <div class="form-text">Leave empty to keep current logo</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Website</label>
                  <input type="url" name="website" class="form-control" value="<?= e($sponsor['website'] ?? '') ?>" placeholder="https://">
                </div>
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea name="description" class="form-control" rows="3"><?= e($sponsor['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label">Display Order</label>
                  <input type="number" name="display_order" class="form-control" value="<?= (int)$sponsor['display_order'] ?>">
                  <div class="form-text">Lower numbers appear first</div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="modal fade" id="addSponsorModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title">Add Sponsor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="Sponsor or supporter name">
          </div>
          <div class="mb-3">
            <label class="form-label">Company</label>
            <input type="text" name="company" class="form-control" placeholder="Company or organization name">
          </div>
          <div class="mb-3">
            <label class="form-label">Logo</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <div class="form-text">Recommended: PNG or JPG, max 2MB</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-control" placeholder="https://example.com">
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the sponsor..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" class="form-control" value="0">
            <div class="form-text">Lower numbers appear first</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Sponsor</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../app/layout/admin_footer.php'; ?>
