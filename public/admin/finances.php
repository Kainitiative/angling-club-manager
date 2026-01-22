<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../error_log.txt');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    file_put_contents(__DIR__ . '/../../error_log.txt', date('Y-m-d H:i:s') . " ERROR: $errstr in $errfile on line $errline\n", FILE_APPEND);
    return false;
});

set_exception_handler(function($e) {
    file_put_contents(__DIR__ . '/../../error_log.txt', date('Y-m-d H:i:s') . " EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    http_response_code(500);
    echo "Error logged to error_log.txt - check that file for details";
    exit;
});

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/layout/club_admin_shell.php';

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

// Use new permission system
$canView = can_view($pdo, $userId, $clubId, 'finances');
$canCreate = can_create($pdo, $userId, $clubId, 'finances');
$canEdit = can_edit($pdo, $userId, $clubId, 'finances');
$canDelete = can_delete($pdo, $userId, $clubId, 'finances');

if (!$canView) {
  http_response_code(403);
  exit('Access denied. Only committee members can view club finances.');
}

$isPostgres = defined('DB_DRIVER') && DB_DRIVER === 'pgsql';
$financeTable = $isPostgres ? 'club_transactions' : 'club_finances';
$typeCol = $isPostgres ? 'transaction_type' : 'type';

if (!$isPostgres) {
  try {
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS club_finances (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        club_id BIGINT UNSIGNED NOT NULL,
        account_id BIGINT UNSIGNED NULL,
        type ENUM('income', 'expense') NOT NULL,
        category VARCHAR(120) NOT NULL DEFAULT 'other',
        amount DECIMAL(10,2) NOT NULL,
        description TEXT NULL,
        transaction_date DATE NOT NULL,
        receipt_url VARCHAR(500) NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_finances_club (club_id),
        INDEX idx_finances_date (transaction_date)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS club_accounts (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        club_id BIGINT UNSIGNED NOT NULL,
        account_name VARCHAR(120) NOT NULL,
        account_type ENUM('bank', 'cash', 'paypal', 'stripe', 'other') NOT NULL DEFAULT 'bank',
        balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        notes TEXT NULL,
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_accounts_club (club_id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
  } catch (PDOException $e) {
  }
}

$categories = [
  'membership' => 'Membership Fees',
  'sponsorship' => 'Sponsorship',
  'donations' => 'Donations',
  'competition_fees' => 'Competition Fees',
  'equipment' => 'Equipment',
  'venue' => 'Venue Hire',
  'insurance' => 'Insurance',
  'prizes' => 'Prizes & Trophies',
  'travel' => 'Travel',
  'marketing' => 'Marketing',
  'utilities' => 'Utilities',
  'other' => 'Other',
];

$filterMonth = $_GET['month'] ?? '';
$filterYear = $_GET['year'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$showReport = isset($_GET['report']);
$showAccounts = isset($_GET['accounts']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add_account') {
    $accountName = trim($_POST['account_name'] ?? '');
    $balance = (float)($_POST['balance'] ?? 0);
    
    if ($accountName) {
      try {
        $stmt = $pdo->prepare("INSERT INTO club_accounts (club_id, account_name, account_type, balance, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$clubId, $accountName, $_POST['account_type'] ?? 'bank', $balance, trim($_POST['notes'] ?? '') ?: null]);
      } catch (PDOException $e) {
        try {
          $stmt = $pdo->prepare("INSERT INTO club_accounts (club_id, name, balance) VALUES (?, ?, ?)");
          $stmt->execute([$clubId, $accountName, $balance]);
        } catch (PDOException $e2) {
        }
      }
      $message = 'Account added.';
      $messageType = 'success';
    }
  } elseif ($action === 'update_account') {
    $accountId = (int)($_POST['account_id'] ?? 0);
    $accountName = trim($_POST['account_name'] ?? '');
    $balance = (float)($_POST['balance'] ?? 0);
    
    if ($accountId && $accountName) {
      try {
        $stmt = $pdo->prepare("UPDATE club_accounts SET account_name = ?, account_type = ?, balance = ?, notes = ? WHERE id = ? AND club_id = ?");
        $stmt->execute([$accountName, $_POST['account_type'] ?? 'bank', $balance, trim($_POST['notes'] ?? '') ?: null, $accountId, $clubId]);
      } catch (PDOException $e) {
        try {
          $stmt = $pdo->prepare("UPDATE club_accounts SET name = ?, balance = ? WHERE id = ? AND club_id = ?");
          $stmt->execute([$accountName, $balance, $accountId, $clubId]);
        } catch (PDOException $e2) {
        }
      }
      $message = 'Account updated.';
      $messageType = 'success';
    }
  } elseif ($action === 'delete_account') {
    $accountId = (int)($_POST['account_id'] ?? 0);
    if ($accountId) {
      $stmt = $pdo->prepare("DELETE FROM club_accounts WHERE id = ? AND club_id = ?");
      $stmt->execute([$accountId, $clubId]);
      $message = 'Account deleted.';
      $messageType = 'info';
    }
  } elseif ($action === 'add') {
    $entryType = $_POST['entry_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $entryDate = $_POST['entry_date'] ?? date('Y-m-d');
    $category = $_POST['category'] ?? 'other';
    
    if (!in_array($entryType, ['income', 'expense'])) {
      $message = 'Invalid entry type.';
      $messageType = 'danger';
    } elseif (empty($title)) {
      $message = 'Please enter a title.';
      $messageType = 'danger';
    } elseif ($amount <= 0) {
      $message = 'Please enter a valid amount.';
      $messageType = 'danger';
    } else {
      if ($isPostgres) {
        $stmt = $pdo->prepare("
          INSERT INTO $financeTable (club_id, transaction_type, description, amount, transaction_date, category, created_by)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
      } else {
        $stmt = $pdo->prepare("
          INSERT INTO $financeTable (club_id, type, description, amount, transaction_date, category, created_by)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
      }
      $stmt->execute([$clubId, $entryType, $title, $amount, $entryDate, $category, $userId]);
      $message = ucfirst($entryType) . ' entry added successfully.';
      $messageType = 'success';
    }
  } elseif ($action === 'delete') {
    $entryId = (int)($_POST['entry_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM $financeTable WHERE id = ? AND club_id = ?");
    $stmt->execute([$entryId, $clubId]);
    $message = 'Entry deleted.';
    $messageType = 'info';
  }
}

$whereClause = "ct.club_id = ?";
$params = [$clubId];

if ($filterMonth && $filterYear) {
  if ($isPostgres) {
    $whereClause .= " AND EXTRACT(MONTH FROM ct.transaction_date) = ? AND EXTRACT(YEAR FROM ct.transaction_date) = ?";
  } else {
    $whereClause .= " AND MONTH(ct.transaction_date) = ? AND YEAR(ct.transaction_date) = ?";
  }
  $params[] = (int)$filterMonth;
  $params[] = (int)$filterYear;
} elseif ($filterYear) {
  if ($isPostgres) {
    $whereClause .= " AND EXTRACT(YEAR FROM ct.transaction_date) = ?";
  } else {
    $whereClause .= " AND YEAR(ct.transaction_date) = ?";
  }
  $params[] = (int)$filterYear;
}

if ($filterCategory) {
  $whereClause .= " AND ct.category = ?";
  $params[] = $filterCategory;
}

$entries = [];
try {
  if ($isPostgres) {
    $stmt = $pdo->prepare("
      SELECT ct.*, ct.transaction_date as entry_date, ct.transaction_type as entry_type,
             u.name as created_by_name
      FROM $financeTable ct
      LEFT JOIN users u ON ct.created_by = u.id
      WHERE $whereClause
      ORDER BY ct.transaction_date DESC, ct.created_at DESC
    ");
  } else {
    $stmt = $pdo->prepare("
      SELECT ct.*, ct.transaction_date as entry_date, ct.type as entry_type,
             u.name as created_by_name
      FROM $financeTable ct
      LEFT JOIN users u ON ct.created_by = u.id
      WHERE $whereClause
      ORDER BY ct.transaction_date DESC, ct.created_at DESC
    ");
  }
  $stmt->execute($params);
  $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $entries = [];
}

$totalIncome = 0;
$totalExpense = 0;
foreach ($entries as $entry) {
  if ($entry['entry_type'] === 'income') {
    $totalIncome += (float)$entry['amount'];
  } else {
    $totalExpense += (float)$entry['amount'];
  }
}
$balance = $totalIncome - $totalExpense;

$availableYears = [date('Y')];
try {
  if ($isPostgres) {
    $stmt = $pdo->prepare("SELECT DISTINCT EXTRACT(YEAR FROM transaction_date)::int as year FROM $financeTable WHERE club_id = ? ORDER BY year DESC");
  } else {
    $stmt = $pdo->prepare("SELECT DISTINCT YEAR(transaction_date) as year FROM $financeTable WHERE club_id = ? ORDER BY year DESC");
  }
  $stmt->execute([$clubId]);
  $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
  if (!empty($years)) {
    $availableYears = $years;
  }
} catch (PDOException $e) {
}

$reportData = [];
if ($showReport) {
  $reportYear = $filterYear ?: date('Y');
  $rawReport = [];
  
  $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  
  for ($m = 1; $m <= 12; $m++) {
    $reportData[$m] = [
      'month_name' => $monthNames[$m],
      'income' => 0,
      'expense' => 0,
      'categories' => []
    ];
  }
  
  try {
    if ($isPostgres) {
      $stmt = $pdo->prepare("
        SELECT 
          EXTRACT(MONTH FROM transaction_date)::int as month,
          category,
          transaction_type as entry_type,
          SUM(amount) as total
        FROM $financeTable
        WHERE club_id = ? AND EXTRACT(YEAR FROM transaction_date) = ?
        GROUP BY EXTRACT(MONTH FROM transaction_date), category, transaction_type
        ORDER BY EXTRACT(MONTH FROM transaction_date)
      ");
    } else {
      $stmt = $pdo->prepare("
        SELECT 
          MONTH(transaction_date) as month,
          category,
          type as entry_type,
          SUM(amount) as total
        FROM $financeTable
        WHERE club_id = ? AND YEAR(transaction_date) = ?
        GROUP BY MONTH(transaction_date), category, type
        ORDER BY MONTH(transaction_date)
      ");
    }
    $stmt->execute([$clubId, $reportYear]);
    $rawReport = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
  }
  
  foreach ($rawReport as $row) {
    $m = (int)$row['month'];
    if ($row['entry_type'] === 'income') {
      $reportData[$m]['income'] += (float)$row['total'];
    } else {
      $reportData[$m]['expense'] += (float)$row['total'];
    }
    $reportData[$m]['categories'][$row['category']] = ($reportData[$m]['categories'][$row['category']] ?? 0) + (float)$row['total'];
  }
}

$accounts = [];
try {
  $stmt = $pdo->prepare("SELECT * FROM club_accounts WHERE club_id = ? ORDER BY id");
  $stmt->execute([$clubId]);
  $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $accounts = [];
}

$accountTypes = [
  'bank' => 'Bank Account',
  'cash' => 'Cash',
  'paypal' => 'PayPal',
  'stripe' => 'Stripe',
  'other' => 'Other'
];

$pageTitle = 'Finances - ' . $club['name'];

club_admin_shell_start($pdo, $club, ['title' => $pageTitle, 'page' => 'finances', 'section' => 'Money']);
?>

<div class="container-fluid py-4">
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card bg-success text-white">
        <div class="card-body text-center">
          <h6 class="card-title mb-1">Total Income</h6>
          <h3 class="mb-0">&euro;<?= number_format($totalIncome, 2) ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card bg-danger text-white">
        <div class="card-body text-center">
          <h6 class="card-title mb-1">Total Expenses</h6>
          <h3 class="mb-0">&euro;<?= number_format($totalExpense, 2) ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card <?= $balance >= 0 ? 'bg-primary' : 'bg-warning' ?> text-white">
        <div class="card-body text-center">
          <h6 class="card-title mb-1">Balance</h6>
          <h3 class="mb-0">&euro;<?= number_format($balance, 2) ?></h3>
        </div>
      </div>
    </div>
  </div>

  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link <?= !$showReport && !$showAccounts ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>">
        <i class="bi bi-list-ul"></i> Transactions
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $showReport ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&report=1">
        <i class="bi bi-bar-chart"></i> Reports
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $showAccounts ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&accounts=1">
        <i class="bi bi-wallet2"></i> Accounts
      </a>
    </li>
  </ul>

  <?php if ($showAccounts): ?>
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-wallet2"></i> Club Accounts</span>
        <?php if ($canEdit): ?>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
            <i class="bi bi-plus-lg"></i> Add Account
          </button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (empty($accounts)): ?>
          <p class="text-muted text-center my-4">No accounts set up yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Account Name</th>
                  <th>Type</th>
                  <th class="text-end">Balance</th>
                  <th>Notes</th>
                  <?php if ($canEdit): ?>
                    <th class="text-center">Actions</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($accounts as $account): ?>
                  <tr>
                    <td><?= e($account['account_name'] ?? $account['name'] ?? '') ?></td>
                    <td><?= e($accountTypes[$account['account_type'] ?? $account['type'] ?? 'other'] ?? 'Other') ?></td>
                    <td class="text-end">&euro;<?= number_format((float)($account['balance'] ?? 0), 2) ?></td>
                    <td><?= e($account['notes'] ?? '') ?></td>
                    <?php if ($canEdit): ?>
                      <td class="text-center">
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this account?')">
                          <input type="hidden" name="action" value="delete_account">
                          <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php elseif ($showReport): ?>
    <div class="card mb-4">
      <div class="card-header">
        <i class="bi bi-bar-chart"></i> Financial Report - <?= $filterYear ?: date('Y') ?>
      </div>
      <div class="card-body">
        <form method="GET" class="row g-3 mb-4">
          <input type="hidden" name="club_id" value="<?= $clubId ?>">
          <input type="hidden" name="report" value="1">
          <div class="col-auto">
            <select name="year" class="form-select">
              <?php foreach ($availableYears as $y): ?>
                <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary">View Report</button>
          </div>
        </form>
        
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead class="table-light">
              <tr>
                <th>Month</th>
                <th class="text-end text-success">Income</th>
                <th class="text-end text-danger">Expenses</th>
                <th class="text-end">Net</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $yearTotalIncome = 0;
              $yearTotalExpense = 0;
              foreach ($reportData as $m => $data): 
                $net = $data['income'] - $data['expense'];
                $yearTotalIncome += $data['income'];
                $yearTotalExpense += $data['expense'];
              ?>
                <tr>
                  <td><?= $data['month_name'] ?></td>
                  <td class="text-end text-success">&euro;<?= number_format($data['income'], 2) ?></td>
                  <td class="text-end text-danger">&euro;<?= number_format($data['expense'], 2) ?></td>
                  <td class="text-end <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">&euro;<?= number_format($net, 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-dark">
              <tr>
                <th>Total</th>
                <th class="text-end">&euro;<?= number_format($yearTotalIncome, 2) ?></th>
                <th class="text-end">&euro;<?= number_format($yearTotalExpense, 2) ?></th>
                <th class="text-end">&euro;<?= number_format($yearTotalIncome - $yearTotalExpense, 2) ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

  <?php else: ?>
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-list-ul"></i> Transactions</span>
        <?php if ($canEdit): ?>
          <div class="btn-group">
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
              <i class="bi bi-plus-lg"></i> Add Income
            </button>
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
              <i class="bi bi-dash-lg"></i> Add Expense
            </button>
          </div>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form method="GET" class="row g-3 mb-4">
          <input type="hidden" name="club_id" value="<?= $clubId ?>">
          <div class="col-auto">
            <select name="year" class="form-select">
              <option value="">All Years</option>
              <?php foreach ($availableYears as $y): ?>
                <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <select name="month" class="form-select">
              <option value="">All Months</option>
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $filterMonth == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-auto">
            <select name="category" class="form-select">
              <option value="">All Categories</option>
              <?php foreach ($categories as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filterCategory === $key ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="?club_id=<?= $clubId ?>" class="btn btn-outline-secondary">Clear</a>
          </div>
        </form>
        
        <?php if (empty($entries)): ?>
          <p class="text-muted text-center my-4">No transactions found.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th class="text-end">Amount</th>
                  <th>Added By</th>
                  <?php if ($canEdit): ?>
                    <th class="text-center">Actions</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($entries as $entry): ?>
                  <tr>
                    <td><?= date('d M Y', strtotime($entry['entry_date'])) ?></td>
                    <td>
                      <span class="badge bg-<?= $entry['entry_type'] === 'income' ? 'success' : 'danger' ?>">
                        <?= ucfirst($entry['entry_type']) ?>
                      </span>
                    </td>
                    <td><?= e($entry['description'] ?? '') ?></td>
                    <td><?= e($categories[$entry['category']] ?? ucfirst($entry['category'] ?? 'Other')) ?></td>
                    <td class="text-end <?= $entry['entry_type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                      <?= $entry['entry_type'] === 'income' ? '+' : '-' ?>&euro;<?= number_format((float)$entry['amount'], 2) ?>
                    </td>
                    <td><?= e($entry['created_by_name'] ?? 'Unknown') ?></td>
                    <?php if ($canEdit): ?>
                      <td class="text-center">
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this entry?')">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="addIncomeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="entry_type" value="income">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="bi bi-plus-lg"></i> Add Income</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Description</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Amount (&euro;)</label>
            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
              <?php foreach ($categories as $key => $label): ?>
                <option value="<?= $key ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Income</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addExpenseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="entry_type" value="expense">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="bi bi-dash-lg"></i> Add Expense</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Description</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Amount (&euro;)</label>
            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
              <?php foreach ($categories as $key => $label): ?>
                <option value="<?= $key ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Add Expense</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addAccountModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add_account">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-wallet2"></i> Add Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Account Name</label>
            <input type="text" name="account_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Account Type</label>
            <select name="account_type" class="form-select">
              <?php foreach ($accountTypes as $key => $label): ?>
                <option value="<?= $key ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Opening Balance (&euro;)</label>
            <input type="number" name="balance" class="form-control" step="0.01" value="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Notes (Optional)</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
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
<?php endif; ?>

<?php
club_admin_shell_end();
