<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

require_login();

$clubId = (int)($_GET['club_id'] ?? 0);
if (!$clubId) {
  header('Location: /public/dashboard.php');
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
$stmt->execute([$clubId]);
$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
  header('Location: /public/dashboard.php');
  exit;
}

$userId = current_user_id();
$stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
$stmt->execute([$clubId, $userId]);
$adminRole = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
$stmt->execute([$clubId, $userId]);
$committeeRole = $stmt->fetchColumn();

$canManage = $adminRole === 'owner' || $adminRole === 'admin' || $committeeRole === 'treasurer';

if (!$canManage) {
  header('Location: /public/club.php?slug=' . urlencode($club['slug']));
  exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add_account') {
    $accountName = trim($_POST['account_name'] ?? '');
    $accountType = $_POST['account_type'] ?? 'bank';
    $balance = (float)($_POST['balance'] ?? 0);
    $notes = trim($_POST['notes'] ?? '') ?: null;
    
    if ($accountName) {
      $stmt = $pdo->prepare("
        INSERT INTO club_accounts (club_id, account_name, account_type, balance, notes)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $accountName, $accountType, $balance, $notes]);
      $message = 'Account added.';
      $messageType = 'success';
    } else {
      $message = 'Please enter an account name.';
      $messageType = 'danger';
    }
    
  } elseif ($action === 'update_balance') {
    $accountId = (int)($_POST['account_id'] ?? 0);
    $balance = (float)($_POST['balance'] ?? 0);
    
    if ($accountId) {
      $stmt = $pdo->prepare("UPDATE club_accounts SET balance = ? WHERE id = ? AND club_id = ?");
      $stmt->execute([$balance, $accountId, $clubId]);
      $message = 'Balance updated.';
      $messageType = 'success';
    }
    
  } elseif ($action === 'edit_account') {
    $accountId = (int)($_POST['account_id'] ?? 0);
    $accountName = trim($_POST['account_name'] ?? '');
    $accountType = $_POST['account_type'] ?? 'bank';
    $balance = (float)($_POST['balance'] ?? 0);
    $notes = trim($_POST['notes'] ?? '') ?: null;
    
    if ($accountId && $accountName) {
      $stmt = $pdo->prepare("
        UPDATE club_accounts 
        SET account_name = ?, account_type = ?, balance = ?, notes = ?
        WHERE id = ? AND club_id = ?
      ");
      $stmt->execute([$accountName, $accountType, $balance, $notes, $accountId, $clubId]);
      $message = 'Account updated.';
      $messageType = 'success';
    }
    
  } elseif ($action === 'toggle_active') {
    $accountId = (int)($_POST['account_id'] ?? 0);
    
    if ($accountId) {
      $stmt = $pdo->prepare("UPDATE club_accounts SET is_active = NOT is_active WHERE id = ? AND club_id = ?");
      $stmt->execute([$accountId, $clubId]);
      $message = 'Account status updated.';
      $messageType = 'success';
    }
    
  } elseif ($action === 'delete_account') {
    $accountId = (int)($_POST['account_id'] ?? 0);
    
    if ($accountId) {
      $stmt = $pdo->prepare("DELETE FROM club_accounts WHERE id = ? AND club_id = ?");
      $stmt->execute([$accountId, $clubId]);
      $message = 'Account deleted.';
      $messageType = 'success';
    }
  }
}

