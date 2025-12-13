<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

require_login();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Angling Club Manager</title>
  <style>
    body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
    h1 { color: #333; }
    .user-info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 30px; }
    .clubs-section { margin-top: 30px; }
    .club-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
    button { background: #0066cc; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
    button:hover { background: #0052a3; }
  </style>
</head>
<body>
  <h1>Dashboard</h1>
  
  <div class="user-info">
    <p><strong>Welcome, <?php echo htmlspecialchars($user['name']); ?></strong></p>
    <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
    <p><a href="logout.php">Logout</a></p>
  </div>

  <div class="clubs-section">
    <h2>Your Clubs</h2>
    <p>
      <a href="create-club.php" style="display: inline-block; background: #0066cc; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">+ Create Club</a>
    </p>

    <?php
    $userId = current_user_id();
    $stmt = $pdo->prepare("
      SELECT c.id, c.name, c.contact_email, ca.admin_role
      FROM clubs c
      JOIN club_admins ca ON c.id = ca.club_id
      WHERE ca.user_id = ?
      ORDER BY c.created_at DESC
    ");
    $stmt->execute([$userId]);
    $clubs = $stmt->fetchAll();

    if (empty($clubs)) {
      echo '<p>You haven\'t created any clubs yet. <a href="create-club.php">Create one now</a>!</p>';
    } else {
      foreach ($clubs as $club) {
        echo '<div class="club-item">';
        echo '<h3>' . htmlspecialchars($club['name']) . '</h3>';
        echo '<p>Role: ' . htmlspecialchars($club['admin_role']) . '</p>';
        if ($club['contact_email']) {
          echo '<p>Contact: ' . htmlspecialchars($club['contact_email']) . '</p>';
        }
        echo '</div>';
      }
    }
    ?>
  </div>
</body>
</html>
