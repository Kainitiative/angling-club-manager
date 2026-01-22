<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something Went Wrong - Angling Ireland</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .error-card { max-width: 500px; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="error-card mx-auto">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="display-1 text-muted mb-3">🎣</div>
                <h1 class="h3 mb-3">Something Went Wrong</h1>
                <p class="text-muted mb-4">We're sorry, but something unexpected happened. Our team has been notified and we're working to fix it.</p>
                <a href="/" class="btn btn-primary">Return to Homepage</a>
            </div>
        </div>
        <p class="text-white-50 mt-4 small">&copy; <?= date('Y') ?> Angling Ireland</p>
    </div>
</div>
</body>
</html>
