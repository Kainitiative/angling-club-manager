<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

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

$stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
$stmt->execute([$clubId, $userId]);
$memberRow = $stmt->fetch();
$committeeRole = $memberRow['committee_role'] ?? null;

$canEditPolicies = $adminRow || in_array($committeeRole, ['chairperson', 'secretary']);

if (!$canEditPolicies) {
  http_response_code(403);
  exit('You do not have permission to edit club policies');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'save_policies') {
    $constitution = trim($_POST['constitution'] ?? '');
    $rulesPolicies = trim($_POST['rules_policies'] ?? '');
    $privacyPolicy = trim($_POST['privacy_policy'] ?? '');
    $membershipTerms = trim($_POST['membership_terms'] ?? '');
    
    $stmt = $pdo->prepare("
      UPDATE clubs 
      SET constitution = ?, rules_policies = ?, privacy_policy = ?, membership_terms = ?
      WHERE id = ?
    ");
    $stmt->execute([
      $constitution ?: null,
      $rulesPolicies ?: null,
      $privacyPolicy ?: null,
      $membershipTerms ?: null,
      $clubId
    ]);
    
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch();
    
    $message = 'Policies saved successfully.';
    $messageType = 'success';
  }
}

$activeTab = $_GET['tab'] ?? 'constitution';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Club Policies - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2>Club Policies & Constitution</h2>
      <p class="text-muted mb-0"><?= e($club['name']) ?></p>
    </div>
    <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-secondary">Back to Club</a>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="card">
    <div class="card-header bg-white">
      <ul class="nav nav-tabs card-header-tabs">
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'constitution' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=constitution">Constitution</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'rules' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=rules">Rules & Policies</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'privacy' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=privacy">Privacy Policy</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeTab === 'membership' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=membership">Membership Terms</a>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="action" value="save_policies">
        
        <?php if ($activeTab === 'constitution'): ?>
          <div class="mb-3">
            <label for="constitution" class="form-label">Club Constitution</label>
            <p class="text-muted small">The formal rules that govern your club's structure, leadership, and decision-making processes.</p>
            <textarea class="form-control" id="constitution" name="constitution" rows="20" placeholder="Enter your club constitution here...

Example sections:
1. Name and Objectives
2. Membership
3. Officers and Committee
4. General Meetings
5. Finances
6. Amendments
7. Dissolution"><?= e($club['constitution'] ?? '') ?></textarea>
          </div>
        <?php elseif ($activeTab === 'rules'): ?>
          <div class="mb-3">
            <label for="rules_policies" class="form-label">Club Rules & Policies</label>
            <p class="text-muted small">Day-to-day rules for members, fishing regulations, and code of conduct.</p>
            <textarea class="form-control" id="rules_policies" name="rules_policies" rows="20" placeholder="Enter your club rules here...

Example sections:
- Fishing Rules
- Catch Limits
- Equipment Restrictions
- Venue Access
- Guest Policy
- Competition Rules
- Code of Conduct"><?= e($club['rules_policies'] ?? '') ?></textarea>
          </div>
        <?php elseif ($activeTab === 'privacy'): ?>
          <div class="mb-3">
            <label for="privacy_policy" class="form-label">Privacy Policy</label>
            <p class="text-muted small">How your club collects, uses, and protects member data.</p>
            <textarea class="form-control" id="privacy_policy" name="privacy_policy" rows="20" placeholder="Enter your privacy policy here...

Example sections:
- Data We Collect
- How We Use Your Data
- Data Sharing
- Data Security
- Your Rights
- Contact Information"><?= e($club['privacy_policy'] ?? '') ?></textarea>
          </div>
        <?php elseif ($activeTab === 'membership'): ?>
          <div class="mb-3">
            <label for="membership_terms" class="form-label">Membership Terms & Conditions</label>
            <p class="text-muted small">Terms that members agree to when joining, including fees, cancellation, and expectations.</p>
            <textarea class="form-control" id="membership_terms" name="membership_terms" rows="20" placeholder="Enter your membership terms here...

Example sections:
- Membership Categories
- Fees and Payment
- Renewal and Expiry
- Cancellation and Refunds
- Member Responsibilities
- Termination of Membership"><?= e($club['membership_terms'] ?? '') ?></textarea>
          </div>
        <?php endif; ?>
        
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <a href="/public/policies.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-secondary" target="_blank">Preview Public Page</a>
        </div>
      </form>
    </div>
  </div>
  
  <div class="card mt-4">
    <div class="card-header bg-white">
      <h6 class="mb-0">Formatting Tips</h6>
    </div>
    <div class="card-body">
      <p class="small text-muted mb-2">Text is displayed as plain text with paragraph breaks preserved. For best readability:</p>
      <ul class="small text-muted mb-0">
        <li>Use blank lines between sections</li>
        <li>Number your sections (1. 2. 3. or a. b. c.)</li>
        <li>Use dashes or bullets for lists</li>
        <li>Keep paragraphs concise</li>
      </ul>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
