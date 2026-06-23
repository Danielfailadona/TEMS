<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TEMs - Transportation Enforcement Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #0f2b4a 0%, #2563eb 50%, #0f2b4a 100%);
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 50%, rgba(37, 99, 235, 0.2), transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(30, 58, 95, 0.2), transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .hero-text h1 {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-text p {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            background: white;
            color: #0f2b4a;
            font-weight: 600;
            padding: 0.85rem 2rem;
            border-radius: 0.8rem;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-decoration: none;
            display: inline-block;
        }

        .btn-hero-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .btn-hero-secondary {
            background: rgba(255,255,255,0.15);
            color: white;
            font-weight: 600;
            padding: 0.85rem 2rem;
            border-radius: 0.8rem;
            border: 2px solid rgba(255,255,255,0.3);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-hero-secondary:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
        }

        .hero-feature {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: start;
        }

        .hero-feature-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.8rem;
            background: rgba(255,255,255,0.2);
            display: grid;
            place-items: center;
            flex-shrink: 0;
            font-size: 1.25rem;
        }

        .hero-feature-text h4 {
            margin: 0 0 0.25rem 0;
            font-weight: 600;
            font-size: 1rem;
        }

        .hero-feature-text p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .hero-visual {
            background: rgba(255,255,255,0.08);
            border-radius: 1.2rem;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }

        .hero-visual-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .stat-box {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 0.8rem;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .stat-box-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-box-label {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .features-section {
            background: #f8fbff;
            padding: 5rem 2rem;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
        }

        .feature-card-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(135deg, #2563eb, #0f2b4a);
            border-radius: 0.8rem;
            display: grid;
            place-items: center;
            color: white;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            margin: 0 0 0.75rem 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f2b4a;
        }

        .feature-card p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            font-size: 2.25rem;
            font-weight: 800;
            color: #0f2b4a;
            margin-bottom: 0.75rem;
        }

        .section-header p {
            font-size: 1.05rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .faq-section {
            background: white;
            padding: 5rem 2rem;
        }

        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            border: 1px solid var(--itevcms-border, rgba(15, 23, 42, 0.08));
            border-radius: 0.8rem;
            margin-bottom: 0.75rem;
            overflow: hidden;
        }

        .faq-question {
            padding: 1.25rem 1.5rem;
            background: #f8fbff;
            font-weight: 600;
            color: #0f2b4a;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s ease;
            border: none;
            width: 100%;
            text-align: left;
            font-size: 1rem;
        }

        .faq-question:hover {
            background: #eef4ff;
        }

        .faq-question i {
            transition: transform 0.3s ease;
            color: #2563eb;
            font-size: 1.1rem;
        }

        .faq-question[aria-expanded="true"] i {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 1.5rem 1.25rem;
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .cta-section {
            background: white;
            padding: 4rem 2rem;
            text-align: center;
        }

        .cta-title {
            max-width: 600px;
            margin: 0 auto 2rem;
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            color: #0f2b4a;
        }

        .cta-subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.05rem;
        }

        .footer-landing {
            background: #0f2b4a;
            color: white;
            padding: 2rem;
            text-align: center;
            font-size: 0.9rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .btn-hero-primary, .btn-hero-secondary {
                width: 100%;
                text-align: center;
            }

            .features-section {
                padding: 3rem 1rem;
            }

            .cta-section {
                padding: 2rem 1rem;
            }

            .faq-section {
                padding: 3rem 1rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" style="z-index: 100;">
    <div class="container-fluid px-4 px-lg-5">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
            <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="32" class="me-2">
            <span style="background: linear-gradient(135deg, #0f2b4a, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">TEMs</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('citizen.citation.lookup') }}">Citation Lookup</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('citizen.clamping.show') }}">Report Parking</a></li>
                <li class="nav-item ms-2">
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign In</a>
                </li>
                <li class="nav-item ms-2">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign Up</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <h1>Modern Traffic Enforcement Management</h1>
            <p>Streamline enforcement operations with real-time tracking, digital citations, and citizen-friendly portals.</p>

            <div class="hero-feature">
                <div class="hero-feature-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                <div class="hero-feature-text">
                    <h4>Real-time Operations</h4>
                    <p>Live GPS tracking and instant data sync</p>
                </div>
            </div>

            <div class="hero-feature">
                <div class="hero-feature-icon"><i class="bi bi-shield-check"></i></div>
                <div class="hero-feature-text">
                    <h4>Secure & Compliant</h4>
                    <p>Full audit trails and role-based access</p>
                </div>
            </div>

            <div class="hero-feature">
                <div class="hero-feature-icon"><i class="bi bi-graph-up"></i></div>
                <div class="hero-feature-text">
                    <h4>Data-Driven</h4>
                    <p>Analytics and reporting built-in</p>
                </div>
            </div>

            <div class="hero-buttons">
                <a href="{{ route('login') }}" class="btn-hero-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </a>
                <a href="{{ route('register') }}" class="btn-hero-secondary">
                    <i class="bi bi-person-plus me-2"></i>Register
                </a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-visual-grid">
                <div class="stat-box">
                    <div class="stat-box-value">2.4K+</div>
                    <div class="stat-box-label">Citations This Year</div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-value">₱1.2M</div>
                    <div class="stat-box-label">Collected Fines</div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-value">98%</div>
                    <div class="stat-box-label">Uptime</div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-value">24/7</div>
                    <div class="stat-box-label">Support</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="features-section" id="features">
    <div class="section-header">
        <h2>Powerful Features</h2>
        <p>Everything you need to manage traffic enforcement efficiently</p>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-card-icon"><i class="bi bi-receipt"></i></div>
            <h3>Digital Citations</h3>
            <p>Generate QR-coded electronic tickets with photo evidence and automatic fine calculation.</p>
        </div>

        <div class="feature-card">
            <div class="feature-card-icon"><i class="bi bi-map"></i></div>
            <h3>Real-time Tracking</h3>
            <p>Monitor enforcement teams live with GPS tracking, zone management, and geofencing alerts.</p>
        </div>

        <div class="feature-card">
            <div class="feature-card-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <h3>Analytics & Reports</h3>
            <p>Comprehensive dashboards with violation trends, revenue reports, and officer performance metrics.</p>
        </div>

        <div class="feature-card">
            <div class="feature-card-icon"><i class="bi bi-lock-fill"></i></div>
            <h3>Secure Access</h3>
            <p>Role-based permissions, audit logging, and encrypted data storage for full compliance.</p>
        </div>

        <div class="feature-card">
            <div class="feature-card-icon"><i class="bi bi-person-check"></i></div>
            <h3>Citizen Portal</h3>
            <p>Self-serve citation lookup, clamping requests, appeal filing, and online payments for vehicle owners.</p>
        </div>

        <div class="feature-card">
            <div class="feature-card-icon"><i class="bi bi-telephone-fill"></i></div>
            <h3>24/7 Support</h3>
            <p>Dedicated support team with response within 1 hour for all system issues.</p>
        </div>
    </div>
</section>

<section class="faq-section" id="faq">
    <div class="section-header">
        <h2>Frequently Asked Questions</h2>
        <p>Common questions about the Transportation Enforcement Management System</p>
    </div>
    <div class="faq-container">
        <div class="faq-item">
            <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="false">
                <span>How do I sign in to the system?</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse" id="faq1">
                <div class="faq-answer">
                    Click the "Sign In" button at the top right of the page. Enter your registered email address and password. If you don't have an account yet, click "Register" to create one.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false">
                <span>How can I look up a citation?</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse" id="faq2">
                <div class="faq-answer">
                    You can use the Citation Lookup feature accessible from the navigation bar. Enter your citation number or vehicle plate number to view details, pay fines, or file an appeal.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false">
                <span>What payment methods are accepted?</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse" id="faq3">
                <div class="faq-answer">
                    We accept multiple payment methods including credit/debit cards and online payment gateways. All transactions are processed securely with industry-standard encryption.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false">
                <span>How do I report a parked vehicle violation?</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse" id="faq4">
                <div class="faq-answer">
                    Use the "Report Parking" link in the navigation bar to submit a parking violation report. Provide the vehicle plate number, location, and upload supporting photos if available.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false">
                <span>How long does account approval take?</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse" id="faq5">
                <div class="faq-answer">
                    New accounts are typically reviewed within 24-48 hours. You'll receive an email notification once your account has been approved. While pending, you can log in and update your profile.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq6" aria-expanded="false">
                <span>Is my data secure?</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse" id="faq6">
                <div class="faq-answer">
                    Yes, security is our top priority. We use encryption, secure authentication, and regular security audits. All data is protected in compliance with applicable standards.
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <h2 class="cta-title">Ready to Transform Your Enforcement Operations?</h2>
    <p class="cta-subtitle">Join agencies using LTEM for efficient traffic management</p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Start Free Trial</a>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">Sign In to Portal</a>
    </div>
</section>

<footer class="footer-landing">
    <p>&copy; 2026 Transportation Enforcement Management System (TEMs). All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
