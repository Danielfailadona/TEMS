@extends('layouts.app')

@section('content')
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
                <h1>{{ $citation->violationType->name }}</h1>
                <p class="mb-0 opacity-75">Issued on {{ $citation->issued_at?->format('F d, Y') }} at {{ $citation->location }}</p>
            </div>
            <div class="detail-hero-qr">
                <img src="{{ $citation->getQRCodeUrl() }}" alt="Citation QR Code" style="width: 100px;">
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
                        <span class="badge bg-success">Paid</span>
                    @elseif ($citation->due_date->isPast())
                        <span class="badge bg-danger">Overdue</span>
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
                            <div class="position-relative overflow-hidden rounded-2" style="cursor: pointer; height: 200px; background: #f0f0f0;">
                                <img src="{{ asset('storage/' . $evidence->file_path) }}"
                                     alt="{{ $evidence->original_name }}"
                                     style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform='scale(1)'"
                                     onclick="openModal('{{ asset('storage/' . $evidence->file_path) }}')">
                            </div>
                            <p class="text-center small text-muted mt-2 mb-0">{{ $evidence->original_name }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- PAYMENT STATUS -->
    @if ($citation->payment)
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
                        <strong>{{ ucfirst($citation->payment->payment_method) }}</strong>
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
                    <button class="btn btn-primary" onclick="alert('Payment portal coming soon!')">
                        <i class="bi bi-credit-card me-2"></i>Pay Online
                    </button>
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>Print Citation
                    </button>
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
});

function openModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}
</script>

@endsection

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-receipt me-2"></i>Citation Details
                </h2>
                <a href="{{ route('citizen.citation.lookup') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>New Search
                </a>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-{{ $citation->status->badgeClass() }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            {{ $citation->citation_number }}
                            <span class="badge bg-white text-dark ms-2">{{ $citation->status->label() }}</span>
                        </h4>
                        <span class="text-white-50">{{ $citation->issued_at?->format('M d, Y h:i A') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Violation Information</h6>
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-muted fw-normal w-30">Violation:</th>
                                        <td class="fw-semibold">{{ $citation->violationType->name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Code:</th>
                                        <td>{{ $citation->violationType->code }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Description:</th>
                                        <td class="text-muted">{{ $citation->violationType->description ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Penalty Amount:</th>
                                        <td class="fw-bold text-primary fs-5">&#8369;{{ number_format($citation->penalty_amount, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Location & Time</h6>
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-muted fw-normal w-30">Location:</th>
                                        <td>{{ $citation->location }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Date/Time:</th>
                                        <td>{{ $citation->issued_at?->format('F d, Y \a\t h:i A') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Due Date:</th>
                                        <td>
                                            <span class="{{ $citation->due_date->isPast() && !$citation->isPaid() ? 'text-danger fw-bold' : '' }}">
                                                {{ $citation->due_date->format('F d, Y') }}
                                                @if ($citation->due_date->isPast() && !$citation->isPaid())
                                                    <i class="bi bi-exclamation-triangle ms-1"></i> Overdue
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Enforcer:</th>
                                        <td>{{ $citation->enforcer->name ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Vehicle Information</h6>
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-muted fw-normal w-30">Plate Number:</th>
                                        <td class="fw-bold fs-5">{{ $citation->vehicle_plate }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Type:</th>
                                        <td>{{ $citation->vehicle_type ?? '—' }} - {{ $citation->vehicle_make }} {{ $citation->vehicle_model }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Color/Year:</th>
                                        <td>{{ $citation->vehicle_color ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Driver:</th>
                                        <td>{{ $citation->driver_name ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Notes</h6>
                            <p class="mb-0">{{ $citation->notes ?? 'No additional notes.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if ($citation->evidence->count() > 0)
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-images me-2"></i>Evidence ({{ $citation->evidence->count() }})
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($citation->evidence as $evidence)
                                <div class="col-md-4">
                                    <div class="card">
                                        <img src="{{ asset('storage/' . $evidence->file_path) }}"
                                             class="card-img-top"
                                             alt="{{ $evidence->original_name }}"
                                             style="height: 200px; object-fit: cover;"
                                             onclick="openModal('{{ asset('storage/' . $evidence->file_path) }}')"
                                             style="cursor: zoom-in;">
                                        <div class="card-body p-2">
                                            <p class="card-text small text-truncate mb-0">{{ $evidence->original_name }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if ($citation->payment)
                <div class="card shadow-sm mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-check-circle me-2"></i>Payment Confirmed
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <strong>Receipt Number:</strong><br>
                                {{ $citation->payment->receipt_number }}
                            </div>
                            <div class="col-md-4">
                                <strong>Amount Paid:</strong><br>
                                &#8369;{{ number_format($citation->payment->amount, 2) }}
                            </div>
                            <div class="col-md-4">
                                <strong>Date:</strong><br>
                                {{ $citation->payment->paid_at?->format('F d, Y h:i A') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Cashier:</strong><br>
                                {{ $citation->payment->cashier->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Payment Method:</strong><br>
                                {{ ucfirst($citation->payment->payment_method) }}
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card shadow-sm mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Payment Pending
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">This citation has not been paid yet. The due date is <strong>{{ $citation->due_date->format('F d, Y') }}</strong>.</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="#" class="btn btn-primary">
                                <i class="bi bi-credit-card me-1"></i>Pay Online
                            </a>
                            <a href="#" class="btn btn-outline-secondary">
                                <i class="bi bi-bank me-1"></i>Payment Centers
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if ($citation->appeal)
                <div class="card shadow-sm mb-4 border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-chat-square-text me-2"></i>Appeal Submitted
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <span class="badge bg-{{ $citation->appeal->status->badgeClass() }}">{{ $citation->appeal->status->label() }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Submitted:</strong><br>
                                {{ $citation->appeal->submitted_at?->format('F d, Y h:i A') }}
                            </div>
                            <div class="col-12">
                                <strong>Reason:</strong>
                                <p class="mb-0 mt-1">{{ $citation->appeal->reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                @if ($citation->isPayable())
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-chat-square-text me-2"></i>Want to Contest This Citation?</h6>
                            <a href="#" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle me-1"></i>File an Appeal
                            </a>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Evidence Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="modalImage" src="" alt="Evidence" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(src) {
        document.getElementById('modalImage').src = src;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
</script>
@endsection