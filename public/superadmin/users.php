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
  $targetUserId = (int)($_POST['user_id'] ?? 0);
  
  if ($action === 'toggle_superadmin' && $targetUserId) {
    $stmt = $pdo->prepare("SELECT is_super_admin FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $current = (bool)$stmt->fetchColumn();
    
    set_user_super_admin($pdo, $targetUserId, !$current);
    $message = $current ? 'Super admin privileges removed.' : 'Super admin privileges granted.';
    $messageType = 'success';
  } elseif ($action === 'delete_user' && $targetUserId) {
    if ($targetUserId !== current_user_id()) {
      $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
      $stmt->execute([$targetUserId]);
      $message = 'User deleted.';
      $messageType = 'success';
    } else {
      $message = 'Cannot delete yourself.';
      $messageType = 'danger';
    }
  }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');

if ($search) {
  $stmt = $pdo->prepare("
    SELECT u.*,
           (SELECT c.name FROM clubs c JOIN club_admins ca ON c.id = ca.club_id WHERE ca.user_id = u.id LIMIT 1) as owned_club,
           (SELECT c.name FROM clubs c JOIN club_members cm ON c.id = cm.club_id WHERE cm.user_id = u.id AND cm.membership_status = 'active' LIMIT 1) as member_of_club
    FROM users u
    WHERE u.name LIKE ? OR u.email LIKE ?
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
  ");
  $searchTerm = "%{$search}%";
  $stmt->execute([$searchTerm, $searchTerm, $perPage, $offset]);
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name LIKE ? OR email LIKE ?");
  $stmt->execute([$searchTerm, $searchTerm]);
  $totalUsers = (int)$stmt->fetchColumn();
} else {
  $users = get_all_users($pdo, $perPage, $offset);
  
  $stmt = $pdo->query("SELECT COUNT(*) FROM users");
  $totalUsers = (int)$stmt->fetchColumn();
}

$totalPages = ceil($totalUsers / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users - Super Admin</title>
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
      <a class="nav-link active" href="/public/superadmin/users.php">Users</a>
      <a class="nav-link" href="/public/superadmin/subscriptions.php">Subscriptions</a>
      <a class="nav-link" href="/public/dashboard.php">Back to App</a>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Users</h2>
    <span class="text-muted"><?= $totalUsers ?> total users</span>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="card mb-4">
    <div class="card-body">
      <form method="get" class="row g-3">
        <div class="col-md-6">
          <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?= e($search) ?>">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary">Search</button>
          <?php if ($search): ?>
            <a href="/public/superadmin/users.php" class="btn btn-outline-secondary">Clear</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
  
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Club</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?= $user['id'] ?></td>
                <td>
                  <?= e($user['name']) ?>
                  <?php if ($user['is_super_admin'] ?? false): ?>
                    <span class="badge bg-dark">Super Admin</span>
                  <?php endif; ?>
                </td>
                <td><?= e($user['email']) ?></td>
                <td>
                  <?php if ($user['owned_club']): ?>
                    <span class="badge bg-primary">Club Owner</span>
                  <?php elseif ($user['member_of_club']): ?>
                    <span class="badge bg-info">Member</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">User</span>
                  <?php endif; ?>
                </td>
                <td><?= e($user['owned_club'] ?? $user['member_of_club'] ?? '-') ?></td>
                <td><?= date('j M Y', strtotime($user['created_at'])) ?></td>
                <td>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                      Actions
                    </button>
                    <ul class="dropdown-menu">
                      <li>
                        <form method="post">
                          <input type="hidden" name="action" value="toggle_superadmin">
                          <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                          <button type="submit" class="dropdown-item">
                            <?= ($user['is_super_admin'] ?? false) ? 'Remove Super Admin' : 'Make Super Admin' ?>
                          </button>
                        </form>
                      </li>
                      <?php if ($user['id'] !== current_user_id()): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" class="dropdown-item text-danger">Delete User</button>
                          </form>
                        </li>
                      <?php endif; ?>
                    </ul>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">No users found</td></tr>
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
                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
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
