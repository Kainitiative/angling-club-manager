<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_login();
$userId = current_user_id();

$clubId = (int)($_GET['club_id'] ?? 0);
if (!$clubId) {
  redirect('/public/dashboard.php');
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

$canManageNews = false;
if ($isAdmin) {
  $canManageNews = true;
} else {
  $stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
  $stmt->execute([$clubId, $userId]);
  $memberRow = $stmt->fetch();
  if ($memberRow && in_array($memberRow['committee_role'], ['chairperson', 'secretary'])) {
    $canManageNews = true;
  }
}

if (!$canManageNews) {
  http_response_code(403);
  exit('You do not have permission to manage news for this club.');
}

$message = '';
$messageType = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'add_news') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
    $publishNow = isset($_POST['publish_now']) ? 1 : 0;
    
    if ($title === '') {
      $errors[] = 'Title is required.';
    }
    if (strlen($title) > 255) {
      $errors[] = 'Title must be 255 characters or less.';
    }
    if ($content === '') {
      $errors[] = 'Content is required.';
    }
    
    if (!$errors) {
      $publishedAt = $publishNow ? date('Y-m-d H:i:s') : null;
      $stmt = $pdo->prepare("INSERT INTO club_news (club_id, author_id, title, content, is_pinned, published_at) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->execute([$clubId, $userId, $title, $content, $isPinned, $publishedAt]);
      $message = 'News article added successfully!';
      $messageType = 'success';
    }
  } elseif ($action === 'delete_news') {
    $newsId = (int)($_POST['news_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM club_news WHERE id = ? AND club_id = ?");
    $stmt->execute([$newsId, $clubId]);
    $message = 'News article deleted.';
    $messageType = 'info';
  } elseif ($action === 'toggle_pin') {
    $newsId = (int)($_POST['news_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE club_news SET is_pinned = NOT is_pinned WHERE id = ? AND club_id = ?");
    $stmt->execute([$newsId, $clubId]);
    $message = 'Pin status updated.';
    $messageType = 'success';
  } elseif ($action === 'publish') {
    $newsId = (int)($_POST['news_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE club_news SET published_at = NOW() WHERE id = ? AND club_id = ? AND published_at IS NULL");
    $stmt->execute([$newsId, $clubId]);
    $message = 'Article published.';
    $messageType = 'success';
  } elseif ($action === 'unpublish') {
    $newsId = (int)($_POST['news_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE club_news SET published_at = NULL WHERE id = ? AND club_id = ?");
    $stmt->execute([$newsId, $clubId]);
    $message = 'Article unpublished.';
    $messageType = 'info';
  } elseif ($action === 'update_news') {
    $newsId = (int)($_POST['news_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if ($title === '') {
      $errors[] = 'Title is required.';
    }
    if (strlen($title) > 255) {
      $errors[] = 'Title must be 255 characters or less.';
    }
    if ($content === '') {
      $errors[] = 'Content is required.';
    }
    
    if (!$errors) {
      $stmt = $pdo->prepare("UPDATE club_news SET title = ?, content = ?, updated_at = NOW() WHERE id = ? AND club_id = ?");
      $stmt->execute([$title, $content, $newsId, $clubId]);
      $message = 'News article updated.';
      $messageType = 'success';
    }
  }
}

$stmt = $pdo->prepare("
  SELECT n.*, u.name as author_name 
  FROM club_news n 
  JOIN users u ON n.author_id = u.id 
  WHERE n.club_id = ? 
  ORDER BY n.is_pinned DESC, n.created_at DESC
");
$stmt->execute([$clubId]);
$newsItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editNewsId = (int)($_GET['edit'] ?? 0);
$editNews = null;
if ($editNewsId) {
  foreach ($newsItems as $item) {
    if ((int)$item['id'] === $editNewsId) {
      $editNews = $item;
      break;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage News - <?= htmlspecialchars($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="/public/dashboard.php">Angling Ireland</a>
      <div class="navbar-nav ms-auto">
        <a class="nav-link" href="/public/club.php?slug=<?= htmlspecialchars($club['slug']) ?>">View Club</a>
        <a class="nav-link" href="/public/dashboard.php">Dashboard</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">Manage News</h1>
        <p class="text-muted mb-0"><?= htmlspecialchars($club['name']) ?></p>
      </div>
      <a href="/public/club.php?slug=<?= htmlspecialchars($club['slug']) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Club
      </a>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-lg-4 mb-4">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0"><?= $editNews ? 'Edit Article' : 'Add News Article' ?></h5>
          </div>
          <div class="card-body">
            <form method="post" action="<?= $editNews ? '?club_id=' . $clubId : '' ?>">
              <input type="hidden" name="action" value="<?= $editNews ? 'update_news' : 'add_news' ?>">
              <?php if ($editNews): ?>
                <input type="hidden" name="news_id" value="<?= $editNews['id'] ?>">
              <?php endif; ?>
              
              <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" maxlength="255" 
                       value="<?= htmlspecialchars($editNews['title'] ?? '') ?>" required>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Content <span class="text-danger">*</span></label>
                <textarea name="content" class="form-control" rows="6" required><?= htmlspecialchars($editNews['content'] ?? '') ?></textarea>
                <div class="form-text">Plain text. Line breaks will be preserved.</div>
              </div>
              
              <?php if (!$editNews): ?>
                <div class="mb-3">
                  <div class="form-check">
                    <input type="checkbox" name="is_pinned" class="form-check-input" id="isPinned">
                    <label class="form-check-label" for="isPinned">Pin to top</label>
                  </div>
                </div>
                
                <div class="mb-3">
                  <div class="form-check">
                    <input type="checkbox" name="publish_now" class="form-check-input" id="publishNow" checked>
                    <label class="form-check-label" for="publishNow">Publish immediately</label>
                  </div>
                  <div class="form-text">Uncheck to save as draft.</div>
                </div>
              <?php endif; ?>
              
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                  <?= $editNews ? 'Update Article' : 'Add Article' ?>
                </button>
                <?php if ($editNews): ?>
                  <a href="?club_id=<?= $clubId ?>" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">News Articles (<?= count($newsItems) ?>)</h5>
          </div>
          <?php if (empty($newsItems)): ?>
            <div class="card-body text-center text-muted py-5">
              <i class="bi bi-newspaper display-4 mb-3 d-block"></i>
              <p>No news articles yet. Add your first one!</p>
            </div>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($newsItems as $news): ?>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <?php if ($news['is_pinned']): ?>
                          <span class="badge bg-warning text-dark"><i class="bi bi-pin-fill"></i> Pinned</span>
                        <?php endif; ?>
                        <?php if ($news['published_at']): ?>
                          <span class="badge bg-success">Published</span>
                        <?php else: ?>
                          <span class="badge bg-secondary">Draft</span>
                        <?php endif; ?>
                      </div>
                      <h6 class="mb-1"><?= htmlspecialchars($news['title']) ?></h6>
                      <p class="text-muted small mb-1">
                        By <?= htmlspecialchars($news['author_name']) ?> 
                        &bull; <?= date('d M Y', strtotime($news['created_at'])) ?>
                        <?php if ($news['published_at']): ?>
                          &bull; Published <?= date('d M Y', strtotime($news['published_at'])) ?>
                        <?php endif; ?>
                      </p>
                      <p class="mb-0 text-truncate" style="max-width: 500px;">
                        <?= htmlspecialchars(substr($news['content'], 0, 150)) ?><?= strlen($news['content']) > 150 ? '...' : '' ?>
                      </p>
                    </div>
                    <div class="btn-group-vertical btn-group-sm ms-3">
                      <a href="?club_id=<?= $clubId ?>&edit=<?= $news['id'] ?>" class="btn btn-outline-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle_pin">
                        <input type="hidden" name="news_id" value="<?= $news['id'] ?>">
                        <button type="submit" class="btn btn-outline-warning" title="<?= $news['is_pinned'] ? 'Unpin' : 'Pin' ?>">
                          <i class="bi bi-pin<?= $news['is_pinned'] ? '-fill' : '' ?>"></i>
                        </button>
                      </form>
                      <?php if ($news['published_at']): ?>
                        <form method="post" class="d-inline">
                          <input type="hidden" name="action" value="unpublish">
                          <input type="hidden" name="news_id" value="<?= $news['id'] ?>">
                          <button type="submit" class="btn btn-outline-secondary" title="Unpublish">
                            <i class="bi bi-eye-slash"></i>
                          </button>
                        </form>
                      <?php else: ?>
                        <form method="post" class="d-inline">
                          <input type="hidden" name="action" value="publish">
                          <input type="hidden" name="news_id" value="<?= $news['id'] ?>">
                          <button type="submit" class="btn btn-outline-success" title="Publish">
                            <i class="bi bi-eye"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                      <form method="post" class="d-inline" onsubmit="return confirm('Delete this article?');">
                        <input type="hidden" name="action" value="delete_news">
                        <input type="hidden" name="news_id" value="<?= $news['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
