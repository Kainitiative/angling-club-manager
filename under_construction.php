<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angling Ireland - Coming Soon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a1a0f;
            color: #fff;
            overflow-x: hidden;
        }
        
        .hero {
            min-height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(180deg, rgba(10, 26, 15, 0.3) 0%, rgba(10, 26, 15, 0.95) 100%),
                url('assets/images/hero-lake.png') center/cover;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 900px;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.5);
            border-radius: 50px;
            padding: 8px 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #4ade80;
            margin-bottom: 24px;
        }
        
        .badge i {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #fff 0%, #4ade80 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .tagline {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 32px;
            font-weight: 500;
        }
        
        .description {
            font-size: 1.15rem;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.7);
            max-width: 700px;
            margin: 0 auto 40px;
        }
        
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255, 255, 255, 0.5);
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(10px); }
        }
        
        .section {
            padding: 100px 20px;
        }
        
        .section-dark {
            background: #0d1f14;
        }
        
        .section-gradient {
            background: linear-gradient(180deg, #0a1a0f 0%, #0d2818 100%);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .section-header p {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.6);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.02) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px 30px;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            border-color: rgba(74, 222, 128, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .feature-card.highlight {
            background: linear-gradient(145deg, rgba(74, 222, 128, 0.15) 0%, rgba(74, 222, 128, 0.05) 100%);
            border-color: rgba(74, 222, 128, 0.3);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 24px;
        }
        
        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .feature-card p {
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.7;
            margin-bottom: 20px;
        }
        
        .feature-list {
            list-style: none;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }
        
        .feature-list li i {
            color: #4ade80;
            font-size: 0.8rem;
        }
        
        .showcase {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .showcase-image {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .showcase-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .showcase-content h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .showcase-content p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-top: 80px;
        }
        
        .stat-card {
            text-align: center;
            padding: 30px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-icon {
            font-size: 2rem;
            color: #4ade80;
            margin-bottom: 16px;
        }
        
        .stat-label {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .affiliations {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.03);
        }
        
        .affiliations h3 {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 30px;
        }
        
        .affiliation-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 40px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .affiliation-item {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .cta-section {
            text-align: center;
            padding: 100px 20px;
            background: linear-gradient(180deg, #0d2818 0%, #0a1a0f 100%);
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .cta-section p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.2rem;
            margin-bottom: 40px;
        }
        
        .cta-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 50px;
            padding: 16px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }
        
        footer {
            text-align: center;
            padding: 40px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        footer p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }
        
        footer a {
            color: #4ade80;
            text-decoration: none;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 992px) {
            h1 {
                font-size: 2.8rem;
            }
            .tagline {
                font-size: 1.2rem;
            }
            .showcase {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .showcase-image {
                order: -1;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 600px) {
            h1 {
                font-size: 2.2rem;
            }
            .section {
                padding: 60px 20px;
            }
            .section-header h2 {
                font-size: 1.8rem;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
            .stat-card {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>

    <section class="hero">
        <div class="hero-content">
            <div class="badge">
                <i class="fas fa-circle"></i>
                Coming Soon
            </div>
            <h1>Angling Ireland</h1>
            <p class="tagline">The Free Digital Hub for Irish Angling Clubs</p>
            <p class="description">
                A powerful, free-forever platform built specifically for Irish angling clubs, syndicates, 
                and fisheries. Manage members, track finances, log catches, run competitions, 
                and build your community - all in one place.
            </p>
        </div>
        <div class="scroll-indicator">
            <i class="fas fa-chevron-down fa-2x"></i>
        </div>
    </section>

    <section class="section section-dark">
        <div class="container">
            <div class="section-header">
                <h2>Powerful Features for Your Club</h2>
                <p>Everything you need to run a modern angling club, designed with Irish anglers in mind</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card highlight">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Member Management</h3>
                    <p>Complete control over your club membership with powerful tools designed for committees.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Online membership applications</li>
                        <li><i class="fas fa-check"></i> Approval workflows for committee</li>
                        <li><i class="fas fa-check"></i> Member profiles and contact details</li>
                        <li><i class="fas fa-check"></i> Role-based permissions (Secretary, Treasurer, etc.)</li>
                        <li><i class="fas fa-check"></i> Membership status tracking</li>
                        <li><i class="fas fa-check"></i> Internal messaging system</li>
                    </ul>
                </div>
                
                <div class="feature-card highlight">
                    <div class="feature-icon">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <h3>Finance Tracking</h3>
                    <p>Professional financial management tools to keep your club's accounts in order.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Income and expense tracking</li>
                        <li><i class="fas fa-check"></i> Multiple account management</li>
                        <li><i class="fas fa-check"></i> Membership fee tiers</li>
                        <li><i class="fas fa-check"></i> Transaction categories</li>
                        <li><i class="fas fa-check"></i> Financial reporting</li>
                        <li><i class="fas fa-check"></i> Treasurer dashboard</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-fish"></i>
                    </div>
                    <h3>Catch Logging</h3>
                    <p>Record and celebrate every catch with detailed logging and personal bests.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Photo uploads with catches</li>
                        <li><i class="fas fa-check"></i> Species, weight, and length tracking</li>
                        <li><i class="fas fa-check"></i> Personal best records</li>
                        <li><i class="fas fa-check"></i> Club records board</li>
                        <li><i class="fas fa-check"></i> Catch of the Month awards</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3>Competitions</h3>
                    <p>Organize and manage club competitions with ease.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Competition scheduling</li>
                        <li><i class="fas fa-check"></i> Member registration</li>
                        <li><i class="fas fa-check"></i> Results tracking</li>
                        <li><i class="fas fa-check"></i> Leaderboards</li>
                        <li><i class="fas fa-check"></i> Season-long standings</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Meetings & Events</h3>
                    <p>Keep your club organized with meeting management.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Meeting scheduling</li>
                        <li><i class="fas fa-check"></i> Agenda management</li>
                        <li><i class="fas fa-check"></i> Minutes recording</li>
                        <li><i class="fas fa-check"></i> Member notifications</li>
                        <li><i class="fas fa-check"></i> Attendance tracking</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Public Club Page</h3>
                    <p>Showcase your club to attract new members.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Customizable club profile</li>
                        <li><i class="fas fa-check"></i> Photo gallery</li>
                        <li><i class="fas fa-check"></i> Waters and venues</li>
                        <li><i class="fas fa-check"></i> Sponsor showcase</li>
                        <li><i class="fas fa-check"></i> Online join requests</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-gradient">
        <div class="container">
            <div class="showcase">
                <div class="showcase-content">
                    <h2>Built for Irish Angling Clubs</h2>
                    <p>
                        Whether you're a small local club, a syndicate managing private waters, 
                        or a commercial fishery - Angling Ireland gives you the tools to modernize 
                        your operations without the cost. We understand Irish angling culture and 
                        have built features specifically for how clubs here operate.
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Committee role support (Chairperson, Secretary, Treasurer, PRO)</li>
                        <li><i class="fas fa-check"></i> Governance best practice guides</li>
                        <li><i class="fas fa-check"></i> Document templates for clubs</li>
                        <li><i class="fas fa-check"></i> News and announcements</li>
                        <li><i class="fas fa-check"></i> Club policies management</li>
                    </ul>
                </div>
                <div class="showcase-image">
                    <img src="assets/images/catch-showcase.png" alt="Fresh catch">
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-heart"></i></div>
                    <div class="stat-label">Free Forever</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="stat-label">Secure & Private</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-mobile-alt"></i></div>
                    <div class="stat-label">Mobile Friendly</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shamrock"></i></div>
                    <div class="stat-label">Made in Ireland</div>
                </div>
            </div>
        </div>
    </section>

    <section class="affiliations">
        <h3>Designed to Work With</h3>
        <div class="affiliation-list">
            <span class="affiliation-item">Angling Council of Ireland</span>
            <span class="affiliation-item">IFSA</span>
            <span class="affiliation-item">IFPAC</span>
            <span class="affiliation-item">NCFFI</span>
            <span class="affiliation-item">TAFI</span>
            <span class="affiliation-item">SSTRAI</span>
        </div>
    </section>

    <section class="cta-section">
        <h2>Launching Soon</h2>
        <p>Be among the first clubs to join the platform</p>
        <div class="cta-badge">
            <i class="fas fa-envelope"></i>
            info@anglingireland.ie
        </div>
    </section>

    <footer>
        <p>&copy; 2026 Angling Ireland &bull; <a href="mailto:info@anglingireland.ie">Contact Us</a></p>
    </footer>

</body>
</html>