$stmt = $pdo->prepare("SELECT * FROM club_accounts WHERE club_id = ? ORDER BY is_active DESC, account_name ASC");
$stmt->execute([$clubId]);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalBalance = 0;
$activeAccounts = 0;
foreach ($accounts as $account) {
  if ($account['is_active']) {
    $totalBalance += (float)$account['balance'];
    $activeAccounts++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Club Accounts - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --primary-dark: #1e3a5f; --primary: #2d5a87; }
    .navbar-custom { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); }
    .balance-card { background: linear-gradient(135deg, #198754 0%, #20c997 100%); color: white; }
    .account-card { transition: all 0.2s; }
    .account-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .account-card.inactive { opacity: 0.6; }
    .account-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .icon-bank { background: #e3f2fd; color: #1976d2; }
    .icon-cash { background: #e8f5e9; color: #388e3c; }
    .icon-paypal { background: #e3f2fd; color: #003087; }
    .icon-other { background: #f3e5f5; color: #7b1fa2; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark navbar-custom">
  <div class="container">
    <a class="navbar-brand" href="/public/club.php?slug=<?= e($club['slug']) ?>"><?= e($club['name']) ?></a>
    <div class="d-flex gap-2">
      <a href="/public/admin/finances.php?club_id=<?= $clubId ?>" class="btn btn-outline-light btn-sm">Finances</a>
      <a href="/public/dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row mb-4">
    <div class="col-md-8">
      <h2 class="mb-1">Club Accounts</h2>
      <p class="text-muted mb-0">Track your club's account balances</p>
    </div>
    <div class="col-md-4">
      <div class="card balance-card">
        <div class="card-body text-center">
          <h6 class="text-white-50 mb-1">Total Balance</h6>
          <h2 class="mb-0"><?= number_format($totalBalance, 2) ?> EUR</h2>
          <small class="text-white-50"><?= $activeAccounts ?> active account<?= $activeAccounts !== 1 ? 's' : '' ?></small>
        </div>
      </div>
    </div>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
      <i class="bi bi-plus-lg"></i> Add Account
    </button>
  </div>
  
  <?php if (empty($accounts)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-wallet2 fs-1 text-muted"></i>
        <h5 class="mt-3">No accounts yet</h5>
        <p class="text-muted">Add your club's bank accounts, cash floats, or other funds to track the total balance.</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
          Add First Account
        </button>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($accounts as $account): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card account-card h-100 <?= !$account['is_active'] ? 'inactive' : '' ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-3">
                  <div class="account-icon icon-<?= $account['account_type'] ?>">
                    <?php 
                      $icon = match($account['account_type']) {
                        'bank' => 'bi-bank',
                        'cash' => 'bi-cash-stack',
                        'paypal' => 'bi-paypal',
                        default => 'bi-wallet2'
                      };
                    ?>
                    <i class="bi <?= $icon ?>"></i>
                  </div>
                  <div>
                    <h5 class="mb-0"><?= e($account['account_name']) ?></h5>
                    <small class="text-muted"><?= ucfirst($account['account_type']) ?></small>
                    <?php if (!$account['is_active']): ?>
                      <span class="badge bg-secondary ms-1">Inactive</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editAccountModal<?= $account['id'] ?>">Edit</a></li>
                    <li>
                      <form method="post">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                        <button type="submit" class="dropdown-item">
                          <?= $account['is_active'] ? 'Mark Inactive' : 'Mark Active' ?>
                        </button>
                      </form>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form method="post" onsubmit="return confirm('Delete this account?');">
                        <input type="hidden" name="action" value="delete_account">
                        <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                        <button type="submit" class="dropdown-item text-danger">Delete</button>
                      </form>
                    </li>
                  </ul>
                </div>
              </div>
              
              <h3 class="mb-2 <?= (float)$account['balance'] < 0 ? 'text-danger' : 'text-success' ?>">
                <?= number_format((float)$account['balance'], 2) ?> EUR
              </h3>
              
              <?php if ($account['notes']): ?>
                <p class="text-muted small mb-2"><?= e($account['notes']) ?></p>
              <?php endif; ?>
              
              <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">
                  Updated: <?= date('j M Y, g:i A', strtotime($account['last_updated'])) ?>
                </small>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickUpdateModal<?= $account['id'] ?>">
                  Update Balance
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal fade" id="quickUpdateModal<?= $account['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
              <form method="post">
                <input type="hidden" name="action" value="update_balance">
                <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                <div class="modal-header">
                  <h6 class="modal-title">Update Balance</h6>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <label class="form-label"><?= e($account['account_name']) ?></label>
                  <div class="input-group">
                    <span class="input-group-text">EUR</span>
                    <input type="number" name="balance" class="form-control" step="0.01" value="<?= number_format((float)$account['balance'], 2, '.', '') ?>" required>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary w-100">Save</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        
        <div class="modal fade" id="editAccountModal<?= $account['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="post">
                <input type="hidden" name="action" value="edit_account">
                <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                <div class="modal-header">
                  <h5 class="modal-title">Edit Account</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label">Account Name *</label>
                    <input type="text" name="account_name" class="form-control" value="<?= e($account['account_name']) ?>" required>
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Type</label>
                      <select name="account_type" class="form-select">
                        <option value="bank" <?= $account['account_type'] === 'bank' ? 'selected' : '' ?>>Bank Account</option>
                        <option value="cash" <?= $account['account_type'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="paypal" <?= $account['account_type'] === 'paypal' ? 'selected' : '' ?>>PayPal</option>
                        <option value="other" <?= $account['account_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Balance (EUR)</label>
                      <input type="number" name="balance" class="form-control" step="0.01" value="<?= number_format((float)$account['balance'], 2, '.', '') ?>">
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= e($account['notes'] ?? '') ?></textarea>
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
</div>

<div class="modal fade" id="addAccountModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add_account">
        <div class="modal-header">
          <h5 class="modal-title">Add Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Account Name *</label>
            <input type="text" name="account_name" class="form-control" required placeholder="e.g., AIB Current Account, Petty Cash">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Type</label>
              <select name="account_type" class="form-select">
                <option value="bank">Bank Account</option>
                <option value="cash">Cash</option>
                <option value="paypal">PayPal</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Current Balance (EUR)</label>
              <input type="number" name="balance" class="form-control" step="0.01" value="0.00">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes (optional)</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="e.g., Main club account, Competition float"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
