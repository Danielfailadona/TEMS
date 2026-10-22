<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay Citation #{{ $citation->citation_number }} — {{ config('itevcms.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8fbff; min-height: 100vh; }
        .animate-on-load { opacity: 0; transform: translateY(12px); transition: opacity 0.55s ease, transform 0.55s ease; }
        .animate-on-load.is-visible { opacity: 1; transform: translateY(0); }
        .stat-card { border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 1.1rem; box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06); background: rgba(255, 255, 255, 0.92); }
        .form-control:focus { border-color: rgba(37, 99, 235, 0.45); box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.16); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" style="z-index: 100;">
    <div class="container-fluid px-4 px-lg-5">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('welcome') }}">
            <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="32" class="me-2">
            <span style="background: linear-gradient(135deg, #0f2b4a, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">TEMs</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('citizen.citation.lookup') }}">Ticket Lookup</a></li>
                <li class="nav-item"><a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign In</a></li>
            </ul>
        </div>
    </div>
</nav>

@include('components.alerts')

<div class="container py-4" style="max-width: 900px;">
    <div class="mb-4 animate-on-load">
        <a href="{{ route('public.citation.ticket', ['id' => $citation->id, 'token' => $token]) }}" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Ticket
        </a>
        <h1 class="h3 mb-1"><i class="bi bi-credit-card me-2 text-primary"></i>Pay Citation</h1>
        <p class="text-muted mb-0">Details are filled in from your citation — review and confirm to pay online.</p>
    </div>

    @if ($citation->payment && $citation->payment->paid_at)
        <div class="alert alert-success animate-on-load">
            <i class="bi bi-check-circle me-1"></i>This citation has already been paid on {{ $citation->payment->paid_at->format('F d, Y') }}.
            Receipt: <strong>{{ $citation->payment->receipt_number }}</strong>
        </div>
    @elseif (! $citation->isPayable())
        <div class="alert alert-warning animate-on-load">
            <i class="bi bi-exclamation-circle me-1"></i>This citation is not eligible for online payment. Please settle it at the enforcement office.
        </div>
    @else
        <div class="row g-4">
            {{-- Autofilled citation summary (read-only) --}}
            <div class="col-lg-6">
                <div class="card stat-card h-100 animate-on-load">
                    <div class="card-header bg-white"><strong>Citation Details</strong></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6"><small class="text-muted d-block">Citation #</small><strong>{{ $citation->citation_number }}</strong></div>
                            <div class="col-6"><small class="text-muted d-block">Plate Number</small><strong>{{ $citation->vehicle_plate ?: '—' }}</strong></div>
                            <div class="col-12"><small class="text-muted d-block">Violation</small><strong>{{ $citation->violationLabel() }}</strong></div>
                            <div class="col-6"><small class="text-muted d-block">Issued</small>{{ $citation->issued_at?->format('M d, Y') }}</div>
                            <div class="col-6"><small class="text-muted d-block">Due Date</small>{{ $citation->due_date?->format('M d, Y') }}</div>
                            <div class="col-12"><small class="text-muted d-block">Location</small>{{ $citation->location ?: '—' }}</div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Amount Due</span>
                            <span class="fs-4 fw-bold text-success">₱{{ number_format($citation->penalty_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Autofilled payment form --}}
            <div class="col-lg-6">
                <div class="card stat-card h-100 animate-on-load">
                    <div class="card-header bg-white"><strong>Payment Details</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('public.citation.checkout', ['id' => $citation->id, 'token' => $token]) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Payer Name</label>
                                <input type="text" name="payer_name" class="form-control" value="{{ $payerName }}" placeholder="Full name">
                                @error('payer_name')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="payer_phone" class="form-control" value="{{ $payerPhone }}" placeholder="+63 9XX XXX XXXX">
                                @error('payer_phone')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control" value="{{ number_format($citation->penalty_amount, 2) }}" disabled>
                                </div>
                            </div>
                            <div class="alert alert-info small mb-3">
                                <i class="bi bi-info-circle me-1"></i>You will be redirected to the secure PayMongo payment page to complete this transaction.
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-lock-fill me-2"></i>Proceed to Pay ₱{{ number_format($citation->penalty_amount, 2) }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('is-visible'); });
    }, { threshold: 0.1 });
    document.querySelectorAll('.animate-on-load').forEach(el => observer.observe(el));

    const navCollapse = document.getElementById('navbarNav');
    if (navCollapse && window.bootstrap) {
        document.addEventListener('click', function(e) {
            if (navCollapse.classList.contains('show') && !navCollapse.contains(e.target) && !e.target.closest('.navbar-toggler')) {
                new bootstrap.Collapse(navCollapse).hide();
            }
        });
    }
});
</script>
</body>
</html>
