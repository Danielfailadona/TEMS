@extends('layouts.app')

@section('title', 'Front Desk')

@section('content')
<div class="mb-4">
    <p class="text-muted mb-0">Walk-in assistance — look up citations and vehicle information.</p>
</div>

<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div class="col-md-4">
                <label class="form-label">Plate Number</label>
                <input type="text" name="plate_number" class="form-control" placeholder="ABC-1234" value="{{ $plateNumber ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Citation Number</label>
                <input type="text" name="citation_number" class="form-control" placeholder="CIT-20260614-XXXXXX" value="{{ $citationNumber ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Clamp Notice Number</label>
                <input type="text" name="notice_number" class="form-control" placeholder="CLP-20260614-XXXXXX" value="{{ $noticeNumber ?? '' }}">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Look Up</button>
                <a href="{{ route('frontdesk.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

@if (isset($citation))
    <div class="card stat-card mb-4">
        <div class="card-header bg-white"><strong>Citation Information</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong class="text-muted small d-block">Citation #</strong>{{ $citation->citation_number }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Vehicle</strong>{{ $citation->vehicle_plate }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Violation</strong>{{ $citation->violationLabel() }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Amount</strong>₱{{ number_format($citation->penalty_amount, 2) }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Status</strong><span class="badge {{ $citation->status->badgeClass() }}">{{ $citation->status->label() }}</span></div>
                <div class="col-md-4"><strong class="text-muted small d-block">Issued</strong>{{ $citation->issued_at->format('M d, Y') }}</div>
                @if ($citation->payment)
                    <div class="col-12"><strong class="text-muted small d-block">Payment</strong>
                        @if ($citation->payment->paid_at)
                            Paid {{ $citation->payment->paid_at->format('M d, Y') }} — Receipt {{ $citation->payment->receipt_number }}
                        @else
                            Payment pending — Receipt {{ $citation->payment->receipt_number }}
                        @endif
                    </div>
                @endif
            </div>

            {{-- APPEAL TILE --}}
            <div class="mt-3 pt-3 border-top">
                @if ($citation->appeal)
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <strong class="text-muted small d-block">Appeal {{ $citation->appeal->ticketNumber() }}</strong>
                            <span class="badge {{ $citation->appeal->status->badgeClass() }}">{{ $citation->appeal->status->label() }}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal" data-bs-target="#appealDetailModal">
                            <i class="bi bi-card-text me-1"></i>View Appeal
                        </button>
                    </div>
                @elseif ($citation->isPayable())
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <strong class="text-muted small d-block">Appeal</strong>
                            <span class="text-muted small">No appeal filed for this citation.</span>
                        </div>
                        @can('create', App\Models\Appeal::class)
                            <button type="button" class="btn btn-sm btn-primary"
                                data-bs-toggle="modal" data-bs-target="#fileAppealModal">
                                <i class="bi bi-plus-lg me-1"></i>File Appeal
                            </button>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($citation->appeal)
        {{-- Appeal detail modal (reason note + outcome) --}}
        <div class="modal fade" id="appealDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Appeal {{ $citation->appeal->ticketNumber() }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge {{ $citation->appeal->status->badgeClass() }}">{{ $citation->appeal->status->label() }}</span>
                            <small class="text-muted">{{ $citation->appeal->submitted_at?->format('M d, Y g:i A') }}</small>
                        </div>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Filed By</dt>
                            <dd class="col-sm-8">{{ $citation->appeal->submitterDisplay() }}</dd>
                            <dt class="col-sm-4">Reason</dt>
                            <dd class="col-sm-8">{{ $citation->appeal->reason }}</dd>
                            @if ($citation->appeal->description)
                                <dt class="col-sm-4">Details</dt>
                                <dd class="col-sm-8">{{ $citation->appeal->description }}</dd>
                            @endif
                            @if ($citation->appeal->reviewed_at)
                                <dt class="col-sm-4">Reviewed</dt>
                                <dd class="col-sm-8">{{ $citation->appeal->reviewed_at->format('M d, Y') }}
                                    @if ($citation->appeal->reviewer) by {{ $citation->appeal->reviewer->name }} @endif
                                </dd>
                            @endif
                            <dt class="col-sm-4">Notes</dt>
                            <dd class="col-sm-8">{{ $citation->appeal->decision_notes ?? '—' }}</dd>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('appeals.show', $citation->appeal) }}" class="btn btn-outline-secondary">Open Full Record</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($citation->isPayable())
        @can('create', App\Models\Appeal::class)
            {{-- File appeal modal --}}
            <div class="modal fade" id="fileAppealModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('appeals.store') }}">
                            @csrf
                            <input type="hidden" name="citation_id" value="{{ $citation->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title">File Appeal — {{ $citation->citation_number }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" class="form-control" required maxlength="255"
                                        placeholder="e.g. Ticket was issued in error">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Details / Note <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea name="description" rows="3" class="form-control" maxlength="2000"
                                        placeholder="Explain the citizen's grounds for the appeal..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>File Appeal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endif
@endif

@if (isset($clamping))
    <div class="card stat-card mb-4">
        <div class="card-header bg-white"><strong>Clamping Notice Information</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong class="text-muted small d-block">Notice #</strong>{{ $clamping->notice_number }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Vehicle</strong>{{ $clamping->vehicle_plate }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Officer</strong>{{ $clamping->officer->name ?? '—' }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Clamped At</strong>{{ $clamping->clamped_at->format('M d, Y') }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Status</strong><span class="badge {{ $clamping->status->badgeClass() }}">{{ $clamping->status->label() }}</span></div>
                <div class="col-md-4"><strong class="text-muted small d-block">Location</strong>{{ $clamping->location ?? '—' }}</div>
                @if ($clamping->paid_at)
                    <div class="col-md-4"><strong class="text-muted small d-block">Amount Paid</strong>₱{{ number_format($clamping->clamping_fee, 2) }}</div>
                    <div class="col-md-4"><strong class="text-muted small d-block">Paid At</strong>{{ $clamping->paid_at->format('M d, Y') }}</div>
                    <div class="col-md-4"><strong class="text-muted small d-block">Method</strong>{{ ucwords(str_replace('_', ' ', $clamping->payment_method ?? '—')) }}</div>
                @endif
                <div class="col-12">
                    <a href="{{ route('clamping.show', $clamping) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>Open Clamp Record
                    </a>
                    @if ($clamping->impoundingRecord)
                        <a href="{{ route('impounding.show', $clamping->impoundingRecord) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-truck me-1"></i>Impounding
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

@if (! isset($citation) && ! isset($clamping) && (request('plate_number') || request('citation_number') || request('notice_number')))
    <div class="alert alert-danger mb-4">No records found matching your search.</div>
@endif

@if (isset($citations))
<div class="mb-4">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('frontdesk.index') }}" class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
        @foreach (\App\Enums\CitationStatus::cases() as $s)
            <a href="{{ route('frontdesk.index', ['status' => $s->value]) }}" class="btn btn-sm {{ $status === $s->value ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $s->label() }}
            </a>
        @endforeach
    </div>

    <div class="row row-cols-1 row-cols-md-2 g-3">
        @forelse ($citations as $citation)
            <div class="col">
                <div class="card stat-card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <strong>{{ $citation->citation_number }}</strong>
                        <span class="badge {{ $citation->status->badgeClass() }}">{{ $citation->status->label() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6"><strong class="text-muted small d-block">Plate</strong>{{ $citation->vehicle_plate }}</div>
                            <div class="col-6"><strong class="text-muted small d-block">Violation</strong>{{ $citation->violationLabel() }}</div>
                            <div class="col-6"><strong class="text-muted small d-block">Amount</strong>₱{{ number_format($citation->penalty_amount, 2) }}</div>
                            <div class="col-6"><strong class="text-muted small d-block">Issued</strong>{{ $citation->issued_at->format('M d, Y') }}</div>
                        </div>
                        @if ($citation->payment)
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted d-block">Paid {{ $citation->payment->paid_at?->format('M d, Y') ?? '—' }}</small>
                                <small class="text-muted">Receipt {{ $citation->payment->receipt_number }}</small>
                            </div>
                        @endif
                        @if ($citation->appeal)
                            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted">Appeal {{ $citation->appeal->ticketNumber() }}</small>
                                <span class="badge {{ $citation->appeal->status->badgeClass() }}">{{ $citation->appeal->status->label() }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-4">No citations found.</div>
        @endforelse
    </div>

    @if ($citations->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $citations->withQueryString()->links() }}
        </div>
    @endif
@endif

@endsection