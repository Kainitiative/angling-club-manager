<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/notifications.php';

require_login();
$userId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'mark_read') {
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
  } elseif ($action === 'mark_all_read') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
  } elseif ($action === 'delete') {
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
  } elseif ($action === 'delete_all_read') {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1");
    $stmt->execute([$userId]);
  }
  
  redirect('/public/notifications.php');
}

$stmt = $pdo->prepare("
  SELECT n.*, c.name as club_name, c.slug as club_slug
  FROM notifications n
  LEFT JOIN clubs c ON n.club_id = c.id
  WHERE n.user_id = ?
  ORDER BY n.is_read ASC, n.created_at DESC
  LIMIT 100
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = 0;
foreach ($notifications as $n) {
  if (!$n['is_read']) $unreadCount++;
}

$typeIcons = [
  'membership_approved' => 'bi-check-circle-fill text-success',
  'membership_rejected' => 'bi-x-circle-fill text-danger',
  'new_news' => 'bi-newspaper text-primary',
  'catch_of_month' => 'bi-trophy-fill text-warning',
  'competition_results' => 'bi-list-ol text-info',
  'message' => 'bi-envelope-fill text-primary',
  'default' => 'bi-bell-fill text-secondary',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - Angling Ireland</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .notification-item.unread {
      background-color: #f0f7ff;
      border-left: 3px solid #0d6efd;
    }
    .notification-icon {
      font-size: 1.5rem;
      width: 40px;
    }
  </style>
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="/public/dashboard.php">Angling Ireland</a>
      <div class="navbar-nav ms-auto">
        <a class="nav-link active" href="/public/notifications.php">Notifications</a>
        <a class="nav-link" href="/public/messages.php">Messages</a>
        <a class="nav-link" href="/public/dashboard.php">Dashboard</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">Notifications</h1>
        <?php if ($unreadCount > 0): ?>
          <span class="text-muted"><?= $unreadCount ?> unread</span>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($unreadCount > 0): ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn-outline-primary btn-sm">Mark All Read</button>
          </form>
        <?php endif; ?>
        <?php if (count($notifications) - $unreadCount > 0): ?>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete all read notifications?');">
            <input type="hidden" name="action" value="delete_all_read">
            <button type="submit" class="btn btn-outline-danger btn-sm">Clear Read</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($notifications)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bi bi-bell-slash display-4 text-muted mb-3 d-block"></i>
          <p class="text-muted mb-0">No notifications yet</p>
        </div>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="list-group list-group-flush">
          <?php foreach ($notifications as $notification): ?>
            <div class="list-group-item notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>">
              <div class="d-flex align-items-start">
                <div class="notification-icon me-3">
                  <i class="bi <?= $typeIcons[$notification['type']] ?? $typeIcons['default'] ?>"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                      <?php if ($notification['message']): ?>
                        <p class="mb-1 text-muted"><?= htmlspecialchars($notification['message']) ?></p>
                      <?php endif; ?>
                      <small class="text-muted">
                        <?= date('d M Y, H:i', strtotime($notification['created_at'])) ?>
                        <?php if ($notification['club_name']): ?>
                          &bull; <?= htmlspecialchars($notification['club_name']) ?>
                        <?php endif; ?>
                      </small>
                    </div>
                    <div class="btn-group btn-group-sm ms-2">
                      <?php if ($notification['link']): ?>
                        <a href="<?= htmlspecialchars($notification['link']) ?>" class="btn btn-outline-primary" title="View">
                          <i class="bi bi-eye"></i>
                        </a>
                      <?php endif; ?>
                      <?php if (!$notification['is_read']): ?>
                        <form method="post" class="d-inline">
                          <input type="hidden" name="action" value="mark_read">
                          <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                          <button type="submit" class="btn btn-outline-secondary" title="Mark as read">
                            <i class="bi bi-check"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
