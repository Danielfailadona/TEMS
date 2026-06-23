<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('itevcms.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 1rem;
            margin: 0;
            font-family: var(--bs-font-sans-serif);
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            opacity: 0.04;
            background-image:
                repeating-linear-gradient(90deg, transparent, transparent 40px, rgba(255,255,255,0.3) 40px, rgba(255,255,255,0.3) 41px),
                repeating-linear-gradient(0deg, transparent, transparent 40px, rgba(255,255,255,0.3) 40px, rgba(255,255,255,0.3) 41px);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            opacity: 0.03;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cpath d='M20 180 L50 150 L80 170 L110 130 L140 160 L170 140' fill='none' stroke='white' stroke-width='2' stroke-dasharray='4 4'/%3E%3Ccircle cx='50' cy='150' r='4' fill='white'/%3E%3Ccircle cx='110' cy='130' r='4' fill='white'/%3E%3C/svg%3E");
            background-size: 200px 200px;
            pointer-events: none;
        }

        .account-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 960px;
        }

        .account-container {
            display: flex;
            width: 100%;
            min-height: 600px;
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            position: relative;
            overflow: hidden;
        }

        .form-panel {
            width: 55%;
            padding: 2.5rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel .brand-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-panel .brand-section img {
            height: 80px;
            margin-bottom: 0.75rem;
        }

        .form-panel .brand-section h2 {
            font-weight: 800;
            font-size: 1.6rem;
            color: #1e293b;
            margin-bottom: 0.15rem;
            letter-spacing: -0.02em;
        }

        .form-panel .brand-section .system-subtitle {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            margin-bottom: 0.6rem;
        }

        .form-panel .brand-section .welcome-heading {
            font-weight: 700;
            font-size: 1.25rem;
            color: #0f172a;
            margin-bottom: 0.15rem;
        }

        .form-panel .brand-section .welcome-sub {
            font-size: 0.85rem;
            color: #64748b;
        }

        .overlay-panel {
            position: absolute;
            top: 0;
            left: 55%;
            width: 45%;
            height: 100%;
            z-index: 10;
            overflow: hidden;
            transition: left 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 1.5rem 0 0 1.5rem;
        }

        .account-container.show-register .overlay-panel {
            left: 0;
        }

        .overlay-inner {
            width: 200%;
            height: 100%;
            display: flex;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .account-container.show-register .overlay-inner {
            transform: translateX(-50%);
        }

        .overlay-half {
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
            text-align: center;
            color: #fff;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 50%, #0f2b4a 100%);
            position: relative;
            overflow: hidden;
        }

        .overlay-half::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .overlay-half::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -30%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(255,255,255,0.04) 0%, transparent 60%);
            pointer-events: none;
        }

        .overlay-half .feature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1.5rem 0;
            text-align: left;
            position: relative;
            z-index: 1;
        }

        .overlay-half .feature-item {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(4px);
            border-radius: 12px;
            padding: 0.85rem;
            transition: transform 0.2s, background 0.2s;
        }

        .overlay-half .feature-item:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,0.16);
        }

        .overlay-half .feature-item .feature-icon {
            font-size: 1.3rem;
            margin-bottom: 0.3rem;
            display: block;
        }

        .overlay-half .feature-item .feature-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            opacity: 0.9;
        }

        .overlay-half .feature-item .feature-desc {
            font-size: 0.65rem;
            opacity: 0.65;
            line-height: 1.3;
        }

        .overlay-half h2 {
            font-weight: 700;
            font-size: 1.6rem;
            margin-bottom: 0.25rem;
            position: relative;
            z-index: 1;
        }

        .overlay-half p {
            opacity: 0.85;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            line-height: 1.5;
            position: relative;
            z-index: 1;
        }

        .btn-toggle {
            border: 2px solid #fff;
            color: #fff;
            background: transparent;
            padding: 0.65rem 2.2rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            z-index: 1;
        }

        .btn-toggle:hover {
            background: #fff;
            color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .brand-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #2563eb, #0f2b4a);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.72rem;
            color: #64748b;
            padding: 0.35rem 0.85rem;
            background: #f8fafc;
            border-radius: 2rem;
            margin-top: 0.75rem;
        }

        .security-badge i {
            font-size: 0.65rem;
            color: #22c55e;
        }

        .btn-outline-secondary {
            border-color: #e2e8f0;
            color: #64748b;
        }

        .btn-outline-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        @media (max-width: 768px) {
            .account-container {
                flex-direction: column;
                min-height: auto;
                max-width: 480px;
            }
            .form-panel {
                width: 100%;
                padding: 1.5rem;
            }
            .overlay-panel {
                display: none;
            }
            .mobile-toggle {
                display: block !important;
            }
        }

        .mobile-toggle {
            display: none;
        }

        .form-panel form .input-group-text {
            background: #f8fafc;
            border-right: none;
        }

        .form-panel form .form-control {
            border-left: none;
        }

        .form-panel form .form-control:focus {
            border-color: #dee2e6;
            box-shadow: none;
        }

        .form-panel form .input-group:focus-within .input-group-text {
            border-color: #2563eb;
        }

        .form-panel form .input-group:focus-within .form-control {
            border-color: #2563eb;
        }

        .form-panel form .input-group .input-group-text:last-child {
            border-left: 1px solid #cbd5e1;
            background: #fff;
        }

        .form-panel form .input-group .input-group-text:last-child i {
            color: #475569;
        }

        .form-panel form .input-group:focus-within .input-group-text:last-child {
            border-color: #2563eb;
        }

        .form-check-input {
            border-color: #94a3b8;
        }

        .form-check-input:checked {
            border-color: #2563eb;
            background-color: #2563eb;
        }

        .form-check-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }
    </style>
