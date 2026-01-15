<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Monitor Pro - Real-Time Domain & SSL Monitoring</title>
    <meta name="description" content="Real-time domain and SSL monitoring with instant alerts. Monitor up to 500 domains, track SSL certificates, and get notified via Telegram, email, or webhooks.">
    
    <!-- Open Graph / Facebook / Telegram -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="Domain Monitor Pro - Real-Time Domain & SSL Monitoring">
    <meta property="og:description" content="Real-time domain and SSL monitoring with instant alerts. Monitor up to 500 domains, track SSL certificates, and get notified via Telegram, email, or webhooks.">
    <meta property="og:image" content="{{ asset('img/logo.png') }}">
    <meta property="og:site_name" content="Domain Monitor Pro">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Domain Monitor Pro - Real-Time Domain & SSL Monitoring">
    <meta name="twitter:description" content="Real-time domain and SSL monitoring with instant alerts. Monitor up to 500 domains, track SSL certificates, and get notified via Telegram, email, or webhooks.">
    <meta name="twitter:image" content="{{ asset('img/logo.png') }}">
    
    <link rel="icon" href="{{ asset('img/2.jpg') }}" type="image/x-icon">
    <style>
        :root {
            --primary: #1a1a2e;
            --primary-light: #16213e;
            --primary-dark: #0f0f1a;
            --secondary: #e94560;
            --secondary-light: #ff6b6b;
            --accent: #0f3460;
            --accent-light: #1a4a7a;
            --background: #0a0a14;
            --surface: #12121e;
            --surface-light: #1a1a2e;
            --text: #eaeaea;
            --text-muted: #a0a0a0;
            --text-dim: #666;
            --success: #00d9a5;
            --warning: #ffc107;
            --gradient-primary: linear-gradient(135deg, var(--secondary) 0%, #ff8a5b 100%);
            --gradient-surface: linear-gradient(180deg, var(--surface) 0%, var(--background) 100%);
            --shadow-glow: 0 0 40px rgba(233, 69, 96, 0.15);
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navigation */
        .nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 1rem 2rem;
            background: rgba(10, 10, 20, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .nav-logo img {
            height: 40px;
            border-radius: 8px;
        }

        .nav-logo span {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--text);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(233, 69, 96, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(233, 69, 96, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: var(--surface-light);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: var(--accent);
            border-color: var(--accent);
        }

        .btn-outline {
            background: transparent;
            color: var(--secondary);
            border: 2px solid var(--secondary);
        }

        .btn-outline:hover {
            background: var(--secondary);
            color: white;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 2rem 80px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(233, 69, 96, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(15, 52, 96, 0.2) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(233, 69, 96, 0.1);
            border: 1px solid rgba(233, 69, 96, 0.3);
            border-radius: 50px;
            font-size: 0.85rem;
            color: var(--secondary-light);
            margin-bottom: 2rem;
        }

        .hero-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--text) 0%, var(--text-muted) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero h1 span {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 2.5rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 4rem;
            margin-top: 4rem;
            padding-top: 3rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Section Styles */
        section {
            padding: 100px 2rem;
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 4rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* Features Section */
        .features {
            background: var(--gradient-surface);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            padding: 2rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: rgba(233, 69, 96, 0.3);
            box-shadow: var(--shadow-glow);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            background: rgba(233, 69, 96, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Pricing Section */
        .pricing {
            background: var(--background);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            max-width: 1100px;
            margin: 0 auto;
        }

        .pricing-card {
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            position: relative;
            transition: var(--transition);
        }

        .pricing-card.featured {
            border-color: var(--secondary);
            transform: scale(1.05);
            box-shadow: var(--shadow-glow);
        }

        .pricing-card.featured::before {
            content: 'Most Popular';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gradient-primary);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .pricing-card:hover {
            border-color: rgba(233, 69, 96, 0.5);
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .pricing-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .pricing-price {
            font-size: 3rem;
            font-weight: 700;
        }

        .pricing-price span {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .pricing-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            color: var(--text-muted);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .pricing-features li::before {
            content: '✓';
            color: var(--success);
            font-weight: 700;
        }

        .pricing-card .btn {
            width: 100%;
        }

        /* Testimonials */
        .testimonials {
            background: var(--gradient-surface);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .testimonial-card {
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            padding: 2rem;
        }

        .testimonial-content {
            font-size: 1.1rem;
            color: var(--text-muted);
            font-style: italic;
            margin-bottom: 1.5rem;
            line-height: 1.8;
        }

        .testimonial-content::before {
            content: '"';
            font-size: 3rem;
            color: var(--secondary);
            line-height: 0;
            display: block;
            margin-bottom: 0.5rem;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .testimonial-avatar {
            width: 48px;
            height: 48px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .testimonial-info h4 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .testimonial-info p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Additional Info */
        .additional {
            background: var(--background);
        }

        .additional-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .additional-item {
            text-align: center;
        }

        .additional-icon {
            width: 80px;
            height: 80px;
            background: rgba(233, 69, 96, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }

        .additional-item h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .additional-item p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Contact Section */
        .contact {
            background: var(--gradient-surface);
        }

        .contact-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
        }

        .contact-info h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .contact-item-icon {
            width: 48px;
            height: 48px;
            background: rgba(233, 69, 96, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .contact-item-content h4 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .contact-item-content p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .contact-form {
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: var(--background);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-sm);
            color: var(--text);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-success {
            display: none;
            background: rgba(0, 217, 165, 0.1);
            border: 1px solid var(--success);
            border-radius: var(--border-radius-sm);
            padding: 1rem;
            text-align: center;
            color: var(--success);
        }

        .form-success.show {
            display: block;
        }

        /* Footer */
        .footer {
            background: var(--primary-dark);
            padding: 3rem 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero-stats {
                flex-direction: column;
                gap: 2rem;
            }

            .contact-container {
                grid-template-columns: 1fr;
            }

            .pricing-card.featured {
                transform: scale(1);
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
        }

        /* Loading state for button */
        .btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn.loading::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="nav">
        <div class="nav-container">
            <a href="{{ route('welcome') }}" class="nav-logo">
                <img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name') }}">
                <span>Domain Monitor</span>
            </a>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#testimonials">Reviews</a>
                <a href="#contact">Contact</a>
                <a href="{{ route('login') }}" class="btn btn-secondary">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <span>Trusted by 1000+ businesses worldwide</span>
            </div>
            <h1>Monitor Your Domains<br><span>Before They Go Down</span></h1>
            <p>Real-time domain and SSL monitoring with instant alerts. Know when your sites are down before your customers do.</p>
            <div class="hero-buttons">
                <a href="{{ route('register') }}" class="btn btn-primary">Start Free Trial →</a>
                <a href="#features" class="btn btn-secondary">Learn More</a>
            </div>
            <div class="hero-stats">
                <div class="stat">
                    <div class="stat-value">99.9%</div>
                    <div class="stat-label">Uptime SLA</div>
                </div>
                <div class="stat">
                    <div class="stat-value">10s</div>
                    <div class="stat-label">Check Interval</div>
                </div>
                <div class="stat">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Monitoring</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2>Everything You Need to Stay Online</h2>
            <p>Comprehensive monitoring tools to ensure your domains and SSL certificates are always healthy.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Real-Time Monitoring</h3>
                <p>Check your domains every 10 minutes with our advanced monitoring system. Get instant status updates.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>SSL Certificate Tracking</h3>
                <p>Monitor SSL certificate validity and expiration. Never let your certificates expire unexpectedly.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Instant Alerts</h3>
                <p>Receive notifications via Telegram, email, or webhooks the moment something goes wrong.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Uptime History</h3>
                <p>Track your domain's uptime history with detailed reports and analytics over time.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌐</div>
                <h3>Bulk Domain Import</h3>
                <p>Import hundreds of domains at once from feeds or manual entry. Perfect for agencies.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>Auto Import from Feed</h3>
                <p>Automatically sync domains from external feeds daily. Set it and forget it.</p>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
        <div class="section-header">
            <h2>Simple, Transparent Pricing</h2>
            <p>Choose the plan that fits your needs. All plans include core monitoring features.</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="pricing-header">
                    <div class="pricing-name">Free</div>
                    <div class="pricing-price">$0<span>/month</span></div>
                </div>
                <ul class="pricing-features">
                    <li>Up to 50 domains</li>
                    <li>60-minute check interval</li>
                    <li>Basic uptime monitoring</li>
                    <li>Email notifications</li>
                    <li>24-hour history</li>
                </ul>
                <a href="{{ route('register', ['plan' => 'free']) }}" class="btn btn-outline">Get Started Free</a>
            </div>
            <div class="pricing-card featured">
                <div class="pricing-header">
                    <div class="pricing-name">Pro</div>
                    <div class="pricing-price">$59<span>/month</span></div>
                </div>
                <ul class="pricing-features">
                    <li>Up to 200 domains</li>
                    <li>30-minute check interval</li>
                    <li>SSL certificate monitoring</li>
                    <li>Telegram & webhook alerts</li>
                    <li>7-day history</li>
                    <li>Priority support</li>
                </ul>
                <a href="{{ route('register', ['plan' => 'pro']) }}" class="btn btn-primary">Start Pro Trial</a>
            </div>
            <div class="pricing-card">
                <div class="pricing-header">
                    <div class="pricing-name">Max</div>
                    <div class="pricing-price">$99<span>/month</span></div>
                </div>
                <ul class="pricing-features">
                    <li>Up to 500 domains</li>
                    <li>10-minute check interval</li>
                    <li>Advanced SSL monitoring</li>
                    <li>All notification channels</li>
                    <li>30-day history</li>
                    <li>Auto feed import</li>
                    <li>Dedicated support</li>
                </ul>
                <a href="{{ route('register', ['plan' => 'max']) }}" class="btn btn-outline">Start Max Trial</a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="section-header">
            <h2>Loved by Teams Worldwide</h2>
            <p>See what our customers have to say about Domain Monitor Pro.</p>
        </div>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-content">
                    Domain Monitor Pro saved us countless hours of manual checking. The instant alerts have prevented multiple potential outages.
                </div>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">JD</div>
                    <div class="testimonial-info">
                        <h4>James Davidson</h4>
                        <p>CTO, TechFlow Solutions</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-content">
                    Managing 300+ client domains was a nightmare until we found this tool. The bulk import and auto-sync features are game changers.
                </div>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">SA</div>
                    <div class="testimonial-info">
                        <h4>Sarah Al-Rashid</h4>
                        <p>Agency Owner, Digital Gulf</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-content">
                    The SSL monitoring alone is worth the subscription. We caught an expiring certificate 2 weeks before it would have caused issues.
                </div>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">MK</div>
                    <div class="testimonial-info">
                        <h4>Mohammed Khan</h4>
                        <p>DevOps Lead, FinServe</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Additional Info Section -->
    <section class="additional" id="additional">
        <div class="section-header">
            <h2>Why Choose Us?</h2>
            <p>Built for reliability, designed for simplicity.</p>
        </div>
        <div class="additional-grid">
            <div class="additional-item">
                <div class="additional-icon">🚀</div>
                <h3>Lightning Fast</h3>
                <p>Our distributed checking network ensures your domains are monitored from multiple locations worldwide.</p>
            </div>
            <div class="additional-item">
                <div class="additional-icon">🛡️</div>
                <h3>Enterprise Security</h3>
                <p>Your data is encrypted and protected with enterprise-grade security measures.</p>
            </div>
            <div class="additional-item">
                <div class="additional-icon">📱</div>
                <h3>Mobile Ready</h3>
                <p>Access your dashboard and receive alerts on any device, anywhere.</p>
            </div>
            <div class="additional-item">
                <div class="additional-icon">🔧</div>
                <h3>API Access</h3>
                <p>Integrate with your existing tools using our comprehensive REST API.</p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="section-header">
            <h2>Get In Touch</h2>
            <p>Have questions? We're here to help.</p>
        </div>
        <div class="contact-container">
            <div class="contact-info">
                <h3>Contact Details</h3>
                <div class="contact-item">
                    <div class="contact-item-icon">📞</div>
                    <div class="contact-item-content">
                        <h4>Phone</h4>
                        <p>+971 50 586 6567</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-item-icon">📍</div>
                    <div class="contact-item-content">
                        <h4>Address</h4>
                        <p>Dubai Internet City, Building 12<br>Dubai, United Arab Emirates</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-item-icon">✉️</div>
                    <div class="contact-item-content">
                        <h4>Email</h4>
                        <p id="contact-email"></p>
                    </div>
                </div>
            </div>
            <div class="contact-form">
                <form id="contactForm">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required placeholder="Your name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required placeholder="How can we help you?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitBtn" style="width: 100%;">Send Message</button>
                    <div class="form-success" id="formSuccess">
                        ✓ Thank you! Your message has been sent successfully.
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'Domain Monitor Pro') }}. All rights reserved.</p>
        <div class="footer-links">
            <a href="{{ route('terms') }}">Terms & Conditions</a>
            <a href="#contact">Contact</a>
        </div>
    </footer>

    <script>
        // Set dynamic email
        document.getElementById('contact-email').textContent = 'info@' + window.location.hostname;

        // Contact form handling
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const formSuccess = document.getElementById('formSuccess');
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Sending...';
            
            // Simulate delay (3-5 seconds)
            const delay = Math.random() * 2000 + 3000;
            
            setTimeout(function() {
                submitBtn.classList.remove('loading');
                submitBtn.textContent = 'Sent!';
                submitBtn.style.background = 'var(--success)';
                formSuccess.classList.add('show');
                
                // Reset form
                document.getElementById('contactForm').reset();
                
                // Reset button after 3 seconds
                setTimeout(function() {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Send Message';
                    submitBtn.style.background = '';
                }, 3000);
            }, delay);
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
