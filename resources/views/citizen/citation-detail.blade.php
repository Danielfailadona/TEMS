<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Citation #{{ $citation->citation_number }} — {{ config('itevcms.app_name') }}</title>
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
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" style="z-index: 100;">
    <div class="container-fluid px-4 px-lg-5">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('welcome') }}">
            <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="32" class="me-2">
            <span style="background: linear-gradient(135deg, #0f2b4a, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">TEMs</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('welcome') }}#features">Features</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('citizen.citation.lookup') }}">Ticket Lookup</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('citizen.report-parking.landing') }}">Report Parking</a></li>
                <li class="nav-item">
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign In</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign Up</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

@include('components.alerts')

<div class="container py-4" style="max-width: 1000px;">
    <!-- HERO HEADER -->
    <div class="detail-hero animate-on-load mb-4">
        <div class="detail-hero-content">
            <div>
                <div class="detail-hero-meta mb-3">
                    <div class="detail-hero-badge">
                        <i class="bi bi-receipt me-1"></i>Citation #{{ $citation->citation_number }}
                    </div>
                    <span class="status-badge status-badge-{{ strtolower($citation->status->value) }}">
                        {{ $citation->status->label() }}
                    </span>
                </div>
                <h1>{{ $citation->violationLabel() }}</h1>
                <p class="mb-0 opacity-75">Issued on {{ $citation->issued_at?->format('F d, Y') }} at {{ $citation->location }}</p>
            </div>
            <div class="detail-hero-qr">
                <div class="bg-white p-1 rounded">
                    {!! $citation->getQRCodeSvg(100) !!}
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="detail-grid mb-4">
        <!-- VIOLATION INFO -->
        <div class="detail-section animate-on-load" style="animation-delay: 0.1s;">
            <div class="detail-section-title">
                <i class="bi bi-exclamation-circle me-2"></i>Violation Details
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Violation Code:</span>
                <span class="detail-row-value">{{ $citation->violationType->code }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Description:</span>
                <span class="detail-row-value">{{ $citation->violationType->description ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Penalty Amount:</span>
                <span class="detail-row-value fw-bold text-success fs-5">₱{{ number_format($citation->penalty_amount, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Issued By:</span>
                <span class="detail-row-value">{{ $citation->enforcer->name ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- VEHICLE INFO -->
        <div class="detail-section animate-on-load" style="animation-delay: 0.2s;">
            <div class="detail-section-title">
                <i class="bi bi-car-front-fill me-2"></i>Vehicle Information
            </div>
            <div class="detail-row">
                <span class="detail-row-label">License Plate:</span>
                <span class="detail-row-value fw-bold fs-5">{{ $citation->vehicle_plate }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Make/Model:</span>
                <span class="detail-row-value">{{ $citation->vehicle_make }} {{ $citation->vehicle_model }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Type/Year:</span>
                <span class="detail-row-value">{{ $citation->vehicle_type ?? '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Color:</span>
                <span class="detail-row-value">{{ $citation->vehicle_color ?? '—' }}</span>
            </div>
        </div>

        <!-- DATES & STATUS -->
        <div class="detail-section animate-on-load" style="animation-delay: 0.3s;">
            <div class="detail-section-title">
                <i class="bi bi-calendar me-2"></i>Timeline
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Issued:</span>
                <span class="detail-row-value">{{ $citation->issued_at?->format('M d, Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Due Date:</span>
                <span class="detail-row-value {{ $citation->due_date->isPast() && !$citation->isPaid() ? 'text-danger fw-bold' : '' }}">
                    {{ $citation->due_date->format('M d, Y') }}
                    @if ($citation->due_date->isPast() && !$citation->isPaid())
                        <i class="bi bi-exclamation-triangle ms-1"></i>
                    @endif
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Days Remaining:</span>
                <span class="detail-row-value">
                    @if ($citation->isPaid())
                        <span class="badge tag-green">Paid</span>
                    @elseif ($citation->due_date->isPast())
                        <span class="badge tag-amber">Overdue</span>
                    @else
                        {{ $citation->due_date->diffInDays() }} days
                    @endif
                </span>
            </div>
        </div>

        <!-- DRIVER INFO -->
        <div class="detail-section animate-on-load" style="animation-delay: 0.4s;">
            <div class="detail-section-title">
                <i class="bi bi-person-fill me-2"></i>Driver Information
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Full Name:</span>
                <span class="detail-row-value">{{ $citation->driver_name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">License No.:</span>
                <span class="detail-row-value">{{ $citation->driver_license ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Phone:</span>
                <span class="detail-row-value">{{ '—' }}</span>
            </div>
        </div>
    </div>

    <!-- LOCATION & NOTES -->
    <div class="detail-section animate-on-load" style="animation-delay: 0.5s; margin-bottom: 2rem;">
        <div class="detail-section-title">
            <i class="bi bi-geo-alt-fill me-2"></i>Location & Notes
        </div>
        <div class="detail-row">
            <span class="detail-row-label">Location:</span>
            <span class="detail-row-value">{{ $citation->location }}</span>
        </div>
        @if ($citation->notes)
            <div class="mt-3 p-2 bg-light rounded" style="border-left: 4px solid #2563eb;">
                <small class="text-muted d-block fw-semibold mb-2">Additional Notes:</small>
                <p class="mb-0">{{ $citation->notes }}</p>
            </div>
        @endif
    </div>

    <!-- EVIDENCE SECTION -->
    @if ($citation->evidence->count() > 0)
        <div class="card stat-card mb-4 animate-on-load" style="animation-delay: 0.6s;">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-images me-2"></i>Evidence Photos ({{ $citation->evidence->count() }})</h5>
                <div class="row g-3">
                    @foreach ($citation->evidence as $evidence)
                        <div class="col-md-4">
                            @if (\App\Services\SupabaseStorage::has($evidence->file_path))
                                <div class="position-relative overflow-hidden rounded-2" style="cursor: pointer; height: 200px; background: #f0f0f0;">
                                    <img src="{{ \App\Services\SupabaseStorage::publicUrl($evidence->file_path) }}"
                                         alt="{{ $evidence->original_name }}"
                                         style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                                         onmouseover="this.style.transform='scale(1.05)'"
                                         onmouseout="this.style.transform='scale(1)'"
                                         onclick="openModal('{{ \App\Services\SupabaseStorage::publicUrl($evidence->file_path) }}')">
                                </div>
                                <p class="text-center small text-muted mt-2 mb-0">{{ $evidence->original_name }}</p>
                            @else
                                <div class="d-flex align-items-center justify-content-center rounded-2 fst-italic" style="height: 200px; background: #f0f0f0;">
                                    <span class="text-muted small">Evidence file unavailable</span>
                                </div>
                                <p class="text-center small text-muted mt-2 mb-0">{{ $evidence->original_name }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- PAYMENT STATUS -->
    @if ($citation->payment && $citation->payment->paid_at)
        <div class="card stat-card mb-4 border-success animate-on-load" style="animation-delay: 0.7s;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width: 3rem; height: 3rem; background: rgba(16, 185, 129, 0.12); border-radius: 0.8rem; display: grid; place-items: center; color: #10b981; font-size: 1.5rem;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Payment Confirmed</h5>
                        <p class="text-muted mb-0">Paid on {{ $citation->payment->paid_at?->format('F d, Y') }}</p>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Receipt Number</small>
                        <strong>{{ $citation->payment->receipt_number }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Amount Paid</small>
                        <strong>₱{{ number_format($citation->payment->amount, 2) }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Payment Method</small>
                        <strong>{{ $citation->payment->payment_method->label() }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Processed By</small>
                        <strong>{{ $citation->payment->cashier->name ?? 'N/A' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card stat-card mb-4 border-warning animate-on-load" style="animation-delay: 0.7s;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width: 3rem; height: 3rem; background: rgba(245, 158, 11, 0.12); border-radius: 0.8rem; display: grid; place-items: center; color: #f59e0b; font-size: 1.5rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Payment Pending</h5>
                        <p class="text-muted mb-0">Due by {{ $citation->due_date->format('F d, Y') }}</p>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    @if ($citation->isPayable())
                        <a href="{{ route('public.citation.pay', ['id' => $citation->id, 'token' => $citation->getValidationToken()]) }}" class="btn btn-primary">
                            <i class="bi bi-credit-card me-2"></i>Pay Online
                        </a>
                    @endif
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>Print Citation
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- APPEAL SECTION -->
    @if ($citation->appeal)
        <div class="card stat-card mb-4 animate-on-load" style="animation-delay: 0.8s;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <div style="width: 3rem; height: 3rem; background: rgba(59, 130, 246, 0.12); border-radius: 0.8rem; display: grid; place-items: center; color: #3b82f6; font-size: 1.5rem;">
                        <i class="bi bi-chat-square-text"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Appeal {{ $citation->appeal->status->label() }}</h5>
                        <p class="text-muted mb-0">Submitted on {{ $citation->appeal->submitted_at?->format('F d, Y h:i A') }}</p>
                    </div>
                    <span class="badge {{ $citation->appeal->status->badgeClass() }} ms-auto">{{ $citation->appeal->status->label() }}</span>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Reason</small>
                        <strong>{{ $citation->appeal->reason }}</strong>
                    </div>
                    @if ($citation->appeal->description)
                        <div class="col-12">
                            <small class="text-muted d-block">Details</small>
                            <p class="mb-0">{{ $citation->appeal->description }}</p>
                        </div>
                    @endif
                    @if ($citation->appeal->reviewed_at)
                        <div class="col-md-6">
                            <small class="text-muted d-block">Decision Date</small>
                            <strong>{{ $citation->appeal->reviewed_at->format('F d, Y') }}</strong>
                        </div>
                    @endif
                    @if ($citation->appeal->decision_notes)
                        <div class="col-12">
                            <small class="text-muted d-block">Office Notes</small>
                            <p class="mb-0">{{ $citation->appeal->decision_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @elseif ($citation->isPayable())
        <div class="card stat-card mb-4 animate-on-load" style="animation-delay: 0.8s;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width: 3rem; height: 3rem; background: rgba(59, 130, 246, 0.12); border-radius: 0.8rem; display: grid; place-items: center; color: #3b82f6; font-size: 1.5rem;">
                        <i class="bi bi-chat-square-text"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Want to Contest This Citation?</h5>
                        <p class="text-muted mb-0">Appeals are filed in person at the enforcement office front desk</p>
                    </div>
                </div>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    To contest this citation, please visit the <strong>Front Desk</strong> and present your citation number. A
                    front desk officer will file your appeal and give you a ticket number you can use to track the outcome.
                </div>
            </div>
        </div>
    @endif

    <!-- BACK BUTTON -->
    <div class="text-center mt-4 mb-4">
        <a href="{{ route('citizen.citation.lookup') }}" class="btn btn-outline-secondary no-print">
            <i class="bi bi-arrow-left me-2"></i>Back to Search
        </a>
    </div>
</div>

<!-- IMAGE MODAL -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            <div class="modal-body p-0">
                <img id="modalImage" src="" style="width: 100%; height: auto;">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-on-load').forEach(el => observer.observe(el));

    const navCollapse = document.getElementById('navbarNav');
    document.addEventListener('click', function(e) {
        if (navCollapse.classList.contains('show') && !navCollapse.contains(e.target) && !e.target.closest('.navbar-toggler')) {
            new bootstrap.Collapse(navCollapse).hide();
        }
    });
});

function openModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}
</script>
</body>
</html>
