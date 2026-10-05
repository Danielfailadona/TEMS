@extends('layouts.app')

@section('title', 'Record Payment')

@section('content')

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Record Payment</h1>
        <p class="text-muted mb-0">Collect payment for citations, clamping, and impounding tickets</p>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-hourglass-split me-1"></i>Awaiting Payment
    </a>
</div>

<div class="card stat-card mb-4"><div class="card-body">
    <form method="GET" action="{{ route('payments.create') }}" class="row g-2">
        <div class="col-md-3">
            <label class="form-label visually-hidden">Category</label>
            <select name="category" class="form-select" onchange="this.form.submit()">
                <option value="" {{ request('category') === '' ? 'selected' : '' }}>All Categories</option>
                <option value="citation" {{ request('category') === 'citation' ? 'selected' : '' }}>Citation</option>
                <option value="clamping" {{ request('category') === 'clamping' ? 'selected' : '' }}>Clamping</option>
                <option value="impounding" {{ request('category') === 'impounding' ? 'selected' : '' }}>Impounding</option>
            </select>
        </div>
        <div class="col-md-6">
            <input type="text" name="lookup" class="form-control" placeholder="Enter citation/notice number or vehicle plate..." value="{{ request('lookup') }}">
        </div>
        <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search me-1"></i>Look Up</button></div>
    </form>
</div></div>

@if ($record)
    @php
        $alreadyPaid = $category === 'citation'
            ? ($record->payment && $record->payment->paid_at)
            : $record->payments()->whereNotNull('paid_at')->exists();

        $released = $category !== 'citation' && $record->status->value === 'released';
        $eligible = $category === 'citation' ? $record->isPayable() : !$alreadyPaid && !$released;

        $amountDue = match ($category) {
            'clamping' => $record->clamping_fee ?? $record->citation?->penalty_amount ?? 0,
            'impounding' => $record->getTotalFees(),
            default => $record->penalty_amount,
        };

        $reference = $category === 'citation' ? $record->citation_number : $record->notice_number;
        $idField = $category === 'citation' ? 'citation_id' : $category.'_id';
    @endphp

    @if ($alreadyPaid)
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>{{ $reference }} has already been paid.
        </div>
    @elseif ($released)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-circle me-1"></i>{{ $reference }} has already been released and cannot take a payment.
        </div>
    @elseif (! $eligible)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-circle me-1"></i>{{ $reference }} is not eligible for payment.
        </div>
    @else
        <div class="card stat-card"><div class="card-body">
            <h5 class="mb-3">
                {{ $category === 'citation' ? 'Citation' : 'Notice' }}: {{ $reference }}
                <span class="badge {{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span>
            </h5>

            <div class="row g-2 small mb-4">
                @if ($category === 'citation')
                    <div class="col-md-4"><strong class="text-muted d-block">Violation</strong>{{ $record->violationType->name }}</div>
                    <div class="col-md-4"><strong class="text-muted d-block">Vehicle</strong>{{ $record->vehicle_plate ?: '—' }}</div>
                    <div class="col-md-4"><strong class="text-muted d-block">Driver</strong>{{ $record->driver_name ?: '—' }}</div>
                @else
                    <div class="col-md-4"><strong class="text-muted d-block">Vehicle</strong>{{ $record->vehicle_plate ?: '—' }}</div>
                    <div class="col-md-4"><strong class="text-muted d-block">Officer</strong>{{ $record->officer?->name ?: '—' }}</div>
                    <div class="col-md-4">
                        <strong class="text-muted d-block">{{ $category === 'clamping' ? 'Clamped' : 'Impounded' }}</strong>
                        {{ ($record->clamped_at ?? $record->impounded_at)?->format('M d, Y') ?? '—' }}
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ route('payments.store') }}">@csrf
                <input type="hidden" name="category" value="{{ $category }}">
                <input type="hidden" name="{{ $idField }}" value="{{ $record->id }}">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            @if ($category === 'citation')
                                Amount Due
                            @else
                                Amount Received <span class="text-muted small">(editable)</span>
                            @endif
                        </label>
                        @if ($category === 'citation')
                            <input type="text" class="form-control" value="₱{{ number_format((float) $amountDue, 2) }}" disabled>
                        @else
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" max="9999999" name="amount" class="form-control"
                                       value="{{ number_format((float) $amountDue, 2, '.', '') }}" required>
                            </div>
                        @endif
                    </div>
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
                        <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i>Confirm Payment
                    </button>
                    <a href="{{ route('payments.index') }}" class="btn btn-link">Cancel</a>
                </div>
            </form>
        </div></div>
    @endif
@elseif ($suggestions->isNotEmpty())
    <div class="card stat-card">
        <div class="card-header bg-white">
            <strong>
                @if (request('lookup'))
                    <i class="bi bi-search me-1"></i>No match for "{{ request('lookup') }}" — closest matches:
                @else
                    <i class="bi bi-inbox me-1"></i>Awaiting Payment
                @endif
            </strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Category</th>
                            <th>{{ request('category') === 'citation' ? 'Citation #' : 'Notice #' }}</th>
                            <th>Plate</th>
                            <th>Violation / Officer</th>
                            <th>Amount Due</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suggestions as $sugg)
                            @php
                                $suggCategory = $sugg['category'] ?? $category;
                                $due = match ($suggCategory) {
                                    'clamping' => $sugg->clamping_fee ?? $sugg->citation?->penalty_amount ?? 0,
                                    'impounding' => $sugg->getTotalFees(),
                                    default => $sugg->penalty_amount,
                                };
                                $selectUrl = route('payments.create', array_filter([
                                    'category' => $suggCategory,
                                    $suggCategory === 'citation' ? 'citation_id' : $suggCategory.'_id' => $sugg->id,
                                ]));
                            @endphp
                            <tr>
                                <td><span class="badge {{ $suggCategory === 'citation' ? 'bg-primary' : ($suggCategory === 'clamping' ? 'bg-warning text-dark' : 'bg-info') }}">{{ ucfirst($suggCategory) }}</span></td>
                                <td class="fw-semibold">{{ $suggCategory === 'citation' ? $sugg->citation_number : $sugg->notice_number }}</td>
                                <td>{{ $sugg->vehicle_plate ?: '—' }}</td>
                                <td>{{ $suggCategory === 'citation' ? $sugg->violationType->name : ($sugg->officer?->name ?: '—') }}</td>
                                <td>₱{{ number_format((float) $due, 2) }}</td>
                                <td><span class="badge {{ $sugg->status->badgeClass() }}">{{ $sugg->status->label() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ $selectUrl }}" class="btn btn-sm btn-outline-success">
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
@elseif (request('lookup'))
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-circle me-1"></i>No record found matching "{{ request('lookup') }}".
    </div>
@else
    <div class="alert alert-warning mb-0">
        <i class="bi bi-info-circle me-1"></i>No tickets are awaiting payment right now.
    </div>
@endif
@endsection