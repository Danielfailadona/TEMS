@extends('layouts.app')

@section('title', $clamping->notice_number)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">Clamp Notice {{ $clamping->notice_number }}</h1>
        <p class="text-muted mb-0">Vehicle: {{ $clamping->vehicle_plate }}</p>
    </div>
    @php
        $clampStatusLabel = $clamping->status->label();
        if ($clamping->status === \App\Enums\ClampingStatus::AwaitingPayment) {
            $clampStatusLabel = 'Pending';
        }
    @endphp
    <span class="badge {{ $clamping->status->badgeClass() }} fs-6">{{ $clampStatusLabel }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card stat-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong class="text-muted small d-block">Officer</strong>{{ $clamping->officer->name }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Clamped At</strong>{{ $clamping->clamped_at->format('M d, Y h:i A') }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Location</strong>{{ $clamping->location ?? '—' }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Related Citation</strong>{{ $clamping->citation?->citation_number ?? '—' }}</div>
                    @if ($clamping->notes)
                        <div class="col-12"><strong class="text-muted small d-block">Notes</strong>{{ $clamping->notes }}</div>
                    @endif
                </div>
                @if ($clamping->evidence_path)
                    <hr>
                    @if (\App\Services\SupabaseStorage::has($clamping->evidence_path))
                        <img src="{{ \App\Services\SupabaseStorage::publicUrl($clamping->evidence_path) }}" alt="Clamp evidence" class="rounded border" style="max-height:200px">
                    @else
                        <div class="text-muted small fst-italic">Evidence file unavailable</div>
                    @endif
                @endif
            </div>
        </div>

        @if ($clamping->paid_at)
            <div class="card stat-card mt-4">
                <div class="card-header bg-white"><strong><i class="bi bi-cash-coin me-1"></i>Payment Record</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong class="text-muted small d-block">Amount</strong>₱{{ number_format($clamping->clamping_fee, 2) }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Paid At</strong>{{ $clamping->paid_at->format('M d, Y h:i A') }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Method</strong>{{ ucwords(str_replace('_', ' ', $clamping->payment_method ?? '—')) }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Reference</strong>{{ $clamping->reference_number ?? '—' }}</div>
                        @php($clampingPayment = $clamping->payments->firstWhere('paid_at'))
                        @if ($clampingPayment)
                            <div class="col-12">
                                <strong class="text-muted small d-block">Receipt</strong>
                                <span class="d-flex flex-wrap align-items-center gap-2">
                                    <span>{{ $clampingPayment->receipt_number }}</span>
                                    <a href="{{ route('payments.show', $clampingPayment) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                    <a href="{{ route('payments.print', $clampingPayment) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </a>
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        @if (in_array($clamping->status, [App\Enums\ClampingStatus::AwaitingPayment, App\Enums\ClampingStatus::Paid, App\Enums\ClampingStatus::WaitingRelease]))
            <div class="card stat-card">
                <div class="card-header bg-white"><strong>Actions</strong></div>
                <div class="card-body d-grid gap-2">
                    @can('markPaid', $clamping)
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payModal">
                            <i class="bi bi-cash-coin me-1"></i>Mark Paid
                        </button>
                    @endcan
                    @can('markWaitingRelease', $clamping)
                        <form method="POST" action="{{ route('clamping.mark-waiting-release', $clamping) }}" onsubmit="return confirm('Mark as waiting for release?')">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-hourglass-split me-1"></i>Waiting for Release
                            </button>
                        </form>
                    @endcan
                    @can('processRelease', $clamping)
                        <form method="POST" action="{{ route('clamping.process-release', $clamping) }}" onsubmit="return confirm('Release this vehicle?')">
                            @csrf
                            <input type="text" name="notes" class="form-control form-control-sm mb-2" placeholder="Release notes (optional)">
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-unlock me-1"></i>Process Release
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        @endif

        <div class="card stat-card mt-4">
            <div class="card-header bg-white"><strong><i class="bi bi-qr-code me-1"></i>Driver Ticket</strong></div>
            <div class="card-body text-center">
                {!! $clamping->getQRCodeSvg(160) !!}
                <p class="text-muted small mt-2 mb-3">Scan to open the clamping ticket.</p>
                <div class="d-grid gap-2">
                    <a href="{{ route('public.clamping.ticket', ['id' => $clamping->id, 'token' => $clamping->getValidationToken()]) }}" target="_blank" class="btn btn-sm btn-primary">
                        <i class="bi bi-ticket me-1"></i>Open Ticket
                    </a>
                </div>
            </div>
        </div>

        @if ($clamping->impoundingRecord)
            <div class="card stat-card mt-4">
                <div class="card-header bg-white"><strong><i class="bi bi-truck me-1"></i>Impounding</strong></div>
                <div class="card-body text-center">
                    <p class="mb-2">Notice: {{ $clamping->impoundingRecord->notice_number }}</p>
                    @if ($clamping->impoundingRecord->status !== App\Enums\ImpoundingStatus::Released)
                        <a href="{{ route('impounding.show', $clamping->impoundingRecord) }}" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-box-arrow-up-right me-1"></i>View in Impounding
                        </a>
                    @endif
                </div>
            </div>
        @endif

        @if ($clamping->release)
            <div class="card stat-card mt-4">
                <div class="card-header bg-white"><strong><i class="bi bi-check-circle me-1"></i>Release</strong></div>
                <div class="card-body">
                    <p class="mb-1">{{ $clamping->release->release_number }}</p>
                    <p class="mb-0 text-muted small">Released {{ $clamping->release->released_at?->format('M d, Y h:i A') ?? '—' }}</p>
                </div>
            </div>
        @endif
    </div>
</div>

@can('markPaid', $clamping)
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('clamping.mark-paid', $clamping) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Record Payment — {{ $clamping->notice_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Amount (₱)</label>
                    <input type="number" step="0.01" min="0" name="clamping_fee" class="form-control" required
                           value="{{ $clamping->clamping_fee ?? $clamping->citation?->penalty_amount ?? 0 }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Payment Method</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold small">Reference Number</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="OR # / reference (optional)">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Payment</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection