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
  $clubId = (int)($_POST['club_id'] ?? 0);
  
  if ($action === 'update_status' && $clubId) {
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, ['trial', 'active', 'cancelled', 'expired', 'suspended'])) {
      update_club_subscription_status($pdo, $clubId, $newStatus);
      $message = 'Club subscription status updated.';
      $messageType = 'success';
    }
  } elseif ($action === 'extend_trial' && $clubId) {
    $days = (int)($_POST['days'] ?? 30);
    $stmt = $pdo->prepare("UPDATE club_subscriptions SET trial_ends_at = DATE_ADD(trial_ends_at, INTERVAL ? DAY) WHERE club_id = ?");
    $stmt->execute([$days, $clubId]);
    $message = "Trial extended by {$days} days.";
    $messageType = 'success';
  } elseif ($action === 'start_trial' && $clubId) {
    create_club_trial($pdo, $clubId, 90);
    $message = 'Trial started (90 days).';
    $messageType = 'success';
  }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$clubs = get_all_clubs_with_subscriptions($pdo, $perPage, $offset);

$stmt = $pdo->query("SELECT COUNT(*) FROM clubs");
$totalClubs = (int)$stmt->fetchColumn();
$totalPages = ceil($totalClubs / $perPage);

$filter = $_GET['filter'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Clubs - Super Admin</title>
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
      <a class="nav-link active" href="/public/superadmin/clubs.php">Clubs</a>
      <a class="nav-link" href="/public/superadmin/users.php">Users</a>
      <a class="nav-link" href="/public/superadmin/subscriptions.php">Subscriptions</a>
      <a class="nav-link" href="/public/dashboard.php">Back to App</a>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Clubs</h2>
    <span class="text-muted"><?= $totalClubs ?> total clubs</span>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Club Name</th>
              <th>Owner</th>
              <th>Email</th>
              <th>Members</th>
              <th>Status</th>
              <th>Trial Ends</th>
              <th>Paid Until</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clubs as $club): ?>
              <tr>
                <td>
                  <a href="/public/club.php?slug=<?= e($club['slug']) ?>" target="_blank">
                    <?= e($club['name']) ?>
                  </a>
                </td>
                <td><?= e($club['owner_name'] ?? '-') ?></td>
                <td><?= e($club['owner_email'] ?? '-') ?></td>
                <td><?= $club['member_count'] ?? 0 ?></td>
                <td>
                  <?php 
                    $status = $club['subscription_status'] ?? 'none';
                    $badgeClass = match($status) {
                      'active' => 'success',
                      'trial' => 'warning',
                      'expired', 'cancelled' => 'danger',
                      'suspended' => 'dark',
                      default => 'secondary'
                    };
                  ?>
                  <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                </td>
                <td>
                  <?php if ($club['trial_ends_at']): ?>
                    <?= date('j M Y', strtotime($club['trial_ends_at'])) ?>
                    <?php 
                      $daysLeft = (int)((strtotime($club['trial_ends_at']) - time()) / 86400);
                      if ($daysLeft > 0 && $daysLeft <= 14): 
                    ?>
                      <span class="badge bg-warning text-dark"><?= $daysLeft ?>d left</span>
                    <?php elseif ($daysLeft <= 0): ?>
                      <span class="badge bg-danger">Expired</span>
                    <?php endif; ?>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td>
                  <?= $club['paid_until'] ? date('j M Y', strtotime($club['paid_until'])) : '-' ?>
                </td>
                <td><?= date('j M Y', strtotime($club['created_at'])) ?></td>
                <td>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                      Actions
                    </button>
                    <ul class="dropdown-menu">
                      <li><a class="dropdown-item" href="/public/club.php?slug=<?= e($club['slug']) ?>" target="_blank">View Club</a></li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <form method="post" class="px-3 py-1">
                          <input type="hidden" name="action" value="update_status">
                          <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                          <select name="status" class="form-select form-select-sm mb-2" onchange="this.form.submit()">
                            <option value="">Change Status...</option>
                            <option value="trial">Trial</option>
                            <option value="active">Active (Paid)</option>
                            <option value="suspended">Suspended</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                          </select>
                        </form>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <form method="post" class="px-3 py-1">
                          <input type="hidden" name="action" value="extend_trial">
                          <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                          <input type="hidden" name="days" value="30">
                          <button type="submit" class="btn btn-sm btn-outline-success w-100">+30 Days Trial</button>
                        </form>
                      </li>
                      <?php if (!$club['subscription_status']): ?>
                      <li>
                        <form method="post" class="px-3 py-1">
                          <input type="hidden" name="action" value="start_trial">
                          <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-primary w-100">Start Trial</button>
                        </form>
                      </li>
                      <?php endif; ?>
                    </ul>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($clubs)): ?>
              <tr><td colspan="9" class="text-center text-muted py-4">No clubs found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <?php if ($totalPages > 1): ?>
      <div class="card-footer bg-white">
        <nav>
          <ul class="pagination mb-0 justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
