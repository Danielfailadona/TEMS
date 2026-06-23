@extends('layouts.app')

@section('title', 'Record Payment')

@section('content')
<h1 class="h3 mb-4">Record Payment</h1>

<div class="card stat-card mb-4"><div class="card-body">
    <form method="GET" action="{{ route('payments.create') }}" class="row g-2">
        <div class="col-md-6">
            <input type="text" name="citation_number" class="form-control" placeholder="Enter citation number..." value="{{ request('citation_number') }}">
        </div>
        <div class="col-auto"><button class="btn btn-outline-secondary">Look Up</button></div>
    </form>
</div></div>

@if ($citation)
    @if ($citation->payment)
        <div class="alert alert-info">Citation {{ $citation->citation_number }} has already been paid.</div>
    @elseif (!$citation->isPayable())
        <div class="alert alert-warning">Citation {{ $citation->citation_number }} is not eligible for payment.</div>
    @else
        <div class="card stat-card"><div class="card-body">
            <h5 class="mb-3">Citation: {{ $citation->citation_number }}</h5>
            <p class="mb-1"><strong>Violation:</strong> {{ $citation->violationType->name }}</p>
            <p class="mb-1"><strong>Vehicle:</strong> {{ $citation->vehicle_plate }}</p>
            <p class="mb-4"><strong>Amount Due:</strong> ₱{{ number_format($citation->penalty_amount, 2) }}</p>

            <form method="POST" action="{{ route('payments.store') }}">@csrf
                <input type="hidden" name="citation_id" value="{{ $citation->id }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method->value }}">{{ $method->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-3">Confirm Payment — ₱{{ number_format($citation->penalty_amount, 2) }}</button>
            </form>
        </div></div>
    @endif
@elseif (request('citation_number'))
    <div class="alert alert-danger">Citation not found.</div>
@endif
@endsection
