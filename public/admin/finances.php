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

if (!$adminRow) {
  http_response_code(403);
  exit('You are not an admin of this club');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add') {
    $entryType = $_POST['entry_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $entryDate = $_POST['entry_date'] ?? date('Y-m-d');
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
        INSERT INTO club_finances (club_id, entry_type, title, amount, entry_date, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$clubId, $entryType, $title, $amount, $entryDate, $notes ?: null, $userId]);
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

$stmt = $pdo->prepare("
  SELECT cf.*, u.name as created_by_name
  FROM club_finances cf
  JOIN users u ON cf.created_by = u.id
  WHERE cf.club_id = ?
  ORDER BY cf.entry_date DESC, cf.created_at DESC
");
$stmt->execute([$clubId]);
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
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card summary-card income">
        <div class="card-body text-center py-4">
          <div class="small opacity-75 mb-1">Total Income</div>
          <div class="fs-3 fw-bold">&euro;<?= number_format($totalIncome, 2) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card summary-card expense">
        <div class="card-body text-center py-4">
          <div class="small opacity-75 mb-1">Total Expenses</div>
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

  <div class="row">
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
              <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Membership fees, Equipment purchase">
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
    
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Transaction History</h5>
        </div>
        <div class="card-body">
          <?php if (empty($entries)): ?>
            <div class="text-center py-4">
              <p class="text-muted mb-0">No financial entries yet. Add your first entry to start tracking.</p>
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
                      <form method="post" class="d-inline" onsubmit="return confirm('Delete this entry?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                        <button type="submit" class="btn btn-link btn-sm text-danger p-0">Delete</button>
                      </form>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
