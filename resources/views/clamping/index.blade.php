@extends('layouts.app')

@section('title', 'Vehicle Clamping')

@section('content')
@if (isset($overdueCitations) && $overdueCitations->isNotEmpty())
    <div class="card stat-card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center"
             role="button" data-bs-toggle="collapse" data-bs-target="#eligibleVehicles" aria-expanded="false">
            <strong>Eligible Vehicles
                <span class="badge bg-danger ms-1">{{ $overdueCitations->count() }}</span>
            </strong>
            <small class="text-muted"><i class="bi bi-chevron-down"></i></small>
        </div>
        <div class="collapse show" id="eligibleVehicles">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Plate #</th><th>Driver</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @foreach ($overdueCitations as $citation)
                            <tr>
                                <td>{{ $citation->vehicle_plate }}</td>
                                <td>{{ $citation->driver_name }}</td>
                                <td class="text-end">
                                    <a href="{{ route('clamping.create', ['vehicle_plate' => $citation->vehicle_plate]) }}" class="btn btn-sm btn-danger">Clamp</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- Search & Filter Bar --}}
<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-5">
                <label class="form-label visually-hidden">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search notice #, plate, or officer..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label visually-hidden">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach (App\Enums\ClampingStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label visually-hidden">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="Clamped from">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label visually-hidden">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" title="Clamped to">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Apply
                </button>
                @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('clamping.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Clamping Records Grid --}}
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
    @forelse ($records as $record)
        <div class="col">
            <div class="card stat-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                    <div class="min-width-0">
                        <strong class="small text-truncate d-block">{{ $record->notice_number }}</strong>
                        <small class="text-muted text-truncate d-block">{{ $record->vehicle_plate }}</small>
                    </div>
                    @php
    $clampIdxLabel = $record->status->label();
    if ($record->status === \App\Enums\ClampingStatus::AwaitingPayment) {
        $clampIdxLabel = 'Pending';
    }
@endphp
<span class="badge {{ $record->status->badgeClass() }} rounded-pill">{{ $clampIdxLabel }}</span>
                </div>
                <div class="card-body small">
                    <div class="row g-2">
                        <div class="col-6"><strong class="text-muted d-block">Officer</strong>{{ $record->officer->name ?? '—' }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Clamped At</strong>{{ $record->clamped_at?->format('M d, Y') }}</div>
                        @if ($record->location)
                            <div class="col-12"><strong class="text-muted d-block">Location</strong><span style="word-break:break-word;">{{ $record->location }}</span></div>
                        @endif
                        @if ($record->citation)
                            <div class="col-12"><strong class="text-muted d-block">Citation</strong>{{ $record->citation->citation_number }}</div>
                        @endif
                        <div class="col-6"><strong class="text-muted d-block">Fee</strong>
                            ₱{{ number_format($record->clamping_fee ?? ($record->citation?->penalty_amount ?? 0), 2) }}
                        </div>
                        @if ($record->paid_at)
                            <div class="col-6"><strong class="text-muted d-block">Paid</strong>{{ $record->paid_at->format('M d, Y') }}</div>
                        @endif
                        @if ($record->released_at)
                            <div class="col-12"><strong class="text-muted d-block">Released</strong>{{ $record->released_at->format('M d, Y h:i A') }}</div>
                        @endif
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                        @can('markPaid', $record)
                            <form method="POST" action="{{ route('clamping.mark-paid', $record) }}" class="d-inline" onsubmit="return confirm('Record payment for {{ addslashes($record->notice_number) }}?')">
                                @csrf
                                <input type="hidden" name="clamping_fee" value="{{ $record->citation?->penalty_amount ?? 0 }}">
                                <input type="hidden" name="payment_method" value="cash">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-cash-coin me-1"></i>Mark Paid
                                </button>
                            </form>
                        @endcan
                        @can('markWaitingRelease', $record)
                            <form method="POST" action="{{ route('clamping.mark-waiting-release', $record) }}" class="d-inline" onsubmit="return confirm('Mark as waiting for release?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="bi bi-hourglass-split me-1"></i>Waiting
                                </button>
                            </form>
                        @endcan
                        @can('processRelease', $record)
                            <form method="POST" action="{{ route('clamping.process-release', $record) }}" class="d-inline" onsubmit="return confirm('Release this vehicle?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-unlock me-1"></i>Release
                                </button>
                            </form>
                        @endcan
                        <a href="{{ route('clamping.show', $record) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card stat-card text-center py-5">
                <div class="card-body">
                    <i class="bi bi-car-front fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted mb-2">No clamping records found.</h5>
                    <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

@if ($records->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $records->withQueryString()->links() }}
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[method="GET"]');
    if (!form) return;
    const dateFrom = form.querySelector('[name="date_from"]');
    const dateTo = form.querySelector('[name="date_to"]');
    if (dateFrom && dateTo) {
        [dateFrom, dateTo].forEach(el => {
            el.addEventListener('change', () => {
                if (dateFrom.value && dateTo.value) form.submit();
            });
        });
    }
});
</script>
@endpush
@endsection