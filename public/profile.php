<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout/member_shell.php';

require_login();

$userId = current_user_id();
$errors = [];
$success = false;

$stmt = $pdo->prepare("SELECT id, name, email, profile_picture_url, dob, phone, town, city, country, gender FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
  http_response_code(404);
  exit("User not found");
}

$stmt = $pdo->prepare("
  SELECT c.id, c.name, c.slug, c.logo_url, cm.membership_status, cm.joined_at
  FROM clubs c
  JOIN club_members cm ON c.id = cm.club_id
  WHERE cm.user_id = ?
  ORDER BY cm.joined_at DESC
  LIMIT 1
");
$stmt->execute([$userId]);
$memberClub = $stmt->fetch();

if (!$memberClub) {
  $stmt = $pdo->prepare("
    SELECT c.id, c.name, c.slug, c.logo_url, ca.admin_role, ca.created_at as joined_at
    FROM clubs c
    JOIN club_admins ca ON c.id = ca.club_id
    WHERE ca.user_id = ?
    ORDER BY ca.created_at DESC
    LIMIT 1
  ");
  $stmt->execute([$userId]);
  $adminClub = $stmt->fetch();
} else {
  $adminClub = null;
}

$userClub = $memberClub ?: $adminClub;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim((string)($_POST['name'] ?? ''));
  $dob = trim((string)($_POST['dob'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $town = trim((string)($_POST['town'] ?? ''));
  $city = trim((string)($_POST['city'] ?? ''));
  $country = trim((string)($_POST['country'] ?? ''));
  $gender = trim((string)($_POST['gender'] ?? ''));
  $profile_picture_url = trim((string)($_POST['profile_picture_url'] ?? ''));

  if ($name === '') {
    $errors[] = "Name is required.";
  }
  if (strlen($name) > 120) {
    $errors[] = "Name must be 120 characters or less.";
  }

  if (!$errors) {
    $stmt = $pdo->prepare("
      UPDATE users SET 
        name = ?,
        profile_picture_url = ?,
        dob = ?,
        phone = ?,
        town = ?,
        city = ?,
        country = ?,
        gender = ?,
        updated_at = NOW()
      WHERE id = ?
    ");
    $stmt->execute([
      $name,
      $profile_picture_url === '' ? null : $profile_picture_url,
      $dob === '' ? null : $dob,
      $phone === '' ? null : $phone,
      $town === '' ? null : $town,
      $city === '' ? null : $city,
      $country === '' ? null : $country,
      $gender === '' ? null : $gender,
      $userId
    ]);
    $success = true;

    $stmt = $pdo->prepare("SELECT id, name, email, profile_picture_url, dob, phone, town, city, country, gender FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
  }
}

$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&size=150&background=0D6EFD&color=fff';
$avatarUrl = $user['profile_picture_url'] ?: $defaultAvatar;

$pageTitle = 'Your Profile';
$currentPage = 'profile';
member_shell_start($pdo, ['title' => 'Your Profile', 'page' => 'profile', 'section' => 'Profile']);
?>

<style>
  .profile-picture {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #dee2e6;
  }
</style>

<h1 class="mb-4">Your Profile</h1>

<?php if ($success): ?>
  <div class="alert alert-success">Profile updated successfully!</div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($userClub): ?>
  <div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
      <h6 class="mb-0">Your Club Membership</h6>
    </div>
    <div class="card-body">
      <div class="d-flex align-items-center flex-wrap gap-3">
        <?php if (!empty($userClub['logo_url'])): ?>
          <img src="<?= e($userClub['logo_url']) ?>" alt="" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
        <?php else: ?>
          <div class="rounded d-flex align-items-center justify-content-center bg-primary text-white fw-bold" style="width: 50px; height: 50px; font-size: 1.25rem;">
            <?= strtoupper(substr($userClub['name'], 0, 1)) ?>
          </div>
        <?php endif; ?>
        <div class="flex-grow-1">
          <h5 class="mb-1">
            <a href="/public/club.php?slug=<?= e($userClub['slug']) ?>" class="text-decoration-none"><?= e($userClub['name']) ?></a>
          </h5>
          <div class="small text-muted">
            <?php if (isset($userClub['admin_role'])): ?>
              <span class="badge bg-warning text-dark"><?= ucfirst($userClub['admin_role']) ?></span>
            <?php elseif ($userClub['membership_status'] === 'active'): ?>
              <span class="badge bg-success">Member</span>
            <?php elseif ($userClub['membership_status'] === 'pending'): ?>
              <span class="badge bg-info">Pending Approval</span>
            <?php endif; ?>
            <?php if ($userClub['joined_at']): ?>
              &bull; Since <?= date('M j, Y', strtotime($userClub['joined_at'])) ?>
            <?php endif; ?>
          </div>
        </div>
        <a href="/public/club.php?slug=<?= e($userClub['slug']) ?>" class="btn btn-outline-primary btn-sm">View Club</a>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="card mb-4 bg-light">
    <div class="card-body text-center py-4">
      <p class="text-muted mb-2">You're not a member of any club yet.</p>
      <a href="/public/clubs.php" class="btn btn-primary btn-sm">Browse Clubs</a>
      <a href="/public/create_club.php" class="btn btn-outline-primary btn-sm">Create a Club</a>
    </div>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post">
      <div class="text-center mb-4">
        <img src="<?= e($avatarUrl) ?>" alt="Profile Picture" class="profile-picture mb-3">
        <div>
          <label class="form-label">Profile Picture URL</label>
          <input type="url" class="form-control" name="profile_picture_url" value="<?= e($user['profile_picture_url'] ?? '') ?>" placeholder="https://example.com/photo.jpg">
          <div class="form-text">Enter a URL to your profile picture, or leave blank for default.</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Name *</label>
          <input type="text" class="form-control" name="name" value="<?= e($user['name']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
          <div class="form-text">Email cannot be changed.</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Date of Birth</label>
          <input type="date" class="form-control" name="dob" value="<?= e($user['dob'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Gender</label>
          <select class="form-select" name="gender">
            <option value="">-- Select --</option>
            <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
            <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
            <option value="prefer_not_to_say" <?= ($user['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : '' ?>>Prefer not to say</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Phone</label>
          <input type="tel" class="form-control" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+353 1234567">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Town</label>
          <input type="text" class="form-control" name="town" value="<?= e($user['town'] ?? '') ?>" placeholder="e.g. Tallaght">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">City</label>
          <input type="text" class="form-control" name="city" value="<?= e($user['city'] ?? '') ?>" placeholder="e.g. Dublin">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Country</label>
          <input type="text" class="form-control" name="country" value="<?= e($user['country'] ?? '') ?>" placeholder="e.g. Ireland">
        </div>
      </div>

      <div class="mt-3 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">Save Profile</button>
        <a href="/public/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php member_shell_end(); ?>
