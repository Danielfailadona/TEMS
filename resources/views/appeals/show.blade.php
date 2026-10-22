@extends('layouts.app')

@section('title', 'Appeal Details')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const appealPenalty = {{ $appeal->citation?->penalty_amount ?? 0 }};
        const discountPercent = {{ \App\Models\Appeal::APPROVED_DISCOUNT_PERCENT }};
        const discountedAmount = Math.round(appealPenalty * (1 - discountPercent / 100) * 100) / 100;

        const acceptAmountEl = document.getElementById('acceptPreviewAmount');
        const acceptOriginalEl = document.getElementById('acceptPreviewOriginal');
        if (acceptAmountEl) acceptAmountEl.textContent = '₱' + discountedAmount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (acceptOriginalEl) acceptOriginalEl.textContent = '₱' + appealPenalty.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        document.querySelectorAll('.evidence-thumb').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('evidenceModalBody').innerHTML =
                    '<img src="' + el.dataset.src + '" alt="Evidence" class="img-fluid" style="max-height: 80vh;">';
                new bootstrap.Modal(document.getElementById('evidenceModal')).show();
            });
        });
    });
</script>
@endpush

@section('content')
@php
    $citation = $appeal->citation;
    $canReview = auth()->user()->isRole(App\Enums\Role::SuperAdmin, App\Enums\Role::Administrator)
        && in_array($appeal->status->value, ['submitted', 'under_review']);
