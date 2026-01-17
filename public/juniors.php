<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$userId = current_user_id();
$user = current_user();

// Fetch junior members managed by this user
$stmt = $pdo->prepare("SELECT * FROM users WHERE parent_id = ? AND is_junior = 1 ORDER BY name ASC");
$stmt->execute([$userId]);
$juniors = $stmt->fetchAll();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_junior') {
        $name = trim($_POST['name'] ?? '');
        $dob = $_POST['dob'] ?? '';
        $medical = trim($_POST['medical_notes'] ?? '');
        $consent = isset($_POST['consent']) ? 1 : 0;

        if (!$name) $errors[] = "Name is required.";
        if (!$dob) $errors[] = "Date of birth is required.";
        if (!$consent) $errors[] = "Guardian consent is required.";

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, dob, parent_id, is_junior, medical_notes, guardian_consent, consent_date) VALUES (?, ?, ?, ?, ?, 1, ?, ?, CURRENT_TIMESTAMP)");
            // Junior doesn't need email/password, using dummy unique email
            $dummyEmail = "junior_" . uniqid() . "@clubmanager.internal";
            $stmt->execute([$name, $dummyEmail, 'N/A', $dob, $userId, $medical, $consent]);
            
            $success = "Junior member added successfully.";
            // Refresh list
            $stmt = $pdo->prepare("SELECT * FROM users WHERE parent_id = ? AND is_junior = 1 ORDER BY name ASC");
            $stmt->execute([$userId]);
            $juniors = $stmt->fetchAll();
        }
    }
}

require_once __DIR__ . '/../app/layout/member_shell.php';
member_shell_start($pdo, ['title' => 'Junior Members', 'page' => 'juniors']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Junior Members</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJuniorModal">Add Junior Member</button>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
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

<div class="row g-4">
    <?php if (empty($juniors)): ?>
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body text-center py-5">
                    <p class="text-muted mb-0">You haven't added any junior members yet.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($juniors as $junior): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= e($junior['name']) ?></h5>
                        <p class="card-text small text-muted mb-2">DOB: <?= e($junior['dob']) ?></p>
                        <?php if ($junior['medical_notes']): ?>
                            <div class="mb-2">
                                <small class="fw-bold d-block">Medical Notes:</small>
                                <div class="p-2 bg-light rounded small"><?= nl2br(e($junior['medical_notes'])) ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex align-items-center mt-3">
                            <span class="badge bg-success me-2">Consent Active</span>
                            <small class="text-muted">Since <?= date('d M Y', strtotime($junior['consent_date'])) ?></small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Junior Modal -->
<div class="modal fade" id="addJuniorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Junior Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="add_junior">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Medical Notes / Allergies</label>
                        <textarea name="medical_notes" class="form-control" rows="3" placeholder="Any information coaches should be aware of..."></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="consent" id="consentCheck" required>
                        <label class="form-check-label" for="consentCheck">
                            I confirm I am the parent/guardian and give consent for this junior to participate in club activities.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Junior</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php member_shell_end(); ?>