</head>
<body>
    <a href="{{ route('welcome') }}" class="position-fixed d-inline-flex align-items-center justify-content-center text-decoration-none"
       style="top:1rem;left:1rem;z-index:100;width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,0.9);box-shadow:0 2px 8px rgba(0,0,0,0.15);color:#1e293b;transition:all 0.2s;"
       onmouseover="this.style.background='#2563eb';this.style.color='#fff'" onmouseout="this.style.background='rgba(255,255,255,0.9)';this.style.color='#1e293b'">
        <i class="bi bi-house-door-fill" style="font-size:1.2rem;"></i>
    </a>
    <div class="account-wrapper">
        <div class="account-container @if($showRegister) show-register @endif" id="accountContainer">
            <!-- Login Panel -->
            <div class="form-panel">
                <div class="brand-section">
                    <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="80" class="mb-2">
                    <div class="system-subtitle">Transportation Enforcement Management System</div>
                    <h2>{{ config('itevcms.app_name') }}</h2>
                    <div class="welcome-heading">Welcome Back</div>
                    <div class="welcome-sub">Sign in to continue to your dashboard.</div>
                </div>
                @if(session('status'))
                    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
                @endif
                <form method="POST" action="{{ route('account.procedure.store') }}">
                    @csrf
                    <input type="hidden" name="_action" value="login">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Email Address</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="admin@example.com" required @if(!$showRegister) autofocus @endif>
                        </div>
                        @error('email')
                            <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">Password</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="••••••••" required>
                            <button class="input-group-text" type="button" onclick="togglePassword('password')" style="cursor:pointer;" tabindex="-1" title="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label small" for="remember">Remember me</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="text-decoration-none small" style="color:#64748b;">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                    <div class="text-center mt-3">
                        <div class="security-badge">
                            <i class="bi bi-shield-check"></i>
                            Secure Access &bull; Encrypted authentication
                        </div>
                    </div>
                </form>
                <div class="text-center mt-3 mobile-toggle">
                    <small class="text-muted">Don't have an account? <a href="#" onclick="toggleForm('register')" class="fw-semibold">Create one</a></small>
                </div>
            </div>

            <!-- Register Panel -->
            <div class="form-panel">
                <div class="brand-section">
                    <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="80" class="mb-2">
                    <div class="system-subtitle">Transportation Enforcement Management System</div>
                    <h2>{{ config('itevcms.app_name') }}</h2>
                    <div class="welcome-heading">Create Account</div>
                    <div class="welcome-sub">Register to access the enforcement system.</div>
                </div>
                <form method="POST" action="{{ route('account.procedure.store') }}">
                    @csrf
                    <input type="hidden" name="_action" value="register">
                    <div class="mb-2">
                        <label for="reg-name" class="form-label fw-semibold small">Full Name</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="reg-name" name="name" value="{{ old('name') }}" placeholder="John Doe" required @if($showRegister) autofocus @endif>
                        </div>
                        @error('name')
                            <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label for="reg-email" class="form-label fw-semibold small">Email Address</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="reg-email" name="email" value="{{ old('email') }}" placeholder="user@example.com" required>
                        </div>
                        @error('email')
                            <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label for="reg-phone" class="form-label fw-semibold small">Phone Number</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="reg-phone" name="phone" value="{{ old('phone') }}" placeholder="+63912345678">
                        </div>
                        @error('phone')
                            <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label for="reg-password" class="form-label fw-semibold small">Password</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="reg-password" name="password" placeholder="••••••••" required>
                            <button class="input-group-text" type="button" onclick="togglePassword('reg-password')" style="cursor:pointer;" tabindex="-1" title="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label for="reg-password-confirm" class="form-label fw-semibold small">Confirm Password</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-lock-check"></i></span>
                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="reg-password-confirm" name="password_confirmation" placeholder="••••••••" required>
                            <button class="input-group-text" type="button" onclick="togglePassword('reg-password-confirm')" style="cursor:pointer;" tabindex="-1" title="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" id="reg-terms" name="terms" required>
                        <label class="form-check-label small" for="reg-terms">I agree to the <a href="#" class="text-decoration-none">Terms of Service</a></label>
                    </div>
                    @error('terms')
                        <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                    @enderror
                    <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                    <div class="text-center mt-3">
                        <div class="security-badge">
                            <i class="bi bi-shield-check"></i>
                            Secure Access &bull; Encrypted authentication
                        </div>
                    </div>
                </form>
                <div class="text-center mt-3 mobile-toggle">
                    <small class="text-muted">Already have an account? <a href="#" onclick="toggleForm('login')" class="fw-semibold">Sign In</a></small>
                </div>
            </div>

            <!-- Overlay -->
            <div class="overlay-panel">
                <div class="overlay-inner">
                    <div class="overlay-half">
                        <h2>New Here?</h2>
                        <p>Join the enforcement platform and access essential tools.</p>
                        <div class="feature-grid">
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-shield-fill"></i></span>
                                <div class="feature-label">Citation Mgmt</div>
                                <div class="feature-desc">Issue & manage traffic violations</div>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-geo-alt-fill"></i></span>
                                <div class="feature-label">Zone Monitoring</div>
                                <div class="feature-desc">Real-time enforcement zones</div>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                                <div class="feature-label">Digital Records</div>
                                <div class="feature-desc">Paperless citation tracking</div>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-credit-card-2-front-fill"></i></span>
                                <div class="feature-label">Online Payments</div>
                                <div class="feature-desc">Secure payment processing</div>
                            </div>
                        </div>
                        <button class="btn-toggle" onclick="toggleForm('register')">Create Account</button>
                    </div>
                    <div class="overlay-half">
                        <h2>Welcome Back</h2>
                        <p>Sign in to manage citations, monitor zones, and more.</p>
                        <div class="feature-grid">
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-bar-chart-fill"></i></span>
                                <div class="feature-label">Dashboard</div>
                                <div class="feature-desc">Enforcement overview & analytics</div>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-search"></i></span>
                                <div class="feature-label">Search</div>
                                <div class="feature-desc">Look up citations & vehicles</div>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-clipboard-data-fill"></i></span>
                                <div class="feature-label">Reports</div>
                                <div class="feature-desc">Download compliance reports</div>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon"><i class="bi bi-shield-fill-check"></i></span>
                                <div class="feature-label">Compliance</div>
                                <div class="feature-desc">Verify enforcement standards</div>
                            </div>
                        </div>
                        <button class="btn-toggle" onclick="toggleForm('login')">Sign In</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleForm(form) {
            const container = document.getElementById('accountContainer');
            if (form === 'register') {
                container.classList.add('show-register');
            } else {
                container.classList.remove('show-register');
            }
        }

        function togglePassword(id) {
            const input = document.getElementById(id);
            const button = input.parentElement.querySelector('.input-group-text:last-child i');
            if (input.type === 'password') {
                input.type = 'text';
                button.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                button.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
</body>
</html>
