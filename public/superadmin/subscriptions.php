<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/superadmin.php';

require_login();
require_super_admin($pdo);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'update_plan') {
    $planId = (int)($_POST['plan_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    $stmt = $pdo->prepare("UPDATE subscription_plans SET price_monthly = ?, description = ? WHERE id = ?");
    $stmt->execute([$price, $description ?: null, $planId]);
    $message = 'Plan updated.';
    $messageType = 'success';
  }
}

try {
  $stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY price_monthly");
  $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $plans = [];
}

try {
  $stmt = $pdo->query("
    SELECT 
      COALESCE(cs.status, 'none') as status,
      COUNT(*) as count
    FROM clubs c
    LEFT JOIN club_subscriptions cs ON c.id = cs.club_id
    GROUP BY COALESCE(cs.status, 'none')
  ");
  $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
  $statusCounts = [];
}

try {
  $stmt = $pdo->query("
    SELECT cs.*, c.name as club_name, sp.name as plan_name, sp.price_monthly
    FROM club_subscriptions cs
    JOIN clubs c ON cs.club_id = c.id
    LEFT JOIN subscription_plans sp ON cs.plan_id = sp.id
    WHERE cs.status = 'trial' AND cs.trial_ends_at < DATE_ADD(NOW(), INTERVAL 14 DAY)
    ORDER BY cs.trial_ends_at ASC
    LIMIT 20
  ");
  $expiringTrials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $expiringTrials = [];
}

try {
  $stmt = $pdo->query("
    SELECT SUM(sp.price_monthly) as mrr
    FROM club_subscriptions cs
    JOIN subscription_plans sp ON cs.plan_id = sp.id
    WHERE cs.status = 'active'
  ");
  $mrr = (float)($stmt->fetchColumn() ?: 0);
} catch (PDOException $e) {
  $mrr = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Subscriptions - Super Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/public/superadmin/">
      <i class="bi bi-shield-lock"></i> Super Admin
    </a>
    <div class="navbar-nav ms-auto">
      <a class="nav-link" href="/public/superadmin/clubs.php">Clubs</a>
      <a class="nav-link" href="/public/superadmin/users.php">Users</a>
      <a class="nav-link active" href="/public/superadmin/subscriptions.php">Subscriptions</a>
      <a class="nav-link" href="/public/dashboard.php">Back to App</a>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">
  <h2 class="mb-4">Subscription Management</h2>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body">
          <h3 class="mb-0">&euro;<?= number_format($mrr, 2) ?></h3>
          <div>Monthly Recurring Revenue</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h3 class="mb-0 text-success"><?= $statusCounts['active'] ?? 0 ?></h3>
          <div class="text-muted">Active Subscriptions</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h3 class="mb-0 text-warning"><?= $statusCounts['trial'] ?? 0 ?></h3>
          <div class="text-muted">In Trial</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h3 class="mb-0 text-danger"><?= ($statusCounts['expired'] ?? 0) + ($statusCounts['cancelled'] ?? 0) ?></h3>
          <div class="text-muted">Expired/Cancelled</div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Subscription Plans</h5>
        </div>
        <div class="card-body">
          <?php if (empty($plans)): ?>
            <p class="text-muted">Subscription plans not yet created. Run migrations to add them.</p>
          <?php else: ?>
            <?php foreach ($plans as $plan): ?>
              <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <h5 class="mb-0"><?= e($plan['name']) ?></h5>
                    <span class="badge bg-secondary"><?= e($plan['slug']) ?></span>
                  </div>
                  <h4 class="text-primary mb-0">&euro;<?= number_format((float)$plan['price_monthly'], 2) ?>/mo</h4>
                </div>
                <p class="text-muted small mb-2"><?= e($plan['description'] ?? 'No description') ?></p>
                <?php if ($plan['features']): ?>
                  <?php $features = json_decode($plan['features'], true) ?: []; ?>
                  <ul class="small mb-0">
                    <?php foreach ($features as $feature): ?>
                      <li><?= e($feature) ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Trials Expiring Soon</h5>
          <span class="badge bg-warning text-dark"><?= count($expiringTrials) ?></span>
        </div>
        <div class="card-body p-0">
          <?php if (empty($expiringTrials)): ?>
            <p class="text-muted text-center py-4 mb-0">No trials expiring in the next 14 days</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Club</th>
                    <th>Expires</th>
                    <th>Days Left</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($expiringTrials as $trial): ?>
                    <?php $daysLeft = (int)((strtotime($trial['trial_ends_at']) - time()) / 86400); ?>
                    <tr>
                      <td><?= e($trial['club_name']) ?></td>
                      <td><?= date('j M', strtotime($trial['trial_ends_at'])) ?></td>
                      <td>
                        <?php if ($daysLeft <= 0): ?>
                          <span class="badge bg-danger">Expired</span>
                        <?php elseif ($daysLeft <= 7): ?>
                          <span class="badge bg-danger"><?= $daysLeft ?>d</span>
                        <?php else: ?>
                          <span class="badge bg-warning text-dark"><?= $daysLeft ?>d</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <form method="post" action="/public/superadmin/clubs.php" style="display:inline;">
                          <input type="hidden" name="action" value="extend_trial">
                          <input type="hidden" name="club_id" value="<?= $trial['club_id'] ?>">
                          <input type="hidden" name="days" value="30">
                          <button type="submit" class="btn btn-sm btn-outline-success">+30d</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="card mt-4">
        <div class="card-header bg-white">
          <h5 class="mb-0">Status Breakdown</h5>
        </div>
        <div class="card-body">
          <div class="row text-center">
            <div class="col">
              <div class="h4 text-warning"><?= $statusCounts['trial'] ?? 0 ?></div>
              <small class="text-muted">Trial</small>
            </div>
            <div class="col">
              <div class="h4 text-success"><?= $statusCounts['active'] ?? 0 ?></div>
              <small class="text-muted">Active</small>
            </div>
            <div class="col">
              <div class="h4 text-danger"><?= $statusCounts['expired'] ?? 0 ?></div>
              <small class="text-muted">Expired</small>
            </div>
            <div class="col">
              <div class="h4 text-secondary"><?= $statusCounts['cancelled'] ?? 0 ?></div>
              <small class="text-muted">Cancelled</small>
            </div>
            <div class="col">
              <div class="h4 text-dark"><?= $statusCounts['suspended'] ?? 0 ?></div>
              <small class="text-muted">Suspended</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
