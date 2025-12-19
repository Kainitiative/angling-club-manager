<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/notifications.php';

require_login();
$userId = current_user_id();

$stmt = $pdo->prepare("
  SELECT c.id, c.name, c.slug FROM clubs c
  JOIN club_members cm ON c.id = cm.club_id
  WHERE cm.user_id = ? AND cm.membership_status = 'active'
  UNION
  SELECT c.id, c.name, c.slug FROM clubs c
  JOIN club_admins ca ON c.id = ca.club_id
  WHERE ca.user_id = ?
");
$stmt->execute([$userId, $userId]);
$userClubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedClubId = (int)($_GET['club_id'] ?? ($userClubs[0]['id'] ?? 0));
$selectedClub = null;
foreach ($userClubs as $c) {
  if ((int)$c['id'] === $selectedClubId) {
    $selectedClub = $c;
    break;
  }
}

$isAdmin = false;
$canSendAnnouncement = false;
if ($selectedClubId) {
  $stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
  $stmt->execute([$selectedClubId, $userId]);
  $isAdmin = (bool)$stmt->fetch();
  
  if (!$isAdmin) {
    $stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
    $stmt->execute([$selectedClubId, $userId]);
    $memberRow = $stmt->fetch();
    $canSendAnnouncement = $memberRow && in_array($memberRow['committee_role'], ['chairperson', 'secretary']);
  } else {
    $canSendAnnouncement = true;
  }
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedClubId) {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'send_message') {
    $recipientId = $_POST['recipient_id'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $isAnnouncement = isset($_POST['is_announcement']) && $canSendAnnouncement ? 1 : 0;
    
    $errors = [];
    if ($subject === '') $errors[] = 'Subject is required.';
    if ($body === '') $errors[] = 'Message body is required.';
    if (!$isAnnouncement && $recipientId === '') $errors[] = 'Please select a recipient.';
    
    if (!$errors) {
      $stmt = $pdo->prepare("INSERT INTO messages (club_id, sender_id, recipient_id, subject, body, is_announcement) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->execute([
        $selectedClubId,
        $userId,
        $isAnnouncement ? null : (int)$recipientId,
        $subject,
        $body,
        $isAnnouncement
      ]);
      
      if ($isAnnouncement) {
        $stmt = $pdo->prepare("
          SELECT user_id FROM club_members WHERE club_id = ? AND membership_status = 'active' AND user_id != ?
          UNION
          SELECT user_id FROM club_admins WHERE club_id = ? AND user_id != ?
        ");
        $stmt->execute([$selectedClubId, $userId, $selectedClubId, $userId]);
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($recipients as $recipientUserId) {
          create_notification(
            $pdo,
            (int)$recipientUserId,
            'message',
            "Club Announcement: {$subject}",
            substr($body, 0, 100) . (strlen($body) > 100 ? '...' : ''),
            "/public/messages.php?club_id={$selectedClubId}",
            $selectedClubId
          );
        }
      } else {
        create_notification(
          $pdo,
          (int)$recipientId,
          'message',
          "New Message: {$subject}",
          substr($body, 0, 100) . (strlen($body) > 100 ? '...' : ''),
          "/public/messages.php?club_id={$selectedClubId}",
          $selectedClubId
        );
      }
      
      $message = $isAnnouncement ? 'Announcement sent to all club members!' : 'Message sent!';
      $messageType = 'success';
    } else {
      $message = implode(' ', $errors);
      $messageType = 'danger';
    }
  } elseif ($action === 'mark_read') {
    $msgId = (int)($_POST['message_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND (recipient_id = ? OR (is_announcement = 1 AND recipient_id IS NULL))");
    $stmt->execute([$msgId, $userId]);
  } elseif ($action === 'delete') {
    $msgId = (int)($_POST['message_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND (sender_id = ? OR recipient_id = ?)");
    $stmt->execute([$msgId, $userId, $userId]);
  }
}

$clubMembers = [];
if ($selectedClubId) {
  $stmt = $pdo->prepare("
    SELECT u.id, u.name, 'admin' as role FROM users u
    JOIN club_admins ca ON u.id = ca.user_id
    WHERE ca.club_id = ? AND u.id != ?
    UNION
    SELECT u.id, u.name, cm.committee_role as role FROM users u
    JOIN club_members cm ON u.id = cm.user_id
    WHERE cm.club_id = ? AND cm.membership_status = 'active' AND u.id != ?
    ORDER BY name
  ");
  $stmt->execute([$selectedClubId, $userId, $selectedClubId, $userId]);
  $clubMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$inboxMessages = [];
$sentMessages = [];
if ($selectedClubId) {
  $stmt = $pdo->prepare("
    SELECT m.*, u.name as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.club_id = ? AND (m.recipient_id = ? OR (m.is_announcement = 1 AND m.recipient_id IS NULL))
    ORDER BY m.created_at DESC
    LIMIT 50
  ");
  $stmt->execute([$selectedClubId, $userId]);
  $inboxMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  $stmt = $pdo->prepare("
    SELECT m.*, u.name as recipient_name
    FROM messages m
    LEFT JOIN users u ON m.recipient_id = u.id
    WHERE m.club_id = ? AND m.sender_id = ?
    ORDER BY m.created_at DESC
    LIMIT 50
  ");
  $stmt->execute([$selectedClubId, $userId]);
  $sentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$activeTab = $_GET['tab'] ?? 'inbox';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages - Angling Club Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .message-item.unread {
      background-color: #f0f7ff;
      border-left: 3px solid #0d6efd;
    }
  </style>
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="/public/dashboard.php">Angling Club Manager</a>
      <div class="navbar-nav ms-auto">
        <a class="nav-link" href="/public/notifications.php">Notifications</a>
        <a class="nav-link active" href="/public/messages.php">Messages</a>
        <a class="nav-link" href="/public/dashboard.php">Dashboard</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 mb-0">Messages</h1>
      <?php if (count($userClubs) > 1): ?>
        <form class="d-flex align-items-center">
          <label class="me-2">Club:</label>
          <select name="club_id" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            <?php foreach ($userClubs as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (int)$c['id'] === $selectedClubId ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if (empty($userClubs)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bi bi-envelope-slash display-4 text-muted mb-3 d-block"></i>
          <p class="text-muted mb-0">You must be a member of a club to send messages.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="row">
        <div class="col-lg-4 mb-4">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Compose Message</h5>
            </div>
            <div class="card-body">
              <form method="post">
                <input type="hidden" name="action" value="send_message">
                
                <?php if ($canSendAnnouncement): ?>
                  <div class="mb-3">
                    <div class="form-check">
                      <input type="checkbox" name="is_announcement" class="form-check-input" id="isAnnouncement">
                      <label class="form-check-label" for="isAnnouncement">Send as announcement to all members</label>
                    </div>
                  </div>
                <?php endif; ?>
                
                <div class="mb-3" id="recipientGroup">
                  <label class="form-label">To</label>
                  <select name="recipient_id" class="form-select" id="recipientSelect">
                    <option value="">Select recipient...</option>
                    <?php foreach ($clubMembers as $member): ?>
                      <option value="<?= $member['id'] ?>">
                        <?= htmlspecialchars($member['name']) ?>
                        <?= $member['role'] !== 'member' ? '(' . ucfirst($member['role']) . ')' : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Subject</label>
                  <input type="text" name="subject" class="form-control" maxlength="255" required>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Message</label>
                  <textarea name="body" class="form-control" rows="4" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Send Message</button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-8">
          <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
              <a class="nav-link <?= $activeTab === 'inbox' ? 'active' : '' ?>" href="?club_id=<?= $selectedClubId ?>&tab=inbox">
                Inbox <?php $unread = count(array_filter($inboxMessages, fn($m) => !$m['is_read'])); if ($unread > 0): ?><span class="badge bg-primary"><?= $unread ?></span><?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= $activeTab === 'sent' ? 'active' : '' ?>" href="?club_id=<?= $selectedClubId ?>&tab=sent">Sent</a>
            </li>
          </ul>

          <?php if ($activeTab === 'inbox'): ?>
            <?php if (empty($inboxMessages)): ?>
              <div class="card">
                <div class="card-body text-center py-5">
                  <i class="bi bi-inbox display-4 text-muted mb-3 d-block"></i>
                  <p class="text-muted mb-0">No messages yet</p>
                </div>
              </div>
            <?php else: ?>
              <div class="card">
                <div class="list-group list-group-flush">
                  <?php foreach ($inboxMessages as $msg): ?>
                    <div class="list-group-item message-item <?= !$msg['is_read'] ? 'unread' : '' ?>">
                      <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                          <div class="d-flex align-items-center gap-2 mb-1">
                            <?php if ($msg['is_announcement']): ?>
                              <span class="badge bg-info">Announcement</span>
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($msg['subject']) ?></strong>
                          </div>
                          <p class="mb-1"><?= nl2br(htmlspecialchars($msg['body'])) ?></p>
                          <small class="text-muted">
                            From <?= htmlspecialchars($msg['sender_name']) ?>
                            &bull; <?= date('d M Y, H:i', strtotime($msg['created_at'])) ?>
                          </small>
                        </div>
                        <div class="btn-group btn-group-sm ms-2">
                          <?php if (!$msg['is_read']): ?>
                            <form method="post" class="d-inline">
                              <input type="hidden" name="action" value="mark_read">
                              <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                              <button type="submit" class="btn btn-outline-secondary" title="Mark as read">
                                <i class="bi bi-check"></i>
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <?php if (empty($sentMessages)): ?>
              <div class="card">
                <div class="card-body text-center py-5">
                  <i class="bi bi-send display-4 text-muted mb-3 d-block"></i>
                  <p class="text-muted mb-0">No sent messages</p>
                </div>
              </div>
            <?php else: ?>
              <div class="card">
                <div class="list-group list-group-flush">
                  <?php foreach ($sentMessages as $msg): ?>
                    <div class="list-group-item">
                      <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                          <div class="d-flex align-items-center gap-2 mb-1">
                            <?php if ($msg['is_announcement']): ?>
                              <span class="badge bg-info">Announcement</span>
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($msg['subject']) ?></strong>
                          </div>
                          <p class="mb-1"><?= nl2br(htmlspecialchars($msg['body'])) ?></p>
                          <small class="text-muted">
                            To <?= $msg['is_announcement'] ? 'All Members' : htmlspecialchars($msg['recipient_name'] ?? 'Unknown') ?>
                            &bull; <?= date('d M Y, H:i', strtotime($msg['created_at'])) ?>
                          </small>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('isAnnouncement')?.addEventListener('change', function() {
      const recipientGroup = document.getElementById('recipientGroup');
      const recipientSelect = document.getElementById('recipientSelect');
      if (this.checked) {
        recipientGroup.style.display = 'none';
        recipientSelect.removeAttribute('required');
      } else {
        recipientGroup.style.display = 'block';
        recipientSelect.setAttribute('required', 'required');
      }
    });
  </script>
</body>
</html>
