@extends('layouts.app')

@section('title', $citation->citation_number)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $citation->citation_number }}</h1>
        <p class="text-muted mb-0">{{ $citation->violationType->name }}</p>
    </div>
    <span class="badge {{ $citation->status->badgeClass() }} fs-6">{{ $citation->status->label() }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card stat-card mb-4">
            <div class="card-header bg-white"><strong>Citation Details</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong class="text-muted small d-block">Vehicle</strong>{{ $citation->vehicle_plate }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Driver</strong>{{ $citation->driver_name ?? '—' }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Penalty</strong>₱{{ number_format($citation->penalty_amount, 2) }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Due Date</strong>{{ $citation->due_date->format('M d, Y') }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Issued By</strong>{{ $citation->enforcer->name }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Issued At</strong>{{ $citation->issued_at->format('M d, Y h:i A') }}</div>
                    <div class="col-12"><strong class="text-muted small d-block">Location</strong>{{ $citation->location ?? '—' }}</div>
                    @if ($citation->notes)
                        <div class="col-12"><strong class="text-muted small d-block">Notes</strong>{{ $citation->notes }}</div>
                    @endif
                </div>
            </div>
        </div>
        @if ($citation->evidence->isNotEmpty())
            <div class="card stat-card">
                <div class="card-header bg-white"><strong>Evidence</strong></div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @foreach ($citation->evidence as $item)
                        <a href="{{ asset('storage/'.$item->file_path) }}" target="_blank">
                            <img src="{{ asset('storage/'.$item->file_path) }}" alt="Evidence" class="rounded border" style="height:120px">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card stat-card mb-3 text-center">
            <div class="card-body py-3">
                {!! $citation->getQRCode() !!}
                <div class="small text-muted mt-2">Scan to view citation details</div>
            </div>
        </div>

        @if ($citation->violationType->is_impoundable && $citation->clampingRecords->isEmpty() && auth()->user()->isRole(App\Enums\Role::SuperAdmin, App\Enums\Role::Administrator, App\Enums\Role::Enforcer))
            <div class="card stat-card mb-3 border-warning">
                <div class="card-body text-center">
                    <form method="POST" action="{{ route('citations.refer-impounding', $citation) }}">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100 fw-semibold">
                            <i class="bi bi-truck-front me-2"></i>Refer for Impounding
                        </button>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            This violation is eligible for impounding.
                        </small>
                    </form>
                </div>
            </div>
        @endif

        @if ($citation->payment)
            <div class="card stat-card mb-3">
                <div class="card-header bg-white"><strong>Payment</strong></div>
                <div class="card-body">
                    <p class="mb-1">Receipt: <a href="{{ route('payments.show', $citation->payment) }}">{{ $citation->payment->receipt_number }}</a></p>
                    <p class="mb-0 text-muted small">Paid {{ $citation->payment->paid_at->format('M d, Y') }}</p>
                </div>
            </div>
        @elseif ($citation->isPayable())
            @can('create', App\Models\Payment::class)
                <div class="card stat-card">
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('payments.create', ['citation_id' => $citation->id]) }}" class="btn btn-success w-100">Record Payment</a>
                        <form method="POST" action="{{ route('citations.checkout', $citation) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-credit-card me-2"></i>Pay Online
                            </button>
                        </form>
                    </div>
                </div>
            @elseif (auth()->user()->isRole(App\Enums\Role::VehicleOwner))
                <div class="card stat-card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('citations.checkout', $citation) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-credit-card me-2"></i>Pay Online via GCash/Maya
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
