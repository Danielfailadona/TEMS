@extends('layouts.app')

@section('title', 'Appeal Details')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const appealPenalty = {{ $appeal->citation?->penalty_amount ?? 0 }};
        const discountPercent = {{ \App\Models\Appeal::APPROVED_DISCOUNT_PERCENT }};
        const discountedAmount = Math.round(appealPenalty * (1 - discountPercent / 100) * 100) / 100;

        // Update modal preview amounts
        const acceptAmountEl = document.getElementById('acceptPreviewAmount');
        const acceptOriginalEl = document.getElementById('acceptPreviewOriginal');
        if (acceptAmountEl) acceptAmountEl.textContent = '₱' + discountedAmount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (acceptOriginalEl) acceptOriginalEl.textContent = '₱' + appealPenalty.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    });
</script>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">Appeal #{{ $appeal->id }}</h1>
        <p class="text-muted mb-0">Appeal Details</p>
    </div>
    <span class="badge {{ $appeal->status->badgeClass() }} fs-6">{{ $appeal->status->label() }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Appeal Summary</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Citation</dt>
                    <dd class="col-sm-8">{{ $appeal->citation->citation_number ?? '—' }} ({{ $appeal->citation->vehicle_plate ?? '—' }})</dd>
                    <dt class="col-sm-4">Submitted By</dt>
                    <dd class="col-sm-8">
                        {{ $appeal->submitterDisplay() }}
                        @if ($appeal->isGuestSubmission() && $appeal->guest_contact)
                            <br><small class="text-muted">{{ $appeal->guest_contact }}</small>
                        @endif
                    </dd>
                    <dt class="col-sm-4">Reason</dt>
                    <dd class="col-sm-8">{{ $appeal->reason }}</dd>
                    <dt class="col-sm-4">Details</dt>
                    <dd class="col-sm-8">{{ $appeal->description ?? '—' }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8"><span class="badge {{ $appeal->status->badgeClass() }}">{{ $appeal->status->label() }}</span></dd>
                    @php
                        $original = $appeal->original_amount ?? ($appeal->citation?->penalty_amount ?? 0);
                        $final = $appeal->adjusted_amount ?? ($appeal->citation?->penalty_amount ?? $original);
                    @endphp
                    <dt class="col-sm-4">Original Amount</dt>
                    <dd class="col-sm-8">₱{{ number_format($original, 2) }}</dd>
                    @if ($appeal->status->value === 'approved')
                        <dt class="col-sm-4">Final Amount</dt>
                        <dd class="col-sm-8">₱{{ number_format($final, 2) }}</dd>
                    @endif
                    <dt class="col-sm-4">Decision Notes</dt>
                    <dd class="col-sm-8">{{ $appeal->decision_notes ?? '—' }}</dd>
                </dl>
            </div>

            @if (auth()->user()->isRole(App\Enums\Role::SuperAdmin, App\Enums\Role::Administrator) && in_array($appeal->status->value, ['submitted', 'under_review']))
                <div class="card-footer bg-white border-top">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('appeals.edit', $appeal) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i>Edit Review
                        </a>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#acceptModal">
                            <i class="bi bi-check-circle me-1"></i>Accept (15% discount)
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle me-1"></i>Reject
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if (auth()->user()->isRole(App\Enums\Role::SuperAdmin, App\Enums\Role::Administrator) && in_array($appeal->status->value, ['submitted', 'under_review']))
    <!-- Accept Modal -->
    <div class="modal fade" id="acceptModal" tabindex="-1" aria-labelledby="acceptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="acceptModalLabel"><i class="bi bi-check-circle me-2"></i>Confirm Accept</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Approve this appeal and apply the standard <strong>{{ \App\Models\Appeal::APPROVED_DISCOUNT_PERCENT }}% discount</strong> to the citation penalty?</p>
                    <div class="alert alert-info mb-0">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="small text-muted">Original Penalty</div>
                                <div class="fw-bold" id="acceptPreviewOriginal">₱0.00</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">After {{ \App\Models\Appeal::APPROVED_DISCOUNT_PERCENT }}% Discount</div>
                                <div class="fw-bold text-success" id="acceptPreviewAmount">₱0.00</div>
                            </div>
                        </div>
                    </div>
                    <p class="small text-muted mt-2">The citation must be unpaid for the discount to apply. If already paid, the appeal will be marked approved but the penalty remains unchanged.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('appeals.update', $appeal) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn btn-success">Confirm Accept</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel"><i class="bi bi-x-circle me-2"></i>Confirm Reject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Reject this appeal? The citation penalty will remain unchanged.</p>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This action cannot be undone. The appellant will be notified.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('appeals.update', $appeal) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn btn-danger">Confirm Reject</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
