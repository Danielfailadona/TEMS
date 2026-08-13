@extends('layouts.app')

@section('title', 'Front Desk')

@section('content')
<div class="mb-4">
    <p class="text-muted mb-0">Walk-in assistance — look up citations and vehicle information.</p>
</div>

<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
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
    <div class="card stat-card">
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
                    <div class="col-12"><strong class="text-muted small d-block">Payment</strong>Paid {{ $citation->payment->paid_at->format('M d, Y') }} — Receipt {{ $citation->payment->receipt_number }}</div>
                @endif
            </div>
        </div>
    </div>
@elseif (isset($citation))
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>Vehicle Information</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong class="text-muted small d-block">Plate #</strong>{{ $citation->vehicle_plate }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Make/Model</strong>{{ $citation->vehicle_make }} {{ $citation->vehicle_model }}</div>
                <div class="col-md-4"><strong class="text-muted small d-block">Driver</strong>{{ $citation->driver_name ?? '—' }}</div>
            </div>
            <div class="mt-3 text-muted small">No citations found for this vehicle.</div>
        </div>
    </div>
@elseif (request('plate_number') || request('citation_number'))
    <div class="alert alert-danger">No records found matching your search.</div>
@endif
@endsection
