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

$userId = current_user_id();
$isAdmin = false;
$isMember = false;

if ($userId) {
  $stmt = $pdo->prepare("SELECT 1 FROM club_admins WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$club['id'], $userId]);
  $isAdmin = (bool)$stmt->fetch();
  
  $stmt = $pdo->prepare("SELECT 1 FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
  $stmt->execute([$club['id'], $userId]);
  $isMember = (bool)$stmt->fetch();
}

$hasConstitution = !empty(trim($club['constitution'] ?? ''));
$hasRules = !empty(trim($club['rules_policies'] ?? ''));
$hasPrivacy = !empty(trim($club['privacy_policy'] ?? ''));
$hasTerms = !empty(trim($club['membership_terms'] ?? ''));

$hasPolicies = $hasConstitution || $hasRules || $hasPrivacy || $hasTerms;

$activeTab = $_GET['tab'] ?? '';
if (!$activeTab) {
  if ($hasConstitution) $activeTab = 'constitution';
  elseif ($hasRules) $activeTab = 'rules';
  elseif ($hasPrivacy) $activeTab = 'privacy';
  elseif ($hasTerms) $activeTab = 'membership';
  else $activeTab = 'constitution';
}

function formatPolicyText(string $text): string {
  $escaped = e($text);
  $paragraphs = preg_split('/\n\s*\n/', $escaped);
  $html = '';
  foreach ($paragraphs as $para) {
    $para = trim($para);
    if ($para !== '') {
      $para = nl2br($para);
      $html .= "<p>{$para}</p>";
    }
  }
  return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Policies - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .policy-content {
      max-width: 800px;
      line-height: 1.7;
    }
    .policy-content p {
      margin-bottom: 1rem;
    }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <?php if ($userId): ?>
        <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
      <?php else: ?>
        <a class="btn btn-outline-light btn-sm" href="/">Log In</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2><?= e($club['name']) ?></h2>
      <p class="text-muted mb-0">Policies & Constitution</p>
    </div>
    <div>
      <?php if ($isAdmin): ?>
        <a href="/public/admin/policies.php?club_id=<?= $club['id'] ?>" class="btn btn-outline-primary btn-sm me-2">Edit Policies</a>
      <?php endif; ?>
      <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-secondary btn-sm">Back to Club</a>
    </div>
  </div>
  
  <?php if (!$hasPolicies): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <p class="text-muted mb-0">This club has not published any policies yet.</p>
        <?php if ($isAdmin): ?>
          <a href="/public/admin/policies.php?club_id=<?= $club['id'] ?>" class="btn btn-primary mt-3">Add Policies</a>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-header bg-white">
        <ul class="nav nav-tabs card-header-tabs">
          <?php if ($hasConstitution): ?>
            <li class="nav-item">
              <a class="nav-link <?= $activeTab === 'constitution' ? 'active' : '' ?>" href="?slug=<?= e($slug) ?>&tab=constitution">Constitution</a>
            </li>
          <?php endif; ?>
          <?php if ($hasRules): ?>
            <li class="nav-item">
              <a class="nav-link <?= $activeTab === 'rules' ? 'active' : '' ?>" href="?slug=<?= e($slug) ?>&tab=rules">Rules & Policies</a>
            </li>
          <?php endif; ?>
          <?php if ($hasPrivacy): ?>
            <li class="nav-item">
              <a class="nav-link <?= $activeTab === 'privacy' ? 'active' : '' ?>" href="?slug=<?= e($slug) ?>&tab=privacy">Privacy Policy</a>
            </li>
          <?php endif; ?>
          <?php if ($hasTerms): ?>
            <li class="nav-item">
              <a class="nav-link <?= $activeTab === 'membership' ? 'active' : '' ?>" href="?slug=<?= e($slug) ?>&tab=membership">Membership Terms</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="card-body">
        <div class="policy-content">
          <?php if ($activeTab === 'constitution' && $hasConstitution): ?>
            <h4 class="mb-4">Club Constitution</h4>
            <?= formatPolicyText($club['constitution']) ?>
          <?php elseif ($activeTab === 'rules' && $hasRules): ?>
            <h4 class="mb-4">Rules & Policies</h4>
            <?= formatPolicyText($club['rules_policies']) ?>
          <?php elseif ($activeTab === 'privacy' && $hasPrivacy): ?>
            <h4 class="mb-4">Privacy Policy</h4>
            <?= formatPolicyText($club['privacy_policy']) ?>
          <?php elseif ($activeTab === 'membership' && $hasTerms): ?>
            <h4 class="mb-4">Membership Terms & Conditions</h4>
            <?= formatPolicyText($club['membership_terms']) ?>
          <?php else: ?>
            <p class="text-muted">Select a policy to view.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
