<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Clamp Notice #{{ $clamping->notice_number }} — {{ config('itevcms.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @page { margin: 12mm; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 560px;">
    {{-- HEADER --}}
    <div class="text-center mb-4">
        <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="56" class="mb-2">
        <h4 class="mb-0 fw-bold">{{ config('itevcms.app_name') }}</h4>
        <p class="text-muted small mb-0">Traffic Enforcement Management System</p>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="text-muted small text-uppercase fw-semibold mb-1">Clamping Ticket</div>
            <h2 class="h3 mb-0 fw-bold">#{{ $clamping->notice_number }}</h2>
        </div>
        <span class="badge {{ $clamping->status->badgeClass() }} fs-6">{{ $clamping->status->label() }}</span>
    </div>

    @include('components.alerts')

    <div class="no-print d-print-none mb-3">
        <a href="{{ route('public.clamping.print', ['id' => $clamping->id, 'token' => $clamping->getValidationToken()]) }}"
           class="btn btn-outline-secondary w-100 py-3">
            <i class="bi bi-printer me-2"></i>Print / Download Copy
        </a>
    </div>

    {{-- CLAMPING DETAILS --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h6 class="card-title text-muted text-uppercase small fw-semibold mb-3">
                <i class="bi bi-car-front me-1"></i>Clamping Details
            </h6>
            <div class="row g-3">
                <div class="col-6">
                    <small class="text-muted d-block">Plate Number</small>
                    <strong>{{ $clamping->vehicle_plate }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Clamped At</small>
                    <strong>{{ $clamping->clamped_at?->format('F d, Y h:i A') }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Officer</small>
                    <strong>{{ $clamping->officer->name ?? '—' }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Location</small>
                    <strong>{{ $clamping->location ?? '—' }}</strong>
                </div>
                @if ($clamping->citation)
                    <div class="col-6">
                        <small class="text-muted d-block">Related Citation</small>
                        <strong>{{ $clamping->citation->citation_number }}</strong>
                    </div>
                @endif
                @if ($clamping->notes)
                    <div class="col-12">
                        <small class="text-muted d-block">Notes</small>
                        <strong>{{ $clamping->notes }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- EVIDENCE --}}
    @if ($clamping->evidence_path)
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="card-title text-muted text-uppercase small fw-semibold mb-3">
                    <i class="bi bi-camera me-1"></i>Evidence
                </h6>
                <img src="{{ \App\Services\SupabaseStorage::publicUrl($clamping->evidence_path) }}"
                     alt="Evidence"
                     class="rounded border w-100"
                     style="max-height: 300px; object-fit: cover; cursor: zoom-in;"
                     onclick="openModal('{{ \App\Services\SupabaseStorage::publicUrl($clamping->evidence_path) }}')">
            </div>
        </div>
    @endif

    {{-- PAYMENT STATUS + ACTIONS --}}
    @if ($clamping->paid_at)
        <div class="card shadow-sm border-success mb-3">
            <div class="card-body py-4">
                <div class="text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-2 mb-1">Payment received.</h5>
                    <p class="text-muted mb-0 small">
                        ₱{{ number_format($clamping->clamping_fee, 2) }} · Paid on {{ $clamping->paid_at->format('F d, Y') }}
                        @if ($clamping->payment_method) · {{ ucwords(str_replace('_', ' ', $clamping->payment_method)) }}
                        @endif
                    </p>
                </div>

                @php($clampingPayment = $clamping->payments->first())
                @if ($clampingPayment)
                    <div class="bg-white border rounded p-3 small mt-4 text-start">
                        <h6 class="text-muted text-uppercase small fw-semibold mb-3">
                            <i class="bi bi-receipt me-1"></i>Official Receipt
                        </h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <span class="text-muted d-block">Receipt #</span>
                                <strong>{{ $clampingPayment->receipt_number }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Related Citation</span>
                                <strong>{{ $clampingPayment->citation?->citation_number ?? '—' }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Amount Paid</span>
                                <strong>₱{{ number_format($clampingPayment->amount, 2) }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Payment Method</span>
                                <strong>{{ ucwords(str_replace('_', ' ', $clampingPayment->payment_method?->value ?? '—')) }}</strong>
                            </div>
                            @if ($clampingPayment->reference_number)
                                <div class="col-6">
                                    <span class="text-muted d-block">Reference</span>
                                    <strong>{{ $clampingPayment->reference_number }}</strong>
                                </div>
                            @endif
                            <div class="col-6">
                                <span class="text-muted d-block">Cashier</span>
                                <strong>{{ $clampingPayment->cashier?->name ?? 'Office' }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Date Paid</span>
                                <strong>{{ $clampingPayment->paid_at?->format('M d, Y h:i A') }}</strong>
                            </div>
                            <div class="col-12">
                                <span class="text-muted d-block">Vehicle</span>
                                <strong>{{ $clamping->vehicle_plate }}</strong>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @elseif($clamping->status !== App\Enums\ClampingStatus::Released)
        <div class="card shadow-sm mb-3">
            <div class="card-body d-grid gap-2">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-bank me-2"></i>
                    <small><strong>Pay at Office:</strong> To release this vehicle, bring this notice number
                        <strong>#{{ $clamping->notice_number }}</strong> to the office and pay the applicable clamping fee in person.</small>
                </div>
            </div>
        </div>
    @endif

    {{-- RELEASE STATUS --}}
    @if ($clamping->status === App\Enums\ClampingStatus::Released)
        <div class="card shadow-sm border-secondary mb-3">
            <div class="card-body text-center py-4">
                <i class="bi bi-check2-circle text-secondary" style="font-size: 3rem;"></i>
                <h5 class="mt-2 mb-1">Vehicle released.</h5>
                <p class="text-muted mb-0 small">
                    {{ $clamping->release?->release_number ?? 'Released' }} ·
                    {{ $clamping->released_at?->format('F d, Y') ?? $clamping->release?->released_at?->format('F d, Y') }}
                </p>
            </div>
        </div>
    @endif

    {{-- QR — same public URL for re-scan --}}
    <div class="card shadow-sm">
        <div class="card-body text-center py-4">
            <div class="small text-muted mb-2">Scan to re-open this ticket on another device</div>
            <div class="bg-white p-3 d-inline-block rounded">
                {!! $clamping->getQRCodeSvg(200) !!}
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