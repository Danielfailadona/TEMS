@extends('layouts.app')

@section('title', 'Payments')

@push('styles')
<style>
    .payment-card {
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .payment-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .payment-status-badge {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 0.35rem;
    }
    .filter-panel {
        transition: all 0.3s ease;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Payments</h1>
        <p class="text-muted mb-0">Manage and view all payment records</p>
    </div>
    <div class="d-flex gap-2">
        @can('create', App\Models\Payment::class)
            <a href="{{ route('payments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Record Payment
            </a>
        @endcan
    </div>
</div>

{{-- Search & Filter Bar --}}
<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" id="filterForm" class="row g-3">
            {{-- Search Input --}}
            <div class="col-12 col-md-6">
                <label class="form-label visually-hidden">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search receipt #, citation #, plate, or driver" value="{{ request('search') }}">
                    @if(request('search'))
                        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary" type="button">Clear</a>
                    @endif
                </div>
            </div>

            {{-- Payment Method Filter --}}
            <div class="col-12 col-md-3">
                <label class="form-label visually-hidden">Payment Method</label>
                <select name="payment_method" class="form-select" onchange="this.form.submit()">
                    <option value="">All Methods</option>
                    @foreach (App\Enums\PaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}" {{ request('payment_method') === $method->value ? 'selected' : '' }}>
                            {{ $method->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div class="col-12 col-md-3">
                <label class="form-label visually-hidden">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From" onchange="this.form.submit()">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label visually-hidden">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To" onchange="this.form.submit()">
            </div>
            <div class="col-12 col-md-auto d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i> Apply
                </button>
            </div>

            {{-- Online Only Checkbox --}}
            <div class="col-12 col-md-auto d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="online" id="onlineFilter" value="1" {{ request('online') ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label small" for="onlineFilter">
                        <i class="bi bi-globe me-1"></i>Online only
                    </label>
                </div>
            </div>

            {{-- Reset Button --}}
            @if(request()->hasAny(['search', 'payment_method', 'date_from', 'date_to', 'online']))
            <div class="col-12 col-md-auto d-flex align-items-end">
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </a>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- Payments Card Grid --}}
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
    @forelse ($payments as $payment)
        <div class="col">
            <div class="card stat-card payment-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                    <strong>{{ $payment->receipt_number }}</strong>
                    <span class="payment-status-badge {{ $payment->getStatusBadgeClass() }}">
                        {{ $payment->getStatusLabel() }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-2 small">
                        <div class="col-6"><strong class="text-muted d-block">Citation</strong>{{ $payment->citation->citation_number ?? '—' }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Vehicle</strong>{{ $payment->citation->vehicle_plate ?? '—' }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Driver</strong>{{ $payment->citation->driver_name ?? '—' }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Plate</strong>{{ $payment->citation->vehicle_plate ?? '—' }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Amount</strong>₱{{ number_format($payment->amount, 2) }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Method</strong>{{ $payment->payment_method->label() }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Type</strong>{{ $payment->isOnlinePayment() ? 'Online' : 'Manual' }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Paid</strong>{{ $payment->paid_at?->format('M d, Y') ?? '—' }}</div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    @can('update', $payment)
                        <a href="{{ route('payments.edit', $payment) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card stat-card text-center py-5">
                <div class="card-body">
                    <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted mb-2">No payments found.</h5>
                    <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
@if ($payments->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $payments->withQueryString()->links() }}
    </div>
@endif
@endsection