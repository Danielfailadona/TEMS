@extends('layouts.app')

@section('title', 'Record Payment')

@section('content')

<div class="card stat-card mb-4"><div class="card-body">
    <form method="GET" action="{{ route('payments.create') }}" class="row g-2">
        <div class="col-md-6">
            <input type="text" name="citation_number" class="form-control" placeholder="Enter citation number..." value="{{ request('citation_number') }}">
        </div>
        <div class="col-auto"><button class="btn btn-outline-secondary">Look Up</button></div>
    </form>
</div></div>

@if ($citation)
    @if ($citation->payment && $citation->payment->paid_at)
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
@elseif ($suggestions->isNotEmpty())
    <div class="card stat-card">
        <div class="card-header bg-white">
            <strong>
                @if (request('citation_number'))
                    <i class="bi bi-search me-1"></i>No match for "{{ request('citation_number') }}" — closest citations:
                @else
                    <i class="bi bi-inbox me-1"></i>Recent Unpaid Citations
                @endif
            </strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Citation #</th>
                            <th>Plate</th>
                            <th>Violation</th>
                            <th>Amount Due</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suggestions as $sugg)
                            <tr>
                                <td class="fw-semibold">{{ $sugg->citation_number }}</td>
                                <td>{{ $sugg->vehicle_plate }}</td>
                                <td>{{ $sugg->violationType->name }}</td>
                                <td>₱{{ number_format($sugg->penalty_amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $sugg->status->badgeClass() }}">{{ $sugg->status->label() }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('payments.create', ['citation_id' => $sugg->id]) }}" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-cash-stack me-1"></i>Select
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@elseif (request('citation_number'))
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-circle me-1"></i>No citation found matching "{{ request('citation_number') }}".
    </div>
@else
    <div class="alert alert-warning mb-0">
        <i class="bi bi-info-circle me-1"></i>No unpaid citations to display. Enter a citation number or vehicle plate above, or issue a citation first.
    </div>
@endif
@endsection
