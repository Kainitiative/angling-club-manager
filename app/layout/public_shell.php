<?php
/**
 * Public Shell - Layout for public-facing pages
 * (Homepage, Browse Clubs, Club Pages, etc.)
 * 
 * Usage:
 * $pageTitle = 'Browse Clubs';
 * include 'app/layout/public_shell.php';
 * public_shell_start($pdo);
 * // ... your content here ...
 * public_shell_end();
 */

require_once __DIR__ . '/../nav_config.php';

function public_shell_start($pdo = null, $options = []) {
    global $pageTitle;
    
    $pageTitle = $options['title'] ?? $pageTitle ?? 'Angling Club Manager';
    $isLoggedIn = function_exists('current_user_id') && current_user_id() > 0;
    $showLoginModal = $options['showLoginModal'] ?? false;
    $navStyle = $options['navStyle'] ?? 'dark'; // dark or transparent
    
    $nav = get_public_nav($isLoggedIn);
    
    include __DIR__ . '/header.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark <?= $navStyle === 'transparent' ? 'bg-transparent position-absolute w-100' : 'bg-dark' ?>">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">
            <i class="bi bi-water me-1"></i>
            Angling Club Manager
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/public/clubs.php">Browse Clubs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/public/competitions.php">Competitions</a>
                </li>
            </ul>
            <div class="d-flex gap-2">
                <?php foreach ($nav as $item): ?>
                    <?php if (!empty($item['modal'])): ?>
                        <a class="btn <?= $item['class'] ?> btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#<?= $item['modal'] ?>">
                            <?= e($item['label']) ?>
                        </a>
                    <?php else: ?>
                        <a class="btn <?= $item['class'] ?> btn-sm" href="<?= e($item['url']) ?>">
                            <?= e($item['label']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</nav>

<main>
<?php
}

function public_shell_end($options = []) {
    $showLoginModal = $options['showLoginModal'] ?? false;
?>
</main>

<footer class="bg-dark text-white py-4 mt-auto">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-muted">&copy; <?= date('Y') ?> Angling Club Manager</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="/public/clubs.php" class="text-muted text-decoration-none me-3">Browse Clubs</a>
                <a href="/public/competitions.php" class="text-muted text-decoration-none">Competitions</a>
            </div>
        </div>
    </div>
</footer>

<?php if ($showLoginModal): ?>
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Log In</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <form method="post" action="/">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Log In</button>
                </form>
                <p class="text-center mt-3 mb-0">
                    New here? <a href="/public/auth/register.php">Create an account</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
    include __DIR__ . '/footer.php';
}
