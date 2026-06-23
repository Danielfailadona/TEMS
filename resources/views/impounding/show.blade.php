@extends('layouts.app')

@section('title', 'Impounded — '.$clamping->vehicle_plate)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $clamping->vehicle_plate }}</h1>
        <p class="text-muted mb-0">Notice: {{ $clamping->notice_number }}</p>
    </div>
    <div class="d-flex gap-2 no-print">
        @can('markPaid', $clamping)
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash-stack me-1"></i>Record Payment</button>
            @if ($clamping->citation && $clamping->citation->isPayable())
                <form action="{{ route('citations.checkout', $clamping->citation) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-globe me-1"></i>Pay Online</button>
                </form>
            @endif
        @endcan
        @can('markWaitingRelease', $clamping)
            <form action="{{ route('impounding.mark-waiting-release', $clamping) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning"><i class="bi bi-clock me-1"></i>Queue for Release</button>
            </form>
        @endcan
        @can('processRelease', $clamping)
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#releaseModal"><i class="bi bi-check-all me-1"></i>Process Release</button>
        @endcan
        @if ($clamping->status === App\Enums\ClampingStatus::Released)
            <a href="{{ route('impounding.print-release', $clamping) }}" class="btn btn-outline-info" target="_blank"><i class="bi bi-printer me-1"></i>Print Release Order</a>
        @endif
        <a href="{{ route('impounding.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card stat-card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Clamping Details</strong>
                <span class="badge {{ $clamping->status->badgeClass() }} fs-6">{{ $clamping->status->label() }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong class="text-muted small d-block">Notice Number</strong>{{ $clamping->notice_number }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Vehicle Plate</strong>{{ $clamping->vehicle_plate }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Clamping Officer</strong>{{ $clamping->officer->name }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Clamped At</strong>{{ $clamping->clamped_at->format('M d, Y h:i A') }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Location</strong>{{ $clamping->location ?? '—' }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Related Citation</strong>
                        @if ($clamping->citation)
                            <a href="{{ route('citations.show', $clamping->citation) }}">{{ $clamping->citation->citation_number }}</a>
                        @else
                            —
                        @endif
                    </div>
                    @if ($clamping->notes)
                        <div class="col-12"><strong class="text-muted small d-block">Clamping Notes</strong>{{ $clamping->notes }}</div>
                    @endif
                </div>
                @if ($clamping->evidence_path)
                    <hr>
                    <img src="{{ asset('storage/'.$clamping->evidence_path) }}" alt="Evidence" class="rounded border" style="max-height:200px">
                @endif
            </div>
        </div>

        @if ($clamping->citation)
            <div class="card stat-card mb-4">
                <div class="card-header bg-white"><strong>Citation Details</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong class="text-muted small d-block">Citation #</strong>{{ $clamping->citation->citation_number }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Violation</strong>{{ $clamping->citation->violationType->name }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Penalty Amount</strong>₱{{ number_format($clamping->citation->penalty_amount, 2) }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Status</strong><span class="badge bg-{{ $clamping->citation->status->label() === 'Paid' ? 'success' : 'warning' }}">{{ $clamping->citation->status->label() }}</span></div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Driver</strong>{{ $clamping->citation->driver_name ?? '—' }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Due Date</strong>{{ $clamping->citation->due_date?->format('M d, Y') ?? '—' }}</div>
                    </div>
                </div>
            </div>
        @endif

        @if ($clamping->citation?->payment)
            <div class="card stat-card mb-4">
                <div class="card-header bg-white"><strong>Payment Record</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong class="text-muted small d-block">Receipt #</strong>{{ $clamping->citation->payment->receipt_number }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Amount Paid</strong>₱{{ number_format($clamping->citation->payment->amount, 2) }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Method</strong>{{ $clamping->citation->payment->payment_method->label() }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Paid At</strong>{{ $clamping->citation->payment->paid_at?->format('M d, Y h:i A') ?? '—' }}</div>
                        <div class="col-md-6"><strong class="text-muted small d-block">Cashier</strong>{{ $clamping->citation->payment->cashier->name }}</div>
                        @if ($clamping->citation->payment->reference_number)
                            <div class="col-md-6"><strong class="text-muted small d-block">Reference</strong>{{ $clamping->citation->payment->reference_number }}</div>
                        @endif
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
                            <small class="fw-semibold d-block">Clamped</small>
                            <small class="text-muted">{{ $clamping->clamped_at->format('M d, Y h:i A') }}</small>
                        </div>
                    </div>
                    @if ($clamping->status === App\Enums\ClampingStatus::Paid || $clamping->status === App\Enums\ClampingStatus::WaitingRelease || $clamping->status === App\Enums\ClampingStatus::Released)
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-1" style="width:10px;height:10px;"></span>
                            <div>
                                <small class="fw-semibold d-block">Paid</small>
                                <small class="text-muted">{{ $clamping->citation?->payment?->paid_at?->format('M d, Y h:i A') ?? '—' }}</small>
                            </div>
                        </div>
                    @endif
                    @if ($clamping->status === App\Enums\ClampingStatus::WaitingRelease || $clamping->status === App\Enums\ClampingStatus::Released)
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge bg-warning rounded-circle p-1" style="width:10px;height:10px;"></span>
                            <div>
                                <small class="fw-semibold d-block">Waiting to Release</small>
                            </div>
                        </div>
                    @endif
                    @if ($clamping->status === App\Enums\ClampingStatus::Released)
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge bg-success rounded-circle p-1" style="width:10px;height:10px;"></span>
                            <div>
                                <small class="fw-semibold d-block">Released</small>
                                <small class="text-muted">{{ $clamping->release?->released_at?->format('M d, Y h:i A') ?? '—' }}</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($clamping->release)
            <div class="card stat-card mb-4">
                <div class="card-header bg-white"><strong>Release Details</strong></div>
                <div class="card-body">
                    <div class="mb-2"><strong class="text-muted small d-block">Release #</strong>{{ $clamping->release->release_number }}</div>
                    <div class="mb-2"><strong class="text-muted small d-block">Released By</strong>{{ $clamping->release->releasedBy?->name ?? '—' }}</div>
                    <div class="mb-2"><strong class="text-muted small d-block">Released At</strong>{{ $clamping->release->released_at?->format('M d, Y h:i A') ?? '—' }}</div>
                    @if ($clamping->release->notes)
                        <div><strong class="text-muted small d-block">Notes</strong>{{ $clamping->release->notes }}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@can('markPaid', $clamping)
    <div class="modal fade" id="payModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('impounding.mark-paid', $clamping) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment — {{ $clamping->vehicle_plate }}</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Amount due: <strong>₱{{ number_format($clamping->citation?->penalty_amount ?? 0, 2) }}</strong></p>
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

@can('processRelease', $clamping)
    <div class="modal fade" id="releaseModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('impounding.process-release', $clamping) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Process Release — {{ $clamping->vehicle_plate }}</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Confirm release of vehicle <strong>{{ $clamping->vehicle_plate }}</strong>?</p>
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
