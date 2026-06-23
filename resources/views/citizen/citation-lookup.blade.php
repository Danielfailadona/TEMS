<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Citation Lookup — {{ config('itevcms.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f8fbff;
            min-height: 100vh;
        }
        .animate-on-load {
            opacity: 0;
            transform: translateY(12px);
            transition: opacity 0.55s ease, transform 0.55s ease;
        }
        .animate-on-load.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .stat-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1.1rem;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
            background: rgba(255, 255, 255, 0.92);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.1);
        }
        .form-control, .form-select {
            border-radius: 0.8rem;
            border: 1px solid rgba(15, 23, 42, 0.12);
            padding: 0.7rem 0.85rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: rgba(37, 99, 235, 0.45);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.16);
        }
        .btn {
            border-radius: 0.8rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        .action-card {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .action-card .card-body {
            transition: background 0.2s ease;
        }
        .action-card:hover .card-body {
            background: rgba(37, 99, 235, 0.03);
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(15, 23, 42, 0.05);
        }
        .feature-item:last-child {
            border-bottom: none;
        }
        .feature-icon {
            width: 2.2rem;
            height: 2.2rem;
            background: rgba(37, 99, 235, 0.1);
            border-radius: 0.6rem;
            display: grid;
            place-items: center;
            color: #2563eb;
            flex-shrink: 0;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" style="z-index: 100;">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('welcome') }}">
                <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="32" class="me-2">
                <span style="background: linear-gradient(135deg, #0f2b4a, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">TEMs</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('welcome') }}#features">Features</a></li>
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

    <div class="container py-4 py-lg-5">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show animate-on-load" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="mb-4 animate-on-load">
                    <h2 class="fw-bold mb-2">
                        <i class="bi bi-search me-2"></i>Citation Lookup
                    </h2>
                    <p class="text-muted mb-0">Find your citation and check its status instantly</p>
                </div>

                <div class="card stat-card mb-4 animate-on-load">
                    <div class="card-body p-3 p-md-4">
                        <div class="mb-3">
                            <small class="text-muted fw-semibold d-block mb-2">SEARCH TYPE</small>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="search_type" id="search_type_simple" value="simple" checked>
                                <label class="btn btn-outline-primary" for="search_type_simple">
                                    <i class="bi bi-lightning me-1"></i>Quick Search
                                </label>
                                <input type="radio" class="btn-check" name="search_type" id="search_type_advanced" value="advanced">
                                <label class="btn btn-outline-primary" for="search_type_advanced">
                                    <i class="bi bi-sliders me-1"></i>Advanced
                                </label>
                            </div>
                        </div>

                        <form action="{{ route('citizen.citation.search') }}" method="GET" id="searchForm">
                            <div id="simple-search">
                                <label for="search" class="form-label fw-semibold">
                                    <i class="bi bi-receipt me-1"></i>Citation or Plate Number
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="search"
                                        name="search"
                                        placeholder="e.g., CIT-2024-001 or ABC-1234"
                                        value="{{ old('search') }}"
                                        autocomplete="off"
                                    >
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle me-1"></i>Minimum 3 characters required
                                </small>
                                @error('search')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <div id="advanced-search" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="adv_citation" class="form-label">Citation Number</label>
                                        <input type="text" class="form-control" id="adv_citation" name="citation_number" placeholder="e.g., CIT-2024-001">
                                    </div>
                                    <div class="col-12">
                                        <label for="adv_plate" class="form-label">Vehicle Plate</label>
                                        <input type="text" class="form-control" id="adv_plate" name="plate_number" placeholder="e.g., ABC-1234" style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-6">
                                        <label for="adv_date" class="form-label">Issued Date</label>
                                        <input type="date" class="form-control" id="adv_date" name="issued_date">
                                    </div>
                                    <div class="col-6">
                                        <label for="adv_status" class="form-label">Status</label>
                                        <select class="form-select" id="adv_status" name="status">
                                            <option value="">All Statuses</option>
                                            <option value="issued">Issued</option>
                                            <option value="paid">Paid</option>
                                            <option value="overdue">Overdue</option>
                                            <option value="appealed">Appealed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                                    <i class="bi bi-search me-2"></i>Search Citation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card stat-card animate-on-load" id="resultsCard" style="display: none;">
                    <div class="card-body p-3 p-md-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2"></i>Search Results</h5>
                        <div id="resultsContent"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="animate-on-load">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-lightning me-2 text-primary"></i>Quick Actions
                    </h5>
                </div>
                <a href="{{ route('citizen.clamping.show') }}" class="action-card animate-on-load" style="animation-delay: 0.1s;">
                    <div class="card stat-card mb-3">
                        <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
                            <div style="width: 3rem; height: 3rem; background: rgba(245, 158, 11, 0.12); border-radius: 0.8rem; display: grid; place-items: center; color: #f59e0b; font-size: 1.4rem; flex-shrink: 0;">
                                <i class="bi bi-shield-exclamation"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">Report Parking</div>
                                <small class="text-muted">Request vehicle clamping on your property</small>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </div>
                    </div>
                </a>
                <a href="#" class="action-card animate-on-load" style="animation-delay: 0.2s;" onclick="alert('Payment portal coming soon!')">
                    <div class="card stat-card mb-4">
                        <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
                            <div style="width: 3rem; height: 3rem; background: rgba(16, 185, 129, 0.12); border-radius: 0.8rem; display: grid; place-items: center; color: #10b981; font-size: 1.4rem; flex-shrink: 0;">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">Pay Citation</div>
                                <small class="text-muted">Online payment portal</small>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </div>
                    </div>
                </a>

                <div class="card stat-card animate-on-load" style="animation-delay: 0.3s;">
                    <div class="card-body p-3 p-md-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-shield-check me-2 text-primary"></i>What's Available
                        </h6>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="bi bi-eye"></i></div>
                            <div>
                                <strong class="d-block small">View Details</strong>
                                <small class="text-muted">Violation, date, location, amount</small>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="bi bi-images"></i></div>
                            <div>
                                <strong class="d-block small">Evidence Photos</strong>
                                <small class="text-muted">View violation evidence</small>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="bi bi-qr-code"></i></div>
                            <div>
                                <strong class="d-block small">QR Code</strong>
                                <small class="text-muted">Printable reference code</small>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="bi bi-cash"></i></div>
                            <div>
                                <strong class="d-block small">Payment Status</strong>
                                <small class="text-muted">Track payment history</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const simpleRadio = document.getElementById('search_type_simple');
        const advancedRadio = document.getElementById('search_type_advanced');
        const simpleSearch = document.getElementById('simple-search');
        const advancedSearch = document.getElementById('advanced-search');
        const searchForm = document.getElementById('searchForm');

        simpleRadio.addEventListener('change', function() {
            simpleSearch.style.display = 'block';
            advancedSearch.style.display = 'none';
            document.getElementById('search').required = true;
        });

        advancedRadio.addEventListener('change', function() {
            simpleSearch.style.display = 'none';
            advancedSearch.style.display = 'block';
            document.getElementById('search').required = false;
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-on-load').forEach(el => observer.observe(el));
    });
    </script>
</body>
</html>
