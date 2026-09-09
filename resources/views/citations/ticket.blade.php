<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Citation #{{ $citation->citation_number }} — {{ config('itevcms.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 560px;">
    {{-- HEADER --}}
    <div class="text-center mb-4">
        <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="56" class="mb-2">
        <h4 class="mb-0 fw-bold">{{ config('itevcms.app_name') }}</h4>
        <p class="text-muted small mb-0">Transportation Enforcement Management System</p>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="text-muted small text-uppercase fw-semibold mb-1">Citation Ticket</div>
            <h2 class="h3 mb-0 fw-bold">#{{ $citation->citation_number }}</h2>
        </div>
        <span class="badge {{ $citation->status->badgeClass() }} fs-6">{{ $citation->status->label() }}</span>
    </div>

    @include('components.alerts')

    @if ($citation->status === App\Enums\CitationStatus::Overdue)
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>This citation is overdue.</span>
        </div>
    @endif

    @if ($citation->status === App\Enums\CitationStatus::Clamped)
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-truck-front-fill"></i>
            <span>This vehicle has been referred for impounding.</span>
        </div>
    @endif

    {{-- CITATION DETAILS --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h6 class="card-title text-muted text-uppercase small fw-semibold mb-3">
                <i class="bi bi-file-earmark-text me-1"></i>Citation Details
            </h6>
            <div class="row g-3">
                <div class="col-6">
                    <small class="text-muted d-block">Violation</small>
                    <strong>{{ $citation->violationType->name }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Penalty Amount</small>
                    <strong class="fs-5 text-success">₱{{ number_format($citation->penalty_amount, 2) }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Date Issued</small>
                    <strong>{{ $citation->issued_at?->format('F d, Y') }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Due Date</small>
                    <strong class="{{ $citation->due_date->isPast() && !$citation->isPaid() ? 'text-danger' : '' }}">
                        {{ $citation->due_date?->format('F d, Y') }}
                    </strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Plate Number</small>
                    <strong>{{ $citation->vehicle_plate }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Vehicle</small>
                    <strong>{{ $citation->vehicle_make }} {{ $citation->vehicle_model }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Issued By</small>
                    <strong>{{ $citation->enforcer->name ?? '—' }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Location</small>
                    <strong>{{ $citation->location ?? '—' }}</strong>
                </div>
                @if ($citation->driver_name)
                    <div class="col-6">
                        <small class="text-muted d-block">Driver</small>
                        <strong>{{ $citation->driver_name }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- EVIDENCE --}}
    @if ($citation->evidence->isNotEmpty())
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="card-title text-muted text-uppercase small fw-semibold mb-3">
                    <i class="bi bi-images me-1"></i>Evidence ({{ $citation->evidence->count() }})
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($citation->evidence as $evidence)
                        <img src="{{ asset('storage/'.$evidence->file_path) }}"
                             alt="Evidence"
                             class="rounded border"
                             style="width: 96px; height: 96px; object-fit: cover; cursor: zoom-in;"
                             onclick="openModal('{{ asset('storage/'.$evidence->file_path) }}')">
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- PAYMENT STATUS + ACTIONS --}}
    @if ($citation->isPaid() && $citation->payment)
        <div class="card shadow-sm border-success mb-3">
            <div class="card-body text-center py-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <h5 class="mt-2 mb-1">This citation has been paid.</h5>
                <p class="text-muted mb-0 small">
                    Receipt #{{ $citation->payment->receipt_number }} · Paid on {{ $citation->payment->paid_at?->format('F d, Y') }}
                </p>
            </div>
        </div>
    @elseif ($citation->isPayable())
        <div class="card shadow-sm mb-3">
            <div class="card-body d-grid gap-2">
                <form method="POST" action="{{ route('public.citation.checkout', ['id' => $citation->id, 'token' => $citation->getValidationToken()]) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-semibold">
                        <i class="bi bi-credit-card me-2"></i>Pay Online (GCash, Maya, Card)
                    </button>
                </form>
                <a href="{{ route('public.citation.print', ['id' => $citation->id, 'token' => $citation->getValidationToken()]) }}"
                   class="btn btn-outline-secondary w-100 py-3">
                    <i class="bi bi-printer me-2"></i>Print / Download Copy
                </a>
                <div class="alert alert-info mb-0 mt-1">
                    <i class="bi bi-bank me-2"></i>
                    <small><strong>Pay at Office:</strong> Bring this ticket number to the office. Ask for the citation
                        <strong>#{{ $citation->citation_number }}</strong> and pay in person.</small>
                </div>
            </div>
        </div>
    @else
        <div class="card shadow-sm border-secondary mb-3">
            <div class="card-body text-center py-4">
                <i class="bi bi-info-circle-fill text-secondary" style="font-size: 3rem;"></i>
                <h5 class="mt-2 mb-1">Payment is not available for this citation.</h5>
                <p class="text-muted mb-0 small">Please contact the office for assistance.</p>
            </div>
        </div>
    @endif

    {{-- QR — same public URL for re-scan --}}
    <div class="card shadow-sm">
        <div class="card-body text-center py-4">
            <div class="small text-muted mb-2">Scan to re-open this ticket on another device</div>
            <div class="bg-white p-3 d-inline-block rounded">
                {!! $citation->getQRCodeSvg(200) !!}
            </div>
        </div>
    </div>

    <p class="text-center text-muted small mt-4 mb-0">
        For verification, please present this ticket at the office.
    </p>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Evidence Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
</body>
</html>