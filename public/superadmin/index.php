<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/superadmin.php';

require_login();
require_super_admin($pdo);

$stats = get_platform_stats($pdo);
$recentClubs = get_all_clubs_with_subscriptions($pdo, 10, 0);
$recentUsers = get_all_users($pdo, 10, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Super Admin Dashboard - Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .stat-card { border-left: 4px solid; }
    .stat-card.primary { border-color: #0d6efd; }
    .stat-card.success { border-color: #198754; }
    .stat-card.warning { border-color: #ffc107; }
    .stat-card.danger { border-color: #dc3545; }
    .stat-card.info { border-color: #0dcaf0; }
    .stat-number { font-size: 2rem; font-weight: bold; }
  </style>
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
      <a class="nav-link" href="/public/superadmin/subscriptions.php">Subscriptions</a>
      <a class="nav-link" href="/public/dashboard.php">Back to App</a>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">
  <h2 class="mb-4">Platform Overview</h2>
  
  <div class="row g-4 mb-4">
    <div class="col-md-4 col-lg-2">
      <div class="card stat-card primary h-100">
        <div class="card-body">
          <div class="stat-number text-primary"><?= $stats['total_users'] ?></div>
          <div class="text-muted">Total Users</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-lg-2">
      <div class="card stat-card success h-100">
        <div class="card-body">
          <div class="stat-number text-success"><?= $stats['total_clubs'] ?></div>
          <div class="text-muted">Total Clubs</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-lg-2">
      <div class="card stat-card info h-100">
        <div class="card-body">
          <div class="stat-number text-info"><?= $stats['total_active_members'] ?></div>
          <div class="text-muted">Active Members</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-lg-2">
      <div class="card stat-card warning h-100">
        <div class="card-body">
          <div class="stat-number text-warning"><?= $stats['clubs_in_trial'] ?></div>
          <div class="text-muted">Clubs in Trial</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-lg-2">
      <div class="card stat-card success h-100">
        <div class="card-body">
          <div class="stat-number text-success"><?= $stats['clubs_paying'] ?></div>
          <div class="text-muted">Paying Clubs</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-lg-2">
      <div class="card stat-card danger h-100">
        <div class="card-body">
          <div class="stat-number text-danger"><?= $stats['clubs_expired'] ?></div>
          <div class="text-muted">Expired/Cancelled</div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row g-4 mb-4">
    <div class="col-md-4 col-lg-2">
      <div class="card stat-card primary h-100">
        <div class="card-body">
          <div class="stat-number text-primary"><?= $stats['total_catches'] ?></div>
          <div class="text-muted">Total Catches</div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-lg-2">
      <div class="card stat-card info h-100">
        <div class="card-body">
          <div class="stat-number text-info"><?= $stats['total_competitions'] ?></div>
          <div class="text-muted">Competitions</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Recent Clubs</h5>
          <a href="/public/superadmin/clubs.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Club</th>
                  <th>Owner</th>
                  <th>Members</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentClubs as $club): ?>
                  <tr>
                    <td>
                      <a href="/public/club.php?slug=<?= e($club['slug']) ?>" target="_blank">
                        <?= e($club['name']) ?>
                      </a>
                    </td>
                    <td><?= e($club['owner_name'] ?? 'Unknown') ?></td>
                    <td><?= $club['member_count'] ?? 0 ?></td>
                    <td>
                      <?php 
                        $status = $club['subscription_status'] ?? 'trial';
                        $badgeClass = match($status) {
                          'active' => 'success',
                          'trial' => 'warning',
                          'expired', 'cancelled' => 'danger',
                          default => 'secondary'
                        };
                      ?>
                      <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($recentClubs)): ?>
                  <tr><td colspan="4" class="text-center text-muted py-3">No clubs yet</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Recent Users</h5>
          <a href="/public/superadmin/users.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Club</th>
                  <th>Joined</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentUsers as $user): ?>
                  <tr>
                    <td>
                      <?= e($user['name']) ?>
                      <?php if ($user['is_super_admin'] ?? false): ?>
                        <span class="badge bg-dark">Super Admin</span>
                      <?php endif; ?>
                    </td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e($user['owned_club'] ?? $user['member_of_club'] ?? '-') ?></td>
                    <td><?= date('j M Y', strtotime($user['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($recentUsers)): ?>
                  <tr><td colspan="4" class="text-center text-muted py-3">No users yet</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