@endphp

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">Appeal {{ $appeal->ticketNumber() }}</h1>
        <p class="text-muted mb-0">Filed {{ $appeal->submitted_at?->format('M d, Y g:i A') }}</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge {{ $appeal->status->badgeClass() }} fs-6">{{ $appeal->status->label() }}</span>
        <a href="{{ route('appeals.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- LEFT: APPEAL SUMMARY CONTAINER --}}
    <div class="col-lg-5">
        <div class="card stat-card">
            <div class="card-header bg-white border-bottom">
                <h5 class="card-title mb-0"><i class="bi bi-journal-text me-2"></i>Appeal Summary</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Ticket #</dt>
                    <dd class="col-sm-7"><span class="badge tag-gray">{{ $appeal->ticketNumber() }}</span></dd>

                    <dt class="col-sm-5">Citation</dt>
                    <dd class="col-sm-7">{{ $citation->citation_number ?? '—' }} ({{ $citation->vehicle_plate ?? '—' }})</dd>

                    <dt class="col-sm-5">Filed By</dt>
                    <dd class="col-sm-7">{{ $appeal->submitterDisplay() }}</dd>

                    <dt class="col-sm-5">Reason</dt>
                    <dd class="col-sm-7">{{ $appeal->reason }}</dd>

                    <dt class="col-sm-5">Details</dt>
                    <dd class="col-sm-7">{{ $appeal->description ?? '—' }}</dd>

                    <dt class="col-sm-5">Status</dt>
                    <dd class="col-sm-7"><span class="badge {{ $appeal->status->badgeClass() }}">{{ $appeal->status->label() }}</span></dd>

                    @php
                        $original = $appeal->original_amount ?? ($citation?->penalty_amount ?? 0);
                        $final = $appeal->adjusted_amount ?? ($citation?->penalty_amount ?? $original);
                    @endphp
                    <dt class="col-sm-5">Original Amount</dt>
                    <dd class="col-sm-7">₱{{ number_format($original, 2) }}</dd>
                    @if ($appeal->status->value === 'approved')
                        <dt class="col-sm-5">Final Amount</dt>
                        <dd class="col-sm-7">₱{{ number_format($final, 2) }}</dd>
                    @endif

                    @if ($appeal->reviewer)
                        <dt class="col-sm-5">Reviewed By</dt>
                        <dd class="col-sm-7">{{ $appeal->reviewer->name }}</dd>
                    @endif
                    @if ($appeal->reviewed_at)
                        <dt class="col-sm-5">Reviewed At</dt>
                        <dd class="col-sm-7">{{ $appeal->reviewed_at->format('M d, Y g:i A') }}</dd>
                    @endif

                    <dt class="col-sm-5">Decision Notes</dt>
                    <dd class="col-sm-7">{{ $appeal->decision_notes ?? '—' }}</dd>
                </dl>
            </div>

            @if ($canReview)
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

        @if ($appeal->status->value === 'approved')
            <div class="alert alert-success mt-3 mb-0">
                <i class="bi bi-check-circle me-2"></i>Appeal approved. The citation penalty was adjusted to
                <strong>₱{{ number_format($final, 2) }}</strong>.
            </div>
        @elseif ($appeal->status->value === 'rejected')
            <div class="alert alert-danger mt-3 mb-0">
                <i class="bi bi-x-circle me-2"></i>Appeal rejected. The citation penalty remains unchanged.
            </div>
        @endif
    </div>

    {{-- RIGHT: FULL CITATION TICKET --}}
    <div class="col-lg-7">
        <div class="card stat-card">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i>Citation Ticket</h5>
                @if ($citation)
                    <span class="badge {{ $citation->status->badgeClass() }}">{{ $citation->status->label() }}</span>
                @endif
            </div>
            <div class="card-body">
                @if (! $citation)
                    <p class="text-muted mb-0">The citation linked to this appeal is no longer available.</p>
                @else
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Citation Number</div>
                            <h4 class="mb-0 fw-bold">#{{ $citation->citation_number }}</h4>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small text-uppercase fw-semibold">Penalty</div>
                            <div class="fs-4 fw-bold text-success">₱{{ number_format($citation->penalty_amount, 2) }}</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Violation</small>
                            <strong>{{ $citation->violationLabel() }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Plate Number</small>
                            <strong>{{ $citation->vehicle_plate }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Vehicle</small>
                            <strong>{{ trim(($citation->vehicle_make ?? '').' '.($citation->vehicle_model ?? '')) ?: '—' }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Driver</small>
                            <strong>{{ $citation->driver_name ?? '—' }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Date Issued</small>
                            <strong>{{ $citation->issued_at?->format('M d, Y') }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Due Date</small>
                            <strong class="{{ $citation->due_date && $citation->due_date->isPast() && ! $citation->isPaid() ? 'text-danger' : '' }}">
                                {{ $citation->due_date?->format('M d, Y') }}
                            </strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Issued By</small>
                            <strong>{{ $citation->enforcer->name ?? '—' }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Location</small>
                            <strong>{{ $citation->location ?? '—' }}</strong>
                        </div>
                    </div>

                    @if ($citation->evidence->isNotEmpty())
                        <hr>
                        <div class="text-muted small text-uppercase fw-semibold mb-2">
                            Evidence ({{ $citation->evidence->count() }})
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($citation->evidence as $item)
                                @if (\App\Services\SupabaseStorage::has($item->file_path))
                                    <a href="#" class="evidence-thumb" data-src="{{ \App\Services\SupabaseStorage::publicUrl($item->file_path) }}">
                                        <img src="{{ \App\Services\SupabaseStorage::publicUrl($item->file_path) }}"
                                             alt="Evidence" class="rounded border"
                                             style="width: 96px; height: 96px; object-fit: cover; cursor: zoom-in;">
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if ($citation->payment)
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Payment</small>
                                <strong>Receipt #{{ $citation->payment->receipt_number }}</strong>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">{{ $citation->payment->paid_at ? 'Paid ' . $citation->payment->paid_at->format('M d, Y') : 'Pending' }}</small>
                                <strong class="text-success">₱{{ number_format($citation->payment->amount ?? $citation->penalty_amount, 2) }}</strong>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@if ($canReview)
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
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="rejectNotes">Reason / Notes <span class="text-danger">*</span></label>
                        <textarea name="decision_notes" id="rejectNotes" form="rejectForm" rows="3" class="form-control" required
                            placeholder="Explain why this appeal is being rejected..."></textarea>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This action cannot be undone. The appellant will be notified.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('appeals.update', $appeal) }}" id="rejectForm" class="d-inline">
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

@if ($citation && $citation->evidence->isNotEmpty())
    <div class="modal fade" id="evidenceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Evidence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="evidenceModalBody"></div>
            </div>
        </div>
    </div>
@endif
@endsection
