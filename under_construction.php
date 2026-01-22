<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angling Ireland - Coming Soon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a5f2a 0%, #0d3d1a 50%, #1a3a4a 100%);
            color: #fff;
            padding: 20px;
        }
        
        .container {
            text-align: center;
            max-width: 600px;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        
        .tagline {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
        }
        
        .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }
        
        .description {
            font-size: 1.1rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
        }
        
        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .feature {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .divider {
            width: 60px;
            height: 3px;
            background: rgba(255, 255, 255, 0.4);
            margin: 2rem auto;
            border-radius: 2px;
        }
        
        .footer {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            h1 {
                font-size: 1.8rem;
            }
            .tagline {
                font-size: 1rem;
            }
            .logo {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🎣</div>
        <h1>Angling Ireland</h1>
        <p class="tagline">The Digital Hub for Irish Anglers</p>
        
        <div class="badge">🚧 Under Construction</div>
        
        <p class="description">
            We're building something special for the Irish angling community. 
            A free platform to help clubs, syndicates, and anglers connect, compete, and thrive.
        </p>
        
        <div class="features">
            <span class="feature">Club Management</span>
            <span class="feature">Catch Logging</span>
            <span class="feature">Competitions</span>
            <span class="feature">Member Portal</span>
        </div>
        
        <div class="divider"></div>
        
        <p class="footer">
            Coming soon &bull; <a href="mailto:info@anglingireland.ie">info@anglingireland.ie</a>
        </p>
    </div>
</body>
</html>
