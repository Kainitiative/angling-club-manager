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
$isAdmin = (bool)$adminRow;

$stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
$stmt->execute([$clubId, $userId]);
$memberRow = $stmt->fetch();
$committeeRole = $memberRow['committee_role'] ?? 'member';

$committeeRolesAllowedToView = ['chairperson', 'secretary', 'treasurer', 'pro', 'safety_officer', 'child_liaison_officer'];
$canView = $isAdmin || in_array($committeeRole, $committeeRolesAllowedToView);
$canEdit = $isAdmin || in_array($committeeRole, ['chairperson', 'treasurer']);

if (!$canView) {
  http_response_code(403);
  exit('Access denied. Only committee members can view club finances.');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add') {
    $entryType = $_POST['entry_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $entryDate = $_POST['entry_date'] ?? date('Y-m-d');
    $category = $_POST['category'] ?? 'other';
    $notes = trim($_POST['notes'] ?? '');
    
    if (!in_array($entryType, ['income', 'expense'])) {
      $message = 'Please select income or expense.';
      $messageType = 'danger';
    } elseif (empty($title)) {
      $message = 'Please enter a title.';
      $messageType = 'danger';
    } elseif ($amount <= 0) {
      $message = 'Please enter a valid amount.';
      $messageType = 'danger';
    } else {
      $stmt = $pdo->prepare("
        INSERT INTO club_finances (club_id, entry_type, title, amount, entry_date, category, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $entryType, $title, $amount, $entryDate, $category, $notes ?: null, $userId]);
      $message = ucfirst($entryType) . ' entry added successfully.';
      $messageType = 'success';
    }
  } elseif ($action === 'delete') {
    $entryId = (int)($_POST['entry_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM club_finances WHERE id = ? AND club_id = ?");
    $stmt->execute([$entryId, $clubId]);
    $message = 'Entry deleted.';
    $messageType = 'info';
  }
}

$whereClause = "cf.club_id = ?";
$params = [$clubId];

if ($filterMonth && $filterYear) {
  $whereClause .= " AND MONTH(cf.entry_date) = ? AND YEAR(cf.entry_date) = ?";
  $params[] = (int)$filterMonth;
  $params[] = (int)$filterYear;
} elseif ($filterYear) {
  $whereClause .= " AND YEAR(cf.entry_date) = ?";
  $params[] = (int)$filterYear;
}

if ($filterCategory) {
  $whereClause .= " AND cf.category = ?";
  $params[] = $filterCategory;
}

$stmt = $pdo->prepare("
  SELECT cf.*, u.name as created_by_name
  FROM club_finances cf
  JOIN users u ON cf.created_by = u.id
  WHERE $whereClause
  ORDER BY cf.entry_date DESC, cf.created_at DESC
");
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

$stmt = $pdo->prepare("SELECT DISTINCT YEAR(entry_date) as year FROM club_finances WHERE club_id = ? ORDER BY year DESC");
$stmt->execute([$clubId]);
$availableYears = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($availableYears)) {
  $availableYears = [date('Y')];
}

$reportData = [];
if ($showReport) {
  $reportYear = $filterYear ?: date('Y');
  $stmt = $pdo->prepare("
    SELECT 
      MONTH(entry_date) as month,
      category,
      entry_type,
      SUM(amount) as total
    FROM club_finances
    WHERE club_id = ? AND YEAR(entry_date) = ?
    GROUP BY MONTH(entry_date), category, entry_type
    ORDER BY MONTH(entry_date)
  ");
  $stmt->execute([$clubId, $reportYear]);
  $rawReport = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  
  for ($m = 1; $m <= 12; $m++) {
    $reportData[$m] = [
      'month_name' => $monthNames[$m],
      'income' => 0,
      'expense' => 0,
      'categories' => [],
    ];
  }
  
  foreach ($rawReport as $row) {
    $m = (int)$row['month'];
    $cat = $row['category'];
    $type = $row['entry_type'];
    $total = (float)$row['total'];
    
    if ($type === 'income') {
      $reportData[$m]['income'] += $total;
    } else {
      $reportData[$m]['expense'] += $total;
    }
    
    if (!isset($reportData[$m]['categories'][$cat])) {
      $reportData[$m]['categories'][$cat] = ['income' => 0, 'expense' => 0];
    }
    $reportData[$m]['categories'][$cat][$type] += $total;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Club Finances - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .finance-card {
      border-left: 4px solid #dee2e6;
    }
    .finance-card.income {
      border-left-color: #198754;
      background: #f8fff8;
    }
    .finance-card.expense {
      border-left-color: #dc3545;
      background: #fff8f8;
    }
    .summary-card {
      border-radius: 12px;
    }
    .summary-card.income {
      background: linear-gradient(135deg, #198754 0%, #28a745 100%);
      color: white;
    }
    .summary-card.expense {
      background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
      color: white;
    }
    .summary-card.balance {
      background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
      color: white;
    }
    .summary-card.balance.negative {
      background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    }
    .category-badge {
      font-size: 0.7rem;
      padding: 2px 6px;
    }
    .report-table th {
      background: #f8f9fa;
    }
    .nav-pills .nav-link.active {
      background: #1e3a5f;
    }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
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
      <h1 class="mb-1">Club Finances</h1>
      <p class="text-muted mb-0"><?= e($club['name']) ?></p>
    </div>
    <div>
      <ul class="nav nav-pills">
        <li class="nav-item">
          <a class="nav-link <?= !$showReport ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>">Transactions</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $showReport ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&report=1&year=<?= $filterYear ?: date('Y') ?>">Summary Report</a>
        </li>
      </ul>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($showReport): ?>
    
    <div class="card mb-4">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Financial Summary Report</h5>
        <form method="get" class="d-flex gap-2">
          <input type="hidden" name="club_id" value="<?= $clubId ?>">
          <input type="hidden" name="report" value="1">
          <select name="year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            <?php foreach ($availableYears as $y): ?>
              <option value="<?= $y ?>" <?= ($filterYear ?: date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover report-table">
            <thead>
              <tr>
                <th>Month</th>
                <th class="text-end text-success">Income</th>
                <th class="text-end text-danger">Expenses</th>
                <th class="text-end">Net</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $yearIncome = 0;
              $yearExpense = 0;
              foreach ($reportData as $m => $data): 
                $net = $data['income'] - $data['expense'];
                $yearIncome += $data['income'];
                $yearExpense += $data['expense'];
              ?>
                <tr>
                  <td>
                    <strong><?= $data['month_name'] ?></strong>
                    <?php if (!empty($data['categories'])): ?>
                      <div class="small text-muted">
                        <?php foreach ($data['categories'] as $catKey => $catData): ?>
                          <?php if ($catData['income'] > 0 || $catData['expense'] > 0): ?>
                            <span class="me-2"><?= $categories[$catKey] ?? ucfirst($catKey) ?>: 
                              <?php if ($catData['income'] > 0): ?>
                                <span class="text-success">+&euro;<?= number_format($catData['income'], 2) ?></span>
                              <?php endif; ?>
                              <?php if ($catData['expense'] > 0): ?>
                                <span class="text-danger">-&euro;<?= number_format($catData['expense'], 2) ?></span>
                              <?php endif; ?>
                            </span>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td class="text-end text-success"><?= $data['income'] > 0 ? '&euro;' . number_format($data['income'], 2) : '-' ?></td>
                  <td class="text-end text-danger"><?= $data['expense'] > 0 ? '&euro;' . number_format($data['expense'], 2) : '-' ?></td>
                  <td class="text-end <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?php if ($data['income'] > 0 || $data['expense'] > 0): ?>
                      <?= $net >= 0 ? '+' : '' ?>&euro;<?= number_format($net, 2) ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-dark">
              <tr>
                <th>Year Total</th>
                <th class="text-end">&euro;<?= number_format($yearIncome, 2) ?></th>
                <th class="text-end">&euro;<?= number_format($yearExpense, 2) ?></th>
                <th class="text-end <?= ($yearIncome - $yearExpense) >= 0 ? '' : 'text-danger' ?>">
                  <?= ($yearIncome - $yearExpense) >= 0 ? '+' : '' ?>&euro;<?= number_format($yearIncome - $yearExpense, 2) ?>
                </th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-white">
        <h5 class="mb-0">Category Breakdown</h5>
      </div>
      <div class="card-body">
        <?php
        $categoryTotals = [];
        foreach ($reportData as $data) {
          foreach ($data['categories'] as $catKey => $catData) {
            if (!isset($categoryTotals[$catKey])) {
              $categoryTotals[$catKey] = ['income' => 0, 'expense' => 0];
            }
            $categoryTotals[$catKey]['income'] += $catData['income'];
            $categoryTotals[$catKey]['expense'] += $catData['expense'];
          }
        }
        arsort($categoryTotals);
        ?>
        <?php if (empty($categoryTotals)): ?>
          <p class="text-muted text-center mb-0">No data for this year.</p>
        <?php else: ?>
          <div class="row">
            <?php foreach ($categoryTotals as $catKey => $catData): ?>
              <div class="col-md-4 mb-3">
                <div class="card h-100">
                  <div class="card-body">
                    <h6 class="card-title"><?= $categories[$catKey] ?? ucfirst($catKey) ?></h6>
                    <div class="d-flex justify-content-between">
                      <span class="text-success">+&euro;<?= number_format($catData['income'], 2) ?></span>
                      <span class="text-danger">-&euro;<?= number_format($catData['expense'], 2) ?></span>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card summary-card income">
          <div class="card-body text-center py-4">
            <div class="small opacity-75 mb-1">Total Income<?= $filterMonth || $filterYear || $filterCategory ? ' (Filtered)' : '' ?></div>
            <div class="fs-3 fw-bold">&euro;<?= number_format($totalIncome, 2) ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card summary-card expense">
          <div class="card-body text-center py-4">
            <div class="small opacity-75 mb-1">Total Expenses<?= $filterMonth || $filterYear || $filterCategory ? ' (Filtered)' : '' ?></div>
            <div class="fs-3 fw-bold">&euro;<?= number_format($totalExpense, 2) ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card summary-card balance <?= $balance < 0 ? 'negative' : '' ?>">
          <div class="card-body text-center py-4">
            <div class="small opacity-75 mb-1">Balance</div>
            <div class="fs-3 fw-bold"><?= $balance < 0 ? '-' : '' ?>&euro;<?= number_format(abs($balance), 2) ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
          <input type="hidden" name="club_id" value="<?= $clubId ?>">
          <div class="col-auto">
            <label class="form-label small">Month</label>
            <select name="month" class="form-select form-select-sm">
              <option value="">All Months</option>
              <?php 
              $months = ['1' => 'January', '2' => 'February', '3' => 'March', '4' => 'April', '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August', '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
              foreach ($months as $num => $name): ?>
                <option value="<?= $num ?>" <?= $filterMonth == $num ? 'selected' : '' ?>><?= $name ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label small">Year</label>
            <select name="year" class="form-select form-select-sm">
              <option value="">All Years</option>
              <?php foreach ($availableYears as $y): ?>
                <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label small">Category</label>
            <select name="category" class="form-select form-select-sm">
              <option value="">All Categories</option>
              <?php foreach ($categories as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filterCategory == $key ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if ($filterMonth || $filterYear || $filterCategory): ?>
              <a href="?club_id=<?= $clubId ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <?php if ($canEdit): ?>
      <div class="col-lg-4 mb-4">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="mb-0">Add Entry</h5>
          </div>
          <div class="card-body">
            <form method="post">
              <input type="hidden" name="action" value="add">
              
              <div class="mb-3">
                <label class="form-label">Type</label>
                <div class="btn-group w-100" role="group">
                  <input type="radio" class="btn-check" name="entry_type" id="type_income" value="income" checked>
                  <label class="btn btn-outline-success" for="type_income">Income</label>
                  <input type="radio" class="btn-check" name="entry_type" id="type_expense" value="expense">
                  <label class="btn btn-outline-danger" for="type_expense">Expense</label>
                </div>
              </div>

              <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category">
                  <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= $key ?>"><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="mb-3">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Annual membership, New rods">
              </div>
              
              <div class="mb-3">
                <label for="amount" class="form-label">Amount (&euro;) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" required placeholder="0.00">
              </div>
              
              <div class="mb-3">
                <label for="entry_date" class="form-label">Date</label>
                <input type="date" class="form-control" id="entry_date" name="entry_date" value="<?= date('Y-m-d') ?>">
              </div>
              
              <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Optional details"></textarea>
              </div>
              
              <button type="submit" class="btn btn-primary w-100">Add Entry</button>
            </form>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <div class="<?= $canEdit ? 'col-lg-8' : 'col-12' ?>">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="mb-0">Transaction History</h5>
          </div>
          <div class="card-body">
            <?php if (empty($entries)): ?>
              <div class="text-center py-4">
                <p class="text-muted mb-0">No financial entries<?= ($filterMonth || $filterYear || $filterCategory) ? ' matching your filters' : ' yet' ?>.</p>
              </div>
            <?php else: ?>
              <div class="list-group list-group-flush">
                <?php foreach ($entries as $entry): ?>
                  <div class="list-group-item finance-card <?= $entry['entry_type'] ?> mb-2 rounded">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <div class="d-flex align-items-center mb-1">
                          <?php if ($entry['entry_type'] === 'income'): ?>
                            <span class="badge bg-success me-2">Income</span>
                          <?php else: ?>
                            <span class="badge bg-danger me-2">Expense</span>
                          <?php endif; ?>
                          <span class="badge bg-secondary category-badge me-2"><?= $categories[$entry['category'] ?? 'other'] ?? 'Other' ?></span>
                          <strong><?= e($entry['title']) ?></strong>
                        </div>
                        <div class="small text-muted">
                          <?= date('j M Y', strtotime($entry['entry_date'])) ?>
                          &bull; Added by <?= e($entry['created_by_name']) ?>
                        </div>
                        <?php if ($entry['notes']): ?>
                          <div class="small text-muted mt-1"><?= e($entry['notes']) ?></div>
                        <?php endif; ?>
                      </div>
                      <div class="text-end">
                        <div class="fs-5 fw-bold <?= $entry['entry_type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                          <?= $entry['entry_type'] === 'income' ? '+' : '-' ?>&euro;<?= number_format((float)$entry['amount'], 2) ?>
                        </div>
                        <?php if ($canEdit): ?>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this entry?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                          <button type="submit" class="btn btn-link btn-sm text-danger p-0">Delete</button>
                        </form>
                        <?php endif; ?>
                      </div>
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
</body>
</html>
