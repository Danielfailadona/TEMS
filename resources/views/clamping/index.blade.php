@extends('layouts.app')

@section('title', 'Vehicle Clamping')

@push('styles')
<style>
    .clp-dash {
        --clp-blue: #176ff2;
        --clp-ink: #182235;
        --clp-muted: #78869a;
        --clp-line: #dfe5eb;
    }
    .clp-dash .clp-toolbar { display: flex; align-items: center; gap: 16px; width: 100%; flex-wrap: wrap; }
    .clp-dash .clp-search-box { display: flex; height: 38px; min-width: 100px; flex: 1; }
    .clp-dash .clp-search-submit {
        display: grid; place-items: center; width: 34px; flex: 0 0 34px; background: #fff;
        color: var(--clp-blue); border: 1px solid var(--clp-blue); border-radius: 4px 0 0 4px; cursor: pointer;
    }
    .clp-dash .clp-search-submit svg { width: 19px; height: 19px; stroke: currentColor; }
    .clp-dash .clp-search-box input {
        min-width: 0; width: 100%; border: 1px solid #d7dee7; border-left: 0; padding: 0 22px;
        outline: none; background: #fff; color: #263247; font-size: 16px; border-radius: 0 4px 4px 0;
    }
    .clp-dash .clp-search-box input:focus { border-color: #7aa9f8; box-shadow: inset 0 0 0 1px #7aa9f8; }
    .clp-dash .clp-danger-btn {
        height: 38px; border: 0; background: #dc3545; color: #fff; border-radius: 16px;
        padding: 0 16px; font-weight: 650; white-space: nowrap; display: inline-flex; align-items: center;
        gap: 6px; text-decoration: none; font-size: 14px;
    }
    .clp-dash .clp-danger-btn:hover { background: #bb2d3b; color: #fff; }
    .clp-dash .clp-table-card { margin-top: 22px; background: #fff; border: 1px solid var(--clp-line); border-radius: 12px; overflow: hidden; }
    .clp-dash .clp-table-wrap { overflow-x: auto; }
    .clp-dash .clp-table { width: 100%; border-collapse: collapse; min-width: 790px; table-layout: fixed; background: #fff; }
    .clp-dash .clp-table thead th {
        height: 51px; text-align: left; font-size: 14px; font-weight: 700; padding: 0 16px;
        border-bottom: 2px solid #e8edf2; white-space: nowrap; color: var(--clp-ink);
    }
    .clp-dash .clp-table tbody tr { height: 78px; border-bottom: 2px solid #e8edf2; }
    .clp-dash .clp-table tbody tr:last-child { border-bottom: 0; }
    .clp-dash .clp-table td { padding: 0 16px; white-space: nowrap; font-size: 14px; color: var(--clp-ink); }
    .clp-dash .clp-table th:nth-child(1), .clp-dash .clp-table td:nth-child(1) { width: 22%; }
    .clp-dash .clp-table th:nth-child(2), .clp-dash .clp-table td:nth-child(2) { width: 20%; }
    .clp-dash .clp-table th:nth-child(3), .clp-dash .clp-table td:nth-child(3) { width: 18%; }
    .clp-dash .clp-table th:nth-child(4), .clp-dash .clp-table td:nth-child(4) { width: 15%; }
    .clp-dash .clp-table th:nth-child(5), .clp-dash .clp-table td:nth-child(5) { width: 14%; }
    .clp-dash .clp-table th:last-child, .clp-dash .clp-table td:last-child { width: 11%; text-align: center; }
    .clp-dash .clp-badge {
        display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
        border-radius: 7px; min-width: 40px; text-align: center;
    }
    .clp-dash .clp-view-btn {
        width: 48px; height: 34px; border: 1px solid var(--clp-blue); color: var(--clp-blue);
        background: #fff; border-radius: 18px; display: inline-grid; place-items: center; text-decoration: none;
    }
    .clp-dash .clp-view-btn svg { width: 20px; height: 20px; stroke: currentColor; }
    .clp-dash .clp-view-btn:hover { background: #edf5ff; color: var(--clp-blue); }
    .clp-dash .clp-empty { padding: 45px 20px; text-align: center; color: #738197; }
    .clp-dash .clp-table-footer {
        min-height: 42px; display: flex; justify-content: space-between; align-items: center; gap: 16px;
        color: #74839a; font-size: 11px; padding: 12px 4px 0; flex-wrap: wrap;
    }
    .clp-dash .pagination { display: flex; align-items: center; gap: 0; margin-bottom: 0; }
    .clp-dash .pagination .page-link {
        width: 38px; height: 30px; background: #fff; border: 1px solid #dce3eb; color: #176ff2;
        margin-left: -1px; display: grid; place-items: center; font-size: 13px; border-radius: 0;
        padding: 0; text-decoration: none;
    }
    .clp-dash .pagination .page-item:first-child .page-link { border-radius: 5px 0 0 5px; color: #60728a; }
    .clp-dash .pagination .page-item:last-child .page-link { border-radius: 0 5px 5px 0; color: #60728a; }
    .clp-dash .pagination .page-item.active .page-link { background: #176ff2; border-color: #176ff2; color: #fff; }
    .clp-dash .pagination .page-item.disabled .page-link { background: #fff; color: #c3cdd9; }
    @media (max-width: 600px) {
        .clp-dash .clp-toolbar { flex-wrap: wrap; gap: 10px; }
        .clp-dash .clp-search-box { order: 3; flex-basis: 100%; }
        .clp-dash .clp-danger-btn { margin-left: auto; }
    }
</style>
@endpush

@section('content')
<div class="clp-dash">
    <form method="GET" class="clp-toolbar" id="clpForm" aria-label="Clamping record search">
        <label class="clp-search-box" aria-label="Search clamping records">
            <button type="submit" class="clp-search-submit" aria-label="Search clamping records">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="search" id="clpSearchInput" placeholder="Search notice #, plate, or officer..." value="{{ request('search') }}" autocomplete="off">
        </label>

        @can('create', App\Models\ClampingRecord::class)
            <a href="{{ route('clamping.create') }}" class="clp-danger-btn">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Record Clamp
            </a>
        @endcan
    </form>

    @if (isset($pendingRequests) && $pendingRequests->isNotEmpty())
        <div class="card stat-card mb-4">
            <div class="card-header bg-white"><strong>Citizen Clamping Requests</strong></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Requester</th><th>Vehicle Plate</th><th>Location</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($pendingRequests as $request)
                            <tr>
                                <td>{{ $request->requester_name ?? '—' }}</td>
                                <td>{{ $request->vehicle_plate }}</td>
                                <td>{{ $request->location ?? '—' }}</td>
                                <td>{{ $request->created_at->format('M d, Y') }}</td>
                                <td><span class="badge bg-warning">{{ ucfirst($request->status ?? 'pending') }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if (isset($overdueCitations) && $overdueCitations->isNotEmpty())
        <div class="card stat-card mb-4">
            <div class="card-header bg-white"><strong>Eligible Vehicles</strong></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Plate #</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($overdueCitations as $citation)
                            <tr>
                                <td>{{ $citation->vehicle_plate }}</td>
                                <td class="text-end">
                                    <a href="{{ route('clamping.create', ['vehicle_plate' => $citation->vehicle_plate]) }}" class="btn btn-sm btn-danger">Clamp</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <section class="clp-table-card" aria-label="Clamping records table">
        <div class="clp-table-wrap">
            <table class="clp-table">
                <thead>
                    <tr>
                        <th scope="col">Notice #</th>
                        <th scope="col">Vehicle</th>
                        <th scope="col">Officer</th>
                        <th scope="col">Clamped At</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>{{ $record->notice_number }}</td>
                            <td>{{ $record->vehicle_plate }}</td>
                            <td>{{ $record->officer->name }}</td>
                            <td>{{ $record->clamped_at->format('M d, Y') }}</td>
                            <td><span class="clp-badge {{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span></td>
                            <td>
                                <a href="{{ route('clamping.show', $record) }}" class="clp-view-btn" aria-label="View clamping record {{ $record->notice_number }}" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="clp-empty">No clamping records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="clp-table-footer">
            <span id="clpResultCount">Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ $records->total() }} results</span>
            @if ($records->hasPages())
                {{ $records->links() }}
            @endif
        </footer>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('clpForm');
    const searchInput = document.getElementById('clpSearchInput');
    if (!form || !searchInput) return;

    let debounce;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => form.submit(), 300);
    });
});
</script>
@endpush