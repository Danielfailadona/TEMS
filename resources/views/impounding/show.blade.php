@extends('layouts.app')

@section('title', 'Impounded — '.$impounding->vehicle_plate)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Operations console</a></li>
                <li class="breadcrumb-item"><a href="{{ route('impounding.index') }}">Impounding</a></li>
            </ol>
        </nav>
        <h1 class="h3 mb-1">{{ $impounding->vehicle_plate }}</h1>
        <p class="text-muted mb-0">Notice: {{ $impounding->notice_number }}</p>
    </div>
    <div class="d-flex gap-2 no-print">
        @can('markPaid', $impounding)
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash-stack me-1"></i>Record Payment</button>
        @endcan
        @can('markWaitingRelease', $impounding)
            <form action="{{ route('impounding.mark-waiting-release', $impounding) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning"><i class="bi bi-clock me-1"></i>Queue for Release</button>
            </form>
        @endcan
        @can('processRelease', $impounding)
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#releaseModal"><i class="bi bi-check-all me-1"></i>Process Release</button>
        @endcan
        @if ($impounding->status === App\Enums\ImpoundingStatus::Released)
            <a href="{{ route('impounding.print-release', $impounding) }}" class="btn btn-outline-info" target="_blank"><i class="bi bi-printer me-1"></i>Print Release Order</a>
        @endif
        <a href="{{ route('impounding.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card stat-card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Impounding Details</strong>
                <span class="badge {{ $impounding->status->badgeClass() }} fs-6">{{ $impounding->status->label() }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong class="text-muted small d-block">Notice Number</strong>{{ $impounding->notice_number }}</div>
                    <div class="col-md-6"><div class="text-muted small d-block">Vehicle Plate</div>{{ $impounding->vehicle_plate }}</div>
                    <div class="col-md-6"><div class="text-muted small d-block">Impounding Officer</div>{{ $impounding->officer->name }}</div>
                    <div class="col-md-6"><div class="text-muted small d-block">Impounded At</div>{{ $impounding->impounded_at->format('M d, Y h:i A') }}</div>
                    <div class="col-md-6"><div class="text-muted small d-block">Location</div>{{ $impounding->location ?? '—' }}</div>
                    <div class="col-md-6"><div class="text-muted small d-block">Related Citation</div>
                        @if ($impounding->citation)
                            <a href="{{ route('citations.show', $impounding->citation) }}">{{ $impounding->citation->citation_number }}</a>
                        @else
                            —
                        @endif
                    </div>
                    @if ($impounding->notes)
                        <div class="col-12"><div class="text-muted small d-block">Notes</div>{{ $impounding->notes }}</div>
                    @endif
                </div>
                @if ($impounding->evidence_path)
                    <hr>
                    @if (\App\Services\SupabaseStorage::has($impounding->evidence_path))
                        <img src="{{ \App\Services\SupabaseStorage::publicUrl($impounding->evidence_path) }}" alt="Evidence" class="rounded border" style="max-height:200px">
                    @else
                        <div class="text-muted small fst-italic">Evidence file unavailable</div>
                    @endif
                @endif
            </div>
        </div>

        @if ($impounding->citation)
            <div class="card stat-card mb-4">
                <div class="card-header bg-white"><strong>Citation Details</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="text-muted small d-block">Citation #</div>{{ $impounding->citation->citation_number }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Violation</div>{{ $impounding->citation->violationType->name }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Penalty Amount</div>₱{{ number_format($impounding->citation->penalty_amount, 2) }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Status</div><span class="badge bg-{{ $impounding->citation->status->label() === 'Paid' ? 'success' : 'warning' }}">{{ $impounding->citation->status->label() }}</span></div>
                        <div class="col-md-6"><div class="text-muted small d-block">Driver</div>{{ $impounding->citation->driver_name ?? '—' }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Due Date</div>{{ $impounding->citation->due_date?->format('M d, Y') ?? '—' }}</div>
                    </div>
                </div>
            </div>
        @endif

        @if ($impounding->clampingRecord)
            <div class="card stat-card mb-4">
                <div class="card-header bg-white"><strong>Clamping Details</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="text-muted small d-block">Notice #</div>{{ $impounding->clampingRecord->notice_number }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Clamped At</div>{{ $impounding->clampingRecord->clamped_at?->format('M d, Y h:i A') ?? '—' }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Status</div><span class="badge {{ $impounding->clampingRecord->status->badgeClass() }}">{{ $impounding->clampingRecord->status->label() }}</span></div>
                        <div class="col-md-6"><div class="text-muted small d-block">Officer</div>{{ $impounding->clampingRecord->officer->name ?? '—' }}</div>
                        @if ($impounding->clampingRecord->location)
                            <div class="col-md-6"><div class="text-muted small d-block">Location</div>{{ $impounding->clampingRecord->location }}</div>
                        @endif
                        @if ($impounding->clampingRecord->clamping_fee)
                            <div class="col-md-6"><div class="text-muted small d-block">Clamping Fee</div>₱{{ number_format($impounding->clampingRecord->clamping_fee, 2) }}</div>
                        @endif
                        <div class="col-12">
                            <a href="{{ route('clamping.show', $impounding->clampingRecord) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Open Clamp Record</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card stat-card mb-4">
            <div class="card-header bg-white"><strong>Impounding Fee Breakdown</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card stat-card-sm text-center">
                            <div class="stat-value text-danger">{{ $impounding->towing_fee ? '₱' . number_format($impounding->towing_fee, 2) : '—' }}</div>
                            <div class="stat-label">Towing Fee</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card-sm text-center">
                            <div class="stat-value text-warning">{{ $impounding->storage_fee_per_day ? '₱' . number_format($impounding->storage_fee_per_day, 2) : '—' }}</div>
                            <div class="stat-label">Storage/Day</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card-sm text-center">
                            <div class="stat-value text-info">{{ $impounding->admin_fee ? '₱' . number_format($impounding->admin_fee, 2) : '—' }}</div>
                            <div class="stat-label">Admin Fee</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card-sm text-center">
                            <div class="stat-value text-primary">{{ $impounding->getStorageDays() }}</div>
                            <div class="stat-label">Storage Days</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card-sm text-center">
                            <div class="stat-value text-success">₱{{ number_format($impounding->getTotalFees(), 2) }}</div>
                            <div class="stat-label"><strong>Total Due</strong></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card-sm text-center">
                            <div class="stat-value text-muted">{{ $impounding->grace_until ? $impounding->grace_until->format('M d, Y H:i') : '—' }}</div>
                            <div class="stat-label">Grace Until</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php($ownPayment = $impounding->payments->firstWhere('paid_at'))
        @if ($ownPayment || $impounding->citation?->payment)
            @php($payment = $ownPayment ?? $impounding->citation->payment)
            <div class="card stat-card mb-4">
                <div class="card-header bg-white">
                    <strong>{{ $ownPayment ? 'Payment Record' : 'Citation Payment Record' }}</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="text-muted small d-block">Receipt #</div>{{ $payment->receipt_number }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Amount Paid</div>₱{{ number_format($payment->amount, 2) }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Method</div>{{ $payment->payment_method->label() }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Paid At</div>{{ $payment->paid_at?->format('M d, Y h:i A') ?? '—' }}</div>
                        <div class="col-md-6"><div class="text-muted small d-block">Cashier</div>{{ $payment->cashier->name ?? 'Online Payment' }}</div>
                        @if ($payment->reference_number)
                            <div class="col-md-6"><div class="text-muted small d-block">Reference</div>{{ $payment->reference_number }}</div>
                        @endif
                        <div class="col-12">
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View Receipt
                            </a>
                            <a href="{{ route('payments.print', $payment) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-printer me-1"></i>Print
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card stat-card mb-4">
            <div class="card-header bg-white"><strong>Status Timeline</strong></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex align-items-center gap-2">
                        <span class="badge bg-danger rounded-circle p-1" style="width:10px;height:10px;"></span>
                        <div>
                            <small class="fw-semibold d-block">Impounded</small>
                            <small class="text-muted">{{ $impounding->impounded_at->format('M d, Y h:i A') }}</small>
                        </div>
                    </div>
                    @if ($impounding->status === App\Enums\ImpoundingStatus::Paid || $impounding->status === App\Enums\ImpoundingStatus::WaitingRelease || $impounding->status === App\Enums\ImpoundingStatus::Released)
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-1" style="width:10px;height:10px;"></span>
                            <div>
                                <small class="fw-semibold d-block">Paid</small>
                                <small class="text-muted">{{ $impounding->paid_at?->format('M d, Y h:i A') ?? '—' }}</small>
                            </div>
                        </div>
                    @endif
                    @if ($impounding->status === App\Enums\ImpoundingStatus::WaitingRelease || $impounding->status === App\Enums\ImpoundingStatus::Released)
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge bg-warning rounded-circle p-1" style="width:10px;height:10px;"></span>
                            <div>
                                <small class="fw-semibold d-block">Waiting to Release</small>
                            </div>
                        </div>
                    @endif
                    @if ($impounding->status === App\Enums\ImpoundingStatus::Released)
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge bg-success rounded-circle p-1" style="width:10px;height:10px;"></span>
                            <div>
                                <small class="fw-semibold d-block">Released</small>
                                <small class="text-muted">{{ $impounding->release?->released_at?->format('M d, Y h:i A') ?? '—' }}</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($impounding->release)
            <div class="card stat-card mb-4">
                <div class="card-header bg-white"><strong>Release Details</strong></div>
                <div class="card-body">
                    <div class="mb-2"><div class="text-muted small d-block">Release #</div>{{ $impounding->release->release_number }}</div>
                    <div class="mb-2"><div class="text-muted small d-block">Released By</div>{{ $impounding->release->releasedBy->name }}</div>
                    <div class="mb-2"><div class="text-muted small d-block">Released At</div>{{ $impounding->release->released_at?->format('M d, Y h:i A') ?? '—' }}</div>
                    @if ($impounding->release->notes)
                        <div><div class="text-muted small d-block">Notes</div>{{ $impounding->release->notes }}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@can('markPaid', $impounding)
    <div class="modal fade" id="payModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('impounding.mark-paid', $impounding) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment — {{ $impounding->vehicle_plate }}</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Amount due: <strong>₱{{ number_format($impounding->getTotalFees(), 2) }}</strong></p>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                @foreach (App\Enums\PaymentMethod::cases() as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Confirm Payment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endcan

@can('processRelease', $impounding)
    <div class="modal fade" id="releaseModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('impounding.process-release', $impounding) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Process Release — {{ $impounding->vehicle_plate }}</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Confirm release of vehicle <strong>{{ $impounding->vehicle_plate }}</strong>?</p>
                        <div class="mb-3">
                            <label class="form-label">Release Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Release Vehicle</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endcan
@endsection