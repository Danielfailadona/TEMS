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
            <div class="col-md-5">
                <label class="form-label">Plate Number</label>
                <input type="text" name="plate_number" class="form-control" placeholder="ABC-1234" value="{{ $plateNumber ?? '' }}">
            </div>
            <div class="col-md-5">
                <label class="form-label">Citation Number</label>
                <input type="text" name="citation_number" class="form-control" placeholder="CIT-20260614-XXXXXX" value="{{ $citationNumber ?? '' }}">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Look Up</button>
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
                <div class="col-md-4"><strong class="text-muted small d-block">Violation</strong>{{ $citation->violationType->name }}</div>
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
        </div>
    </div>
@elseif (request('plate_number') || request('citation_number'))
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
                            <div class="col-6"><strong class="text-muted small d-block">Violation</strong>{{ $citation->violationType->name }}</div>
                            <div class="col-6"><strong class="text-muted small d-block">Amount</strong>₱{{ number_format($citation->penalty_amount, 2) }}</div>
                            <div class="col-6"><strong class="text-muted small d-block">Issued</strong>{{ $citation->issued_at->format('M d, Y') }}</div>
                        </div>
                        @if ($citation->payment)
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted d-block">Paid {{ $citation->payment->paid_at?->format('M d, Y') ?? '—' }}</small>
                                <small class="text-muted">Receipt {{ $citation->payment->receipt_number }}</small>
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