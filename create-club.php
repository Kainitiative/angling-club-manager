<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

require_login();
$user = current_user();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $contact_email = trim($_POST['contact_email'] ?? '');
  $location_text = trim($_POST['location_text'] ?? '');
  $about_text = trim($_POST['about_text'] ?? '');

  // Validation
  if (empty($name)) {
    $errors[] = "Club name is required";
  }

  if (empty($errors)) {
    try {
      // Insert club
      $stmt = $pdo->prepare("
        INSERT INTO clubs (name, contact_email, location_text, about_text, created_at)
        VALUES (?, ?, ?, ?, NOW())
      ");
      $stmt->execute([$name, $contact_email ?: null, $location_text ?: null, $about_text ?: null]);
      
      $clubId = $pdo->lastInsertId();

      // Insert user as owner in club_admins
      $stmt = $pdo->prepare("
        INSERT INTO club_admins (club_id, user_id, admin_role, created_at)
        VALUES (?, ?, 'owner', NOW())
      ");
      $stmt->execute([$clubId, current_user_id()]);

      $success = true;
      
      // Redirect after 2 seconds
      header("Refresh: 2; url=dashboard.php");
    } catch (Throwable $e) {
      $errors[] = "Failed to create club: " . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Club - Angling Club Manager</title>
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; }
    h1 { color: #333; }
    form { background: #f9f9f9; padding: 20px; border-radius: 5px; border: 1px solid #ddd; }
    .form-group { margin-bottom: 15px; }
    label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
    input, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif; box-sizing: border-box; }
    textarea { resize: vertical; min-height: 100px; }
    button { background: #0066cc; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
    button:hover { background: #0052a3; }
    .errors { background: #ffcccc; color: #cc0000; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #cc0000; }
    .success { background: #ccffcc; color: #00aa00; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #00aa00; }
    .success p { margin: 0; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <a href="dashboard.php">&larr; Back to Dashboard</a>
  
  <h1>Create New Club</h1>

  <?php if ($success): ?>
    <div class="success">
      <p><strong>✓ Club created successfully!</strong></p>
      <p>Redirecting to dashboard...</p>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="errors">
      <strong>Errors:</strong>
      <ul>
        <?php foreach ($errors as $error): ?>
          <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!$success): ?>
    <form method="POST">
      <div class="form-group">
        <label for="name">Club Name *</label>
        <input type="text" id="name" name="name" required>
      </div>

      <div class="form-group">
        <label for="contact_email">Contact Email</label>
        <input type="email" id="contact_email" name="contact_email">
      </div>

      <div class="form-group">
        <label for="location_text">Location</label>
        <input type="text" id="location_text" name="location_text">
      </div>

      <div class="form-group">
        <label for="about_text">About</label>
        <textarea id="about_text" name="about_text"></textarea>
      </div>

      <button type="submit">Create Club</button>
    </form>
  <?php endif; ?>
</body>
</html>
