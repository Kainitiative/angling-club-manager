<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

try {

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/image_upload.php';

require_login();

$userId = current_user_id();
$clubId = (int)($_GET['club_id'] ?? 0);
$tab = $_GET['tab'] ?? 'branding';
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

$stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
$stmt->execute([$clubId, $userId]);
$memberRow = $stmt->fetch();
$committeeRole = $memberRow ? ($memberRow['committee_role'] ?? null) : null;

$canEditProfile = $adminRow || in_array($committeeRole, ['chairperson', 'pro']);

if (!$canEditProfile) {
  http_response_code(403);
  exit('You do not have permission to edit this club profile');
}

$stmt = $pdo->prepare("SELECT * FROM club_profile_settings WHERE club_id = ?");
$stmt->execute([$clubId]);
$settings = $stmt->fetch();

if (!$settings) {
  $stmt = $pdo->prepare("INSERT INTO club_profile_settings (club_id) VALUES (?)");
  $stmt->execute([$clubId]);
  $stmt = $pdo->prepare("SELECT * FROM club_profile_settings WHERE club_id = ?");
  $stmt->execute([$clubId]);
  $settings = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT * FROM club_membership_fees WHERE club_id = ? ORDER BY display_order, id");
$stmt->execute([$clubId]);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM club_perks WHERE club_id = ? ORDER BY display_order, id");
$stmt->execute([$clubId]);
$perks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM club_gallery WHERE club_id = ? ORDER BY display_order, id");
$stmt->execute([$clubId]);
$gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'save_branding') {
    $heroTitle = trim($_POST['hero_title'] ?? '');
    $heroTagline = trim($_POST['hero_tagline'] ?? '');
    $heroImageUrl = trim($_POST['hero_image_url'] ?? '');
    $primaryColor = $_POST['primary_color'] ?? '#1e3a5f';
    $secondaryColor = $_POST['secondary_color'] ?? '#2d5a87';
    $logoUrl = $club['logo_url'] ?? '';
    
    if ($primaryColor && !preg_match('/^#[0-9A-Fa-f]{6}$/', $primaryColor)) {
      $primaryColor = '#1e3a5f';
    }
    if ($secondaryColor && !preg_match('/^#[0-9A-Fa-f]{6}$/', $secondaryColor)) {
      $secondaryColor = '#2d5a87';
    }
    if ($heroImageUrl && !preg_match('/^https?:\/\//i', $heroImageUrl)) {
      $heroImageUrl = '';
    }
    
    if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
      try {
        $uploadDir = __DIR__ . '/../../uploads/logos';
        $result = processLogoUpload($_FILES['logo_file'], $uploadDir, 200, 85);
        $logoUrl = $result['url'];
      } catch (Exception $e) {
        $message = 'Logo upload failed: ' . $e->getMessage();
        $messageType = 'danger';
      }
    }
    
    if (!$message) {
      $stmt = $pdo->prepare("
        UPDATE club_profile_settings 
        SET hero_title = ?, hero_tagline = ?, hero_image_url = ?, primary_color = ?, secondary_color = ?
        WHERE club_id = ?
      ");
      $stmt->execute([$heroTitle ?: null, $heroTagline ?: null, $heroImageUrl ?: null, $primaryColor, $secondaryColor, $clubId]);
      
      $stmt = $pdo->prepare("UPDATE clubs SET logo_url = ? WHERE id = ?");
      $stmt->execute([$logoUrl ?: null, $clubId]);
      
      $message = 'Branding settings saved.';
      $messageType = 'success';
    }
    $tab = 'branding';
    
  } elseif ($action === 'save_content') {
    $aboutText = trim($_POST['about_text'] ?? '');
    $whyJoinText = trim($_POST['why_join_text'] ?? '');
    
    $stmt = $pdo->prepare("UPDATE clubs SET about_text = ? WHERE id = ?");
    $stmt->execute([$aboutText ?: null, $clubId]);
    
    $stmt = $pdo->prepare("UPDATE club_profile_settings SET why_join_text = ? WHERE club_id = ?");
    $stmt->execute([$whyJoinText ?: null, $clubId]);
    
    $message = 'Content saved.';
    $messageType = 'success';
    $tab = 'content';
    
  } elseif ($action === 'save_contact') {
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $facebookUrl = trim($_POST['facebook_url'] ?? '');
    $instagramUrl = trim($_POST['instagram_url'] ?? '');
    $twitterUrl = trim($_POST['twitter_url'] ?? '');
    $websiteUrl = trim($_POST['website_url'] ?? '');
    
    $stmt = $pdo->prepare("
      UPDATE club_profile_settings 
      SET contact_email = ?, contact_phone = ?, facebook_url = ?, instagram_url = ?, twitter_url = ?, website_url = ?
      WHERE club_id = ?
    ");
    $stmt->execute([
      $contactEmail ?: null, $contactPhone ?: null, 
      $facebookUrl ?: null, $instagramUrl ?: null, $twitterUrl ?: null, $websiteUrl ?: null,
      $clubId
    ]);
    
    $message = 'Contact information saved.';
    $messageType = 'success';
    $tab = 'content';
    
  } elseif ($action === 'add_perk') {
    $perkText = trim($_POST['perk_text'] ?? '');
    if ($perkText) {
      $stmt = $pdo->prepare("SELECT MAX(display_order) FROM club_perks WHERE club_id = ?");
      $stmt->execute([$clubId]);
      $maxOrder = (int)$stmt->fetchColumn();
      
      $stmt = $pdo->prepare("INSERT INTO club_perks (club_id, perk_text, display_order) VALUES (?, ?, ?)");
      $stmt->execute([$clubId, $perkText, $maxOrder + 1]);
      $message = 'Perk added.';
      $messageType = 'success';
    }
    $tab = 'content';
    
  } elseif ($action === 'delete_perk') {
    $perkId = (int)($_POST['perk_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM club_perks WHERE id = ? AND club_id = ?");
    $stmt->execute([$perkId, $clubId]);
    $message = 'Perk removed.';
    $messageType = 'info';
    $tab = 'content';
    
  } elseif ($action === 'add_fee') {
    $feeName = trim($_POST['fee_name'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $billingPeriod = $_POST['billing_period'] ?? 'yearly';
    $description = trim($_POST['description'] ?? '');
    
    if ($feeName && $amount > 0) {
      $stmt = $pdo->prepare("SELECT MAX(display_order) FROM club_membership_fees WHERE club_id = ?");
      $stmt->execute([$clubId]);
      $maxOrder = (int)$stmt->fetchColumn();
      
      $stmt = $pdo->prepare("
        INSERT INTO club_membership_fees (club_id, fee_name, amount, billing_period, description, display_order)
        VALUES (?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $feeName, $amount, $billingPeriod, $description ?: null, $maxOrder + 1]);
      $message = 'Membership fee added.';
      $messageType = 'success';
    }
    $tab = 'fees';
    
  } elseif ($action === 'delete_fee') {
    $feeId = (int)($_POST['fee_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM club_membership_fees WHERE id = ? AND club_id = ?");
    $stmt->execute([$feeId, $clubId]);
    $message = 'Fee removed.';
    $messageType = 'info';
    $tab = 'fees';
    
  } elseif ($action === 'add_gallery') {
    $caption = trim($_POST['caption'] ?? '');
    $imageUrl = '';
    
    if (!empty($_FILES['gallery_file']['name']) && $_FILES['gallery_file']['error'] !== UPLOAD_ERR_NO_FILE) {
      try {
        $uploadDir = __DIR__ . '/../../uploads/gallery';
        $result = processGalleryUpload($_FILES['gallery_file'], $uploadDir, 1200, 85);
        $imageUrl = $result['url'];
      } catch (Exception $e) {
        $message = 'Image upload failed: ' . $e->getMessage();
        $messageType = 'danger';
      }
    }
    
    if ($imageUrl) {
      $stmt = $pdo->prepare("SELECT MAX(display_order) FROM club_gallery WHERE club_id = ?");
      $stmt->execute([$clubId]);
      $maxOrder = (int)$stmt->fetchColumn();
      
      $stmt = $pdo->prepare("
        INSERT INTO club_gallery (club_id, image_url, caption, display_order, uploaded_by)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $imageUrl, $caption ?: null, $maxOrder + 1, $userId]);
      $message = 'Photo added to gallery.';
      $messageType = 'success';
    }
    $tab = 'gallery';
    
  } elseif ($action === 'delete_gallery') {
    $galleryId = (int)($_POST['gallery_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM club_gallery WHERE id = ? AND club_id = ?");
    $stmt->execute([$galleryId, $clubId]);
    $message = 'Photo removed.';
    $messageType = 'info';
    $tab = 'gallery';
  }
  
  $stmt = $pdo->prepare("SELECT * FROM club_profile_settings WHERE club_id = ?");
  $stmt->execute([$clubId]);
  $settings = $stmt->fetch();
  
  $stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
  $stmt->execute([$clubId]);
  $club = $stmt->fetch();
  
  $stmt = $pdo->prepare("SELECT * FROM club_membership_fees WHERE club_id = ? ORDER BY display_order, id");
  $stmt->execute([$clubId]);
  $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  $stmt = $pdo->prepare("SELECT * FROM club_perks WHERE club_id = ? ORDER BY display_order, id");
  $stmt->execute([$clubId]);
  $perks = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  $stmt = $pdo->prepare("SELECT * FROM club_gallery WHERE club_id = ? ORDER BY display_order, id");
  $stmt->execute([$clubId]);
  $gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$billingPeriodLabels = [
  'one_time' => 'One-time',
  'monthly' => 'Monthly',
  'quarterly' => 'Quarterly',
  'yearly' => 'Yearly',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Club Profile - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .nav-pills .nav-link.active {
      background: #1e3a5f;
    }
    .color-preview {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      border: 2px solid #dee2e6;
    }
    .gallery-thumb {
      width: 120px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
    }
    .perk-item, .fee-item {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 8px;
    }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Ireland</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/club.php?slug=<?= e($club['slug']) ?>">View Club</a>
      <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
      <a class="btn btn-outline-light btn-sm" href="/public/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="mb-1">Edit Club Profile</h1>
      <p class="text-muted mb-0"><?= e($club['name']) ?></p>
    </div>
    <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-primary" target="_blank">Preview Profile</a>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <ul class="nav nav-pills mb-4">
    <li class="nav-item">
      <a class="nav-link <?= $tab === 'branding' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=branding">Branding</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab === 'content' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=content">Content</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab === 'fees' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=fees">Membership Fees</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab === 'gallery' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=gallery">Gallery</a>
    </li>
  </ul>

  <?php if ($tab === 'branding'): ?>
    <div class="card">
      <div class="card-header bg-white">
        <h5 class="mb-0">Branding & Colors</h5>
      </div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_branding">
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="logo_file" class="form-label">Club Logo</label>
                <?php if ($club['logo_url']): ?>
                  <div class="mb-2">
                    <img src="<?= e($club['logo_url']) ?>" alt="Current logo" style="max-width: 100px; max-height: 100px; border-radius: 8px; border: 1px solid #dee2e6;">
                    <span class="text-muted ms-2">Current logo</span>
                  </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="form-text">Upload an image (max 5MB). Will be resized to 200x200px.</div>
              </div>
              
              <div class="mb-3">
                <label for="hero_title" class="form-label">Hero Title</label>
                <input type="text" class="form-control" id="hero_title" name="hero_title" value="<?= e($settings['hero_title'] ?? '') ?>" placeholder="Welcome to Our Club">
              </div>
              
              <div class="mb-3">
                <label for="hero_tagline" class="form-label">Hero Tagline</label>
                <textarea class="form-control" id="hero_tagline" name="hero_tagline" rows="2" placeholder="Your welcoming message to visitors"><?= e($settings['hero_tagline'] ?? '') ?></textarea>
              </div>
              
              <div class="mb-3">
                <label for="hero_image_url" class="form-label">Hero Background Image URL</label>
                <input type="url" class="form-control" id="hero_image_url" name="hero_image_url" value="<?= e($settings['hero_image_url'] ?? '') ?>" placeholder="https://example.com/hero.jpg">
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="mb-3">
                <label for="primary_color" class="form-label">Primary Color</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="<?= e($settings['primary_color'] ?? '#1e3a5f') ?>">
                  <input type="text" class="form-control" style="width: 100px;" value="<?= e($settings['primary_color'] ?? '#1e3a5f') ?>" id="primary_color_text" onchange="document.getElementById('primary_color').value = this.value">
                </div>
              </div>
              
              <div class="mb-3">
                <label for="secondary_color" class="form-label">Secondary Color</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" value="<?= e($settings['secondary_color'] ?? '#2d5a87') ?>">
                  <input type="text" class="form-control" style="width: 100px;" value="<?= e($settings['secondary_color'] ?? '#2d5a87') ?>" id="secondary_color_text" onchange="document.getElementById('secondary_color').value = this.value">
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Preview</label>
                <div class="p-4 rounded text-white text-center" id="color_preview" style="background: linear-gradient(135deg, <?= e($settings['primary_color'] ?? '#1e3a5f') ?> 0%, <?= e($settings['secondary_color'] ?? '#2d5a87') ?> 100%);">
                  <h5><?= e($settings['hero_title'] ?: $club['name']) ?></h5>
                  <p class="mb-0 opacity-75"><?= e($settings['hero_tagline'] ?: 'Your club tagline here') ?></p>
                </div>
              </div>
            </div>
          </div>
          
          <button type="submit" class="btn btn-primary">Save Branding</button>
        </form>
      </div>
    </div>

  <?php elseif ($tab === 'content'): ?>
    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="mb-0">About & Why Join</h5>
          </div>
          <div class="card-body">
            <form method="post">
              <input type="hidden" name="action" value="save_content">
              
              <div class="mb-3">
                <label for="about_text" class="form-label">About Us</label>
                <textarea class="form-control" id="about_text" name="about_text" rows="5" placeholder="Tell visitors about your club..."><?= e($club['about_text'] ?? '') ?></textarea>
              </div>
              
              <div class="mb-3">
                <label for="why_join_text" class="form-label">Why Join Our Club?</label>
                <textarea class="form-control" id="why_join_text" name="why_join_text" rows="5" placeholder="Explain the benefits of joining..."><?= e($settings['why_join_text'] ?? '') ?></textarea>
              </div>
              
              <button type="submit" class="btn btn-primary">Save Content</button>
            </form>
          </div>
        </div>
        
        <div class="card mt-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Contact Information</h5>
          </div>
          <div class="card-body">
            <form method="post">
              <input type="hidden" name="action" value="save_contact">
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="contact_email" class="form-label">Public Email</label>
                  <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= e($settings['contact_email'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="contact_phone" class="form-label">Phone</label>
                  <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?= e($settings['contact_phone'] ?? '') ?>">
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="facebook_url" class="form-label">Facebook URL</label>
                  <input type="url" class="form-control" id="facebook_url" name="facebook_url" value="<?= e($settings['facebook_url'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="instagram_url" class="form-label">Instagram URL</label>
                  <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="<?= e($settings['instagram_url'] ?? '') ?>">
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="twitter_url" class="form-label">Twitter/X URL</label>
                  <input type="url" class="form-control" id="twitter_url" name="twitter_url" value="<?= e($settings['twitter_url'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="website_url" class="form-label">Website URL</label>
                  <input type="url" class="form-control" id="website_url" name="website_url" value="<?= e($settings['website_url'] ?? '') ?>">
                </div>
              </div>
              
              <button type="submit" class="btn btn-primary">Save Contact Info</button>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6 mb-4">
        <div class="card">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Membership Perks</h5>
          </div>
          <div class="card-body">
            <form method="post" class="mb-3">
              <input type="hidden" name="action" value="add_perk">
              <div class="input-group">
                <input type="text" class="form-control" name="perk_text" placeholder="e.g., Access to private waters" required>
                <button type="submit" class="btn btn-success">Add Perk</button>
              </div>
            </form>
            
            <?php if (empty($perks)): ?>
              <p class="text-muted text-center mb-0">No perks added yet.</p>
            <?php else: ?>
              <?php foreach ($perks as $perk): ?>
                <div class="perk-item d-flex justify-content-between align-items-center">
                  <span><?= e($perk['perk_text']) ?></span>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="delete_perk">
                    <input type="hidden" name="perk_id" value="<?= $perk['id'] ?>">
                    <button type="submit" class="btn btn-link btn-sm text-danger p-0">Remove</button>
                  </form>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($tab === 'fees'): ?>
    <div class="row">
      <div class="col-lg-5 mb-4">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="mb-0">Add Membership Fee</h5>
          </div>
          <div class="card-body">
            <form method="post">
              <input type="hidden" name="action" value="add_fee">
              
              <div class="mb-3">
                <label for="fee_name" class="form-label">Fee Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="fee_name" name="fee_name" required placeholder="e.g., Adult Membership">
              </div>
              
              <div class="row">
                <div class="col-6 mb-3">
                  <label for="amount" class="form-label">Amount (&euro;) <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                </div>
                <div class="col-6 mb-3">
                  <label for="billing_period" class="form-label">Billing Period</label>
                  <select class="form-select" id="billing_period" name="billing_period">
                    <option value="yearly">Yearly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="one_time">One-time</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="2" placeholder="What's included in this tier?"></textarea>
              </div>
              
              <button type="submit" class="btn btn-primary w-100">Add Fee</button>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-lg-7 mb-4">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="mb-0">Current Membership Fees</h5>
          </div>
          <div class="card-body">
            <?php if (empty($fees)): ?>
              <p class="text-muted text-center mb-0">No membership fees set up yet.</p>
            <?php else: ?>
              <?php foreach ($fees as $fee): ?>
                <div class="fee-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1"><?= e($fee['fee_name']) ?></h6>
                      <div class="fs-4 fw-bold text-primary">&euro;<?= number_format((float)$fee['amount'], 2) ?> <small class="text-muted fw-normal">/ <?= $billingPeriodLabels[$fee['billing_period']] ?></small></div>
                      <?php if ($fee['description']): ?>
                        <p class="small text-muted mb-0 mt-1"><?= e($fee['description']) ?></p>
                      <?php endif; ?>
                    </div>
                    <form method="post">
                      <input type="hidden" name="action" value="delete_fee">
                      <input type="hidden" name="fee_id" value="<?= $fee['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($tab === 'gallery'): ?>
    <div class="row">
      <div class="col-lg-4 mb-4">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="mb-0">Add Photo</h5>
          </div>
          <div class="card-body">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="add_gallery">
              
              <div class="mb-3">
                <label for="gallery_file" class="form-label">Upload Image <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="gallery_file" name="gallery_file" required accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="form-text">Max 10MB. Will be resized to max 1200px wide.</div>
              </div>
              
              <div class="mb-3">
                <label for="caption" class="form-label">Caption</label>
                <input type="text" class="form-control" id="caption" name="caption" placeholder="Optional description">
              </div>
              
              <button type="submit" class="btn btn-primary w-100">Add Photo</button>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-lg-8 mb-4">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="mb-0">Photo Gallery</h5>
          </div>
          <div class="card-body">
            <?php if (empty($gallery)): ?>
              <p class="text-muted text-center mb-0">No photos in gallery yet.</p>
            <?php else: ?>
              <div class="row g-3">
                <?php foreach ($gallery as $photo): ?>
                  <div class="col-md-4 col-6">
                    <div class="position-relative">
                      <img src="<?= e($photo['image_url']) ?>" alt="<?= e($photo['caption'] ?? '') ?>" class="img-fluid rounded" style="width: 100%; height: 120px; object-fit: cover;">
                      <?php if ($photo['caption']): ?>
                        <div class="small text-muted mt-1"><?= e($photo['caption']) ?></div>
                      <?php endif; ?>
                      <form method="post" class="position-absolute top-0 end-0 m-1">
                        <input type="hidden" name="action" value="delete_gallery">
                        <input type="hidden" name="gallery_id" value="<?= $photo['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 2px 6px; font-size: 12px;">&times;</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('primary_color')?.addEventListener('input', function() {
  document.getElementById('primary_color_text').value = this.value;
  updatePreview();
});
document.getElementById('secondary_color')?.addEventListener('input', function() {
  document.getElementById('secondary_color_text').value = this.value;
  updatePreview();
});
function updatePreview() {
  const primary = document.getElementById('primary_color').value;
  const secondary = document.getElementById('secondary_color').value;
  document.getElementById('color_preview').style.background = `linear-gradient(135deg, ${primary} 0%, ${secondary} 100%)`;
}
</script>
</body>
</html>
<?php
} catch (Throwable $e) {
  http_response_code(500);
  echo '<div style="font-family: monospace; padding: 20px; background: #fee; border: 1px solid #c00; margin: 20px;">';
  echo '<h2 style="color: #c00;">Error on Club Profile Page</h2>';
  echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
  echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ' (line ' . $e->getLine() . ')</p>';
  echo '<pre style="background: #fff; padding: 10px; overflow: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
  echo '</div>';
}
?>
