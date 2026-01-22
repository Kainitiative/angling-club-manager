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
    
    $pageTitle = $options['title'] ?? $pageTitle ?? 'Angling Ireland';
    $isLoggedIn = function_exists('current_user_id') && current_user_id() > 0;
    $showLoginModal = $options['showLoginModal'] ?? false;
    $navStyle = $options['navStyle'] ?? 'dark'; // dark or transparent
    
    $nav = get_public_nav($isLoggedIn);
    
    $customStyles = '
        .public-navbar {
            padding: 0.75rem 0;
        }
        
        .public-navbar .navbar-brand {
            font-size: 1.1rem;
        }
        
        .public-navbar .navbar-toggler {
            border: none;
            padding: 0.5rem;
            min-width: 44px;
            min-height: 44px;
        }
        
        .public-navbar .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .public-navbar .navbar-nav .nav-link {
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }
        
        .public-navbar .btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        @media (max-width: 991.98px) {
            .public-navbar .navbar-collapse {
                padding: 1rem 0;
            }
            
            .public-navbar .navbar-nav .nav-link {
                padding: 0.875rem 0;
            }
            
            .public-navbar .d-flex {
                flex-direction: column;
                width: 100%;
                gap: 0.5rem !important;
            }
            
            .public-navbar .btn {
                width: 100%;
            }
        }
        
        .public-main {
            min-height: calc(100vh - 200px);
        }
        
        .public-footer {
            padding: 2rem 0;
        }
        
        @media (max-width: 767.98px) {
            .public-footer {
                text-align: center;
            }
            
            .public-footer h6 {
                margin-top: 1.5rem;
            }
            
            .public-footer h6:first-child {
                margin-top: 0;
            }
        }
    ';
    
    include __DIR__ . '/header.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark public-navbar <?= $navStyle === 'transparent' ? 'bg-transparent position-absolute w-100' : 'bg-dark' ?>" style="z-index: 1030;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">
            <i class="bi bi-water me-1"></i>
            Angling Ireland
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
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
                        <a class="btn <?= $item['class'] ?>" href="#" data-bs-toggle="modal" data-bs-target="#<?= $item['modal'] ?>">
                            <?= e($item['label']) ?>
                        </a>
                    <?php else: ?>
                        <a class="btn <?= $item['class'] ?>" href="<?= e($item['url']) ?>">
                            <?= e($item['label']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</nav>

<main class="public-main">
<?php
}

function public_shell_end($options = []) {
    $showLoginModal = $options['showLoginModal'] ?? false;
?>
</main>

<footer class="bg-dark text-white mt-auto public-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <h6 class="text-white mb-2">Angling Ireland</h6>
                <p class="text-muted small mb-0">&copy; <?= date('Y') ?> Patrick Ryan Digital Design</p>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <h6 class="text-white mb-2">Explore</h6>
                <a href="/public/clubs.php" class="text-muted text-decoration-none d-block small mb-1">Browse Clubs</a>
                <a href="/public/competitions.php" class="text-muted text-decoration-none d-block small">Competitions</a>
            </div>
            <div class="col-md-4">
                <h6 class="text-white mb-2">Legal</h6>
                <a href="/public/legal/privacy.php" class="text-muted text-decoration-none d-block small mb-1">Privacy Policy</a>
                <a href="/public/legal/terms.php" class="text-muted text-decoration-none d-block small mb-1">Terms & Conditions</a>
                <a href="/public/legal/cookies.php" class="text-muted text-decoration-none d-block small">Cookie Policy</a>
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
