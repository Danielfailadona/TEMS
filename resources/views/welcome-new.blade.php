@extends('layouts.guest')

@section('title', 'Welcome to LTEM')

@section('content')
<style>
    /* Hero Section Enhancements */
    .welcome-hero {
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

    .welcome-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: 
            radial-gradient(circle at 20% 50%, rgba(37, 99, 235, 0.2), transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(30, 58, 95, 0.2), transparent 50%);
        pointer-events: none;
    }

    .welcome-content {
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
        text-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        margin-bottom: 2rem;
    }

    .btn-hero {
        padding: 0.85rem 2rem;
        border-radius: 0.8rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-hero-primary {
        background: white;
        color: #0f2b4a;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .btn-hero-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    }

    .btn-hero-secondary {
        background: rgba(255,255,255,0.15);
        color: white;
        border: 2px solid rgba(255,255,255,0.3);
    }

    .btn-hero-secondary:hover {
        background: rgba(255,255,255,0.25);
        border-color: rgba(255,255,255,0.5);
    }

    .hero-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-top: 2rem;
    }

    .stat-item {
        background: rgba(255,255,255,0.1);
        padding: 1.5rem;
        border-radius: 0.8rem;
        border: 1px solid rgba(255,255,255,0.15);
        text-align: center;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.85rem;
        opacity: 0.8;
    }

    .hero-visual {
        background: rgba(255,255,255,0.08);
        border-radius: 1.2rem;
        padding: 2rem;
        border: 1px solid rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
    }

    .feature-showcase {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .feature-item {
        background: rgba(255,255,255,0.12);
        padding: 1.2rem;
        border-radius: 0.8rem;
        border: 1px solid rgba(255,255,255,0.15);
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .feature-icon {
        width: 3rem;
        height: 3rem;
        background: rgba(255,255,255,0.2);
        border-radius: 0.8rem;
        display: grid;
        place-items: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .feature-text h4 {
        margin: 0 0 0.25rem 0;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .feature-text p {
        margin: 0;
        font-size: 0.85rem;
        opacity: 0.9;
    }

    /* Features Section */
    .features-section {
        background: #f8fbff;
        padding: 5rem 2rem;
    }

    .section-title {
        text-align: center;
        margin-bottom: 3rem;
    }

    .section-title h2 {
        font-size: 2.5rem;
        font-weight: 800;
        color: #0f2b4a;
        margin-bottom: 1rem;
    }

    .section-title p {
        font-size: 1.1rem;
        color: #666;
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
        transition: all 0.3s ease;
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

    /* Benefits Section */
    .benefits-section {
        background: white;
        padding: 4rem 2rem;
    }

    .benefits-grid {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .benefit-box {
        padding: 2rem;
        border-left: 4px solid #2563eb;
        background: #f8fbff;
        border-radius: 0.8rem;
    }

    .benefit-box h4 {
        color: #0f2b4a;
        margin-bottom: 0.75rem;
        font-weight: 700;
    }

    .benefit-box p {
        color: #666;
        margin: 0;
        line-height: 1.6;
    }

    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, #0f2b4a, #2563eb);
        color: white;
        padding: 4rem 2rem;
        text-align: center;
    }

    .cta-title {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }

    .cta-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 2rem;
    }

    /* Footer */
    .welcome-footer {
        background: #0f2b4a;
        color: white;
        padding: 2rem;
        text-align: center;
        font-size: 0.9rem;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    @media (max-width: 768px) {
        .welcome-content {
            grid-template-columns: 1fr;
        }

        .hero-buttons {
            flex-direction: column;
        }

        .btn-hero {
            width: 100%;
            text-align: center;
        }

        .features-section, .benefits-section, .cta-section {
            padding: 3rem 1rem;
        }

        .hero-stats, .feature-showcase {
            grid-template-columns: 1fr;
        }
    }

    @keyframes gradientShift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fadeInUp 0.6s ease-out forwards;
    }
</style>

<!-- Navigation -->
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
                <li class="nav-item"><a class="nav-link" href="#benefits">Benefits</a></li>
                <li class="nav-item ms-2">
                    <a href="/login" class="btn btn-outline-primary btn-sm">Sign In</a>
                </li>
                <li class="nav-item ms-2">
                    <a href="/register" class="btn btn-primary btn-sm">Sign Up</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="welcome-hero">
    <div class="welcome-content">
        <div class="hero-text">
            <h1>Modern Traffic Enforcement Management</h1>
            <p>Streamline enforcement operations with real-time tracking, digital citations, and citizen-friendly portals.</p>
            
            <div class="feature-showcase">
                <div class="feature-item">
                    <div class="feature-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                    <div class="feature-text">
                        <h4>Real-time Operations</h4>
                        <p>Live GPS tracking and instant data sync</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                    <div class="feature-text">
                        <h4>Secure & Compliant</h4>
                        <p>Full audit trails and role-based access</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="bi bi-graph-up"></i></div>
                    <div class="feature-text">
                        <h4>Data-Driven</h4>
                        <p>Analytics and reporting built-in</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="bi bi-geo-alt"></i></div>
                    <div class="feature-text">
                        <h4>Geofencing</h4>
                        <p>Zone management and alerts</p>
                    </div>
                </div>
            </div>

            <div class="hero-buttons">
                <a href="/login" class="btn-hero btn-hero-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </a>
                <a href="/register" class="btn-hero btn-hero-secondary">
                    <i class="bi bi-person-plus me-2"></i>Register Now
                </a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="feature-showcase" style="gap: 1rem;">
                <div class="stat-item">
                    <div class="stat-value">2.4K+</div>
                    <div class="stat-label">Citations This Year</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">₱1.2M</div>
                    <div class="stat-label">Collected Fines</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">98%</div>
                    <div class="stat-label">Uptime SLA</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Support Available</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section" id="features">
    <div class="section-title">
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

<!-- Benefits Section -->
<section class="benefits-section" id="benefits">
    <div class="section-title">
        <h2>Why Choose LTEM?</h2>
        <p>Benefits for your organization</p>
    </div>
    
    <div class="benefits-grid">
        <div class="benefit-box">
            <h4><i class="bi bi-arrow-up-right text-success me-2"></i>Increase Efficiency</h4>
            <p>Streamline operations and reduce paperwork with our digital citation system. Officers can focus on enforcement instead of administration.</p>
        </div>

        <div class="benefit-box">
            <h4><i class="bi bi-cash-stack text-success me-2"></i>Maximize Revenue</h4>
            <p>Improve collection rates with automated reminders, multiple payment options, and transparent tracking for every citation.</p>
        </div>

        <div class="benefit-box">
            <h4><i class="bi bi-eye text-success me-2"></i>Real-time Visibility</h4>
            <p>Track all enforcement operations in real-time with live GPS, enabling better resource allocation and incident response.</p>
        </div>

        <div class="benefit-box">
            <h4><i class="bi bi-shield-exclamation text-success me-2"></i>Reduce Disputes</h4>
            <p>Every citation includes timestamped photos and GPS coordinates, eliminating disputes and improving legal standing.</p>
        </div>

        <div class="benefit-box">
            <h4><i class="bi bi-people text-success me-2"></i>Better Public Relations</h4>
            <p>Citizens can track their citations online, pay conveniently, and file appeals through a user-friendly portal.</p>
        </div>

        <div class="benefit-box">
            <h4><i class="bi bi-cloud-check text-success me-2"></i>Scalable Solution</h4>
            <p>Cloud-based platform that grows with your city. Add users, zones, and enforcement teams without worrying about infrastructure.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="cta-title">Ready to Transform Your Operations?</div>
    <p class="cta-subtitle">Join leading cities using LTEM for efficient traffic enforcement</p>
    <div>
        <a href="/register" class="btn btn-light btn-lg" style="margin-right: 1rem;">Get Started Free</a>
        <a href="/login" class="btn btn-outline-light btn-lg">Sign In</a>
    </div>
</section>

<!-- Footer -->
<footer class="welcome-footer">
    <p>&copy; 2026 Transportation Enforcement Management System (TEMs). All rights reserved.</p>
    <p style="font-size: 0.85rem; margin-top: 1rem;">
        <a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Privacy Policy</a> • 
        <a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Terms of Service</a> • 
        <a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Contact Us</a>
    </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Smooth scroll and fade-in animations
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.feature-card, .benefit-box').forEach(el => observer.observe(el));

    // Smooth scroll for nav links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>

@endsection
