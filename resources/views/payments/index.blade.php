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

{{-- Receipts / Awaiting Payment toggle --}}
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $viewMode === 'receipts' ? 'active' : '' }}" href="{{ route('payments.index', array_filter(request()->only(['search','payment_method','category','date_from','date_to','online']))) }}">
            <i class="bi bi-receipt me-1"></i>Receipts
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $viewMode === 'payables' ? 'active' : '' }}" href="{{ route('payments.index', ['view' => 'payables']) }}">
            <i class="bi bi-hourglass-split me-1"></i>Awaiting Payment
        </a>
    </li>
</ul>

@if ($viewMode === 'payables')
    {{-- Category pills with outstanding counts --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('payments.index', ['view' => 'payables', 'category' => 'citation']) }}"
           class="btn btn-sm {{ $payableCategory === 'citation' ? 'btn-primary' : 'btn-outline-secondary' }}">
            Citations <span class="badge bg-light text-dark ms-1">{{ $payableCounts['citation'] }}</span>
        </a>
        <a href="{{ route('payments.index', ['view' => 'payables', 'category' => 'clamping']) }}"
           class="btn btn-sm {{ $payableCategory === 'clamping' ? 'btn-primary' : 'btn-outline-secondary' }}">
            Clamping <span class="badge bg-light text-dark ms-1">{{ $payableCounts['clamping'] }}</span>
        </a>
        <a href="{{ route('payments.index', ['view' => 'payables', 'category' => 'impounding']) }}"
           class="btn btn-sm {{ $payableCategory === 'impounding' ? 'btn-primary' : 'btn-outline-secondary' }}">
            Impounding <span class="badge bg-light text-dark ms-1">{{ $payableCounts['impounding'] }}</span>
        </a>
    </div>

    {{-- Search --}}
    <div class="card stat-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="view" value="payables">
                <input type="hidden" name="category" value="{{ $payableCategory }}">
                <div class="col-12 col-md-9">
                    <label class="form-label visually-hidden">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" class="form-control" placeholder="Search notice #, plate, {{ $payableCategory === 'citation' ? 'or driver' : 'or officer' }}" value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-12 col-md-auto d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                    @if (request('search'))
                        <a href="{{ route('payments.index', ['view' => 'payables', 'category' => $payableCategory]) }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Awaiting ticket cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        @forelse ($payables as $item)
            <div class="col">
                <div class="card stat-card payment-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                        <div class="min-width-0">
                            <strong class="small d-block text-truncate">
                                {{ $item->citation_number ?? $item->notice_number }}
                            </strong>
                            <span class="payment-status-badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                        </div>
                        <span class="payment-status-badge bg-primary">
                            {{ $payableCategory === 'citation' ? 'Citation' : 'Notice' }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 small">
                            <div class="col-6"><strong class="text-muted d-block">Vehicle</strong>{{ $item->vehicle_plate ?: '—' }}</div>
                            <div class="col-6"><strong class="text-muted d-block">{{ $payableCategory === 'citation' ? 'Driver' : 'Officer' }}</strong>
                                {{ $payableCategory === 'citation' ? ($item->driver_name ?: '—') : ($item->officer?->name ?: '—') }}
                            </div>
                            <div class="col-12">
                                <strong class="text-muted d-block">{{ $payableCategory === 'citation' ? 'Penalty' : 'Amount Due' }}</strong>
                                @php
                                    $due = match ($payableCategory) {
                                        'clamping' => $item->clamping_fee ?? $item->citation?->penalty_amount ?? 0,
                                        'impounding' => $item->getTotalFees(),
                                        default => $item->penalty_amount,
                                    };
                                @endphp
                                ₱{{ number_format((float) $due, 2) }}
                            </div>
                            <div class="col-12"><strong class="text-muted d-block">{{ $payableCategory === 'citation' ? 'Issued' : 'Recorded' }}</strong>
                                {{ ($item->issued_at ?? $item->clamped_at ?? $item->impounded_at)?->format('M d, Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center gap-2">
                        @php
                            $detailRoute = match ($payableCategory) {
                                'clamping' => route('clamping.show', $item),
                                'impounding' => route('impounding.show', $item),
                                default => route('citations.show', $item),
                            };
                            $payRoute = route('payments.create', array_filter([
                                'category' => $payableCategory,
                                $payableCategory === 'citation' ? 'citation_id' : $payableCategory.'_id' => $item->id,
                            ]));
                        @endphp
                        <a href="{{ $detailRoute }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                        @can('create', App\Models\Payment::class)
                            <a href="{{ $payRoute }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-cash-coin me-1"></i>Collect
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card stat-card text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-check2-circle fs-1 text-success mb-3 d-block"></i>
                        <h5 class="mb-2">Nothing awaiting payment</h5>
                        <p class="text-muted small mb-0">
                            @if (request('search'))
                                Try adjusting your search.
                            @else
                                All {{ ucfirst($payableCategory) }} tickets have been paid.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if ($payables->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $payables->links() }}
        </div>
    @endif
@else
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

            {{-- Category Filter --}}
            <div class="col-12 col-md-3">
                <label class="form-label visually-hidden">Category</label>
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <option value="citation" {{ request('category') === 'citation' ? 'selected' : '' }}>Citation</option>
                    <option value="clamping" {{ request('category') === 'clamping' ? 'selected' : '' }}>Clamping</option>
                    <option value="impounding" {{ request('category') === 'impounding' ? 'selected' : '' }}>Impounding</option>
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
            @if(request()->hasAny(['search', 'category', 'payment_method', 'date_from', 'date_to', 'online']))
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
                    <div class="min-width-0">
                        <strong class="small d-block text-truncate">{{ $payment->receipt_number }}</strong>
                        <span class="payment-status-badge {{ $payment->getStatusBadgeClass() }}">
                            {{ $payment->getStatusLabel() }}
                        </span>
                    </div>
                    <span class="payment-status-badge bg-primary">{{ $payment->categoryLabel() }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-2 small">
                        <div class="col-12"><strong class="text-muted d-block">{{ $payment->category() === 'citation' ? 'Citation' : 'Notice' }}</strong>{{ $payment->payableNoticeNumber() }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Vehicle</strong>{{ $payment->payableVehicle() }}</div>
                        <div class="col-6"><strong class="text-muted d-block">{{ $payment->category() === 'citation' ? 'Driver' : 'Officer' }}</strong>{{ $payment->payablePerson() }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Amount</strong>₱{{ number_format($payment->amount, 2) }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Method</strong>{{ $payment->isOnlinePayment() ? ucfirst($payment->online_payment_method ?? 'Online') : $payment->payment_method->label() }}</div>
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
@endif
@endsection