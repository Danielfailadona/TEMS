@extends('layouts.app')

@section('title', 'Clamping Requests')

@push('styles')
<style>
    .cr-dash {
        --cr-blue: #176ff2;
        --cr-ink: #182235;
        --cr-line: #dfe5eb;
    }
    .cr-dash .cr-controls {
        height: 81px; background: #fff; border: 1px solid var(--cr-line); border-radius: 14px;
        display: flex; align-items: center; padding: 0 22px; margin-bottom: 22px; gap: 16px;
    }
    .cr-dash .cr-filter-wrap { position: relative; }
    .cr-dash .cr-filter-button {
        height: 37px; width: 117px; border: 1px solid #dce1e7; background: #fff;
        display: flex; align-items: center; justify-content: center; gap: 9px;
        font-size: 15px; font-weight: 700; color: #101010; border-radius: 6px; cursor: pointer;
    }
    .cr-dash .cr-filter-button:hover { background: #f4f7fb; }
    .cr-dash .cr-filter-glyph { width: 15px; height: 15px; color: var(--cr-blue); }
    .cr-dash .cr-filter-menu {
        position: absolute; left: 0; top: 42px; width: 229px; background: #fff; padding: 4px 0;
        border: 1px solid #d6dadf; border-radius: 6px; z-index: 50; display: none; overflow: hidden;
        box-shadow: 0 6px 18px rgba(20, 40, 60, .13);
    }
    .cr-dash .cr-filter-menu.open { display: block; }
    .cr-dash .cr-filter-option {
        height: 26px; width: 100%; border: 0; background: #fff; text-align: left; padding: 0 10px;
        font-size: 15px; color: #0e0e0e; display: flex; align-items: center; text-decoration: none;
    }
    .cr-dash .cr-filter-option:hover, .cr-dash .cr-filter-option.selected { background: #f1f5f9; }
    .cr-dash .cr-search-box {
        height: 37px; display: flex; flex: 1 1 420px; border: 1px solid #d7dde5; border-radius: 6px;
        background: #fff; overflow: hidden;
    }
    .cr-dash .cr-search-icon {
        width: 38px; border-right: 1px solid #d7dde5; display: grid; place-items: center;
        color: var(--cr-blue); background: #fff;
    }
    .cr-dash .cr-search-icon svg { width: 16px; height: 16px; }
    .cr-dash .cr-search-box input {
        border: 0; outline: 0; flex: 1; padding: 0 16px; font-size: 15px; color: #263247; background: none;
    }
    .cr-dash .cr-search-box input:focus { box-shadow: inset 0 0 0 1px #7aa9f8; }
    .cr-dash .cr-table-card { margin-top: 22px; background: #fff; border: 1px solid var(--cr-line); border-radius: 12px; overflow: hidden; }
    .cr-dash .cr-table-wrap { overflow-x: auto; }
    .cr-dash .cr-table { width: 100%; border-collapse: collapse; min-width: 900px; table-layout: fixed; background: #fff; }
    .cr-dash .cr-table thead th {
        height: 51px; text-align: left; font-size: 14px; font-weight: 700; padding: 0 16px;
        border-bottom: 2px solid #e8edf2; white-space: nowrap; color: var(--cr-ink);
    }
    .cr-dash .cr-table tbody tr { height: 78px; border-bottom: 2px solid #e8edf2; }
    .cr-dash .cr-table tbody tr:last-child { border-bottom: 0; }
    .cr-dash .cr-table td { padding: 0 16px; white-space: nowrap; font-size: 14px; color: var(--cr-ink); }
    .cr-dash .cr-table th:nth-child(1), .cr-dash .cr-table td:nth-child(1) { width: 20%; }
    .cr-dash .cr-table th:nth-child(2), .cr-dash .cr-table td:nth-child(2) { width: 13%; }
    .cr-dash .cr-table th:nth-child(3), .cr-dash .cr-table td:nth-child(3) { width: 16%; }
    .cr-dash .cr-table th:nth-child(4), .cr-dash .cr-table td:nth-child(4) { width: 15%; }
    .cr-dash .cr-table th:nth-child(5), .cr-dash .cr-table td:nth-child(5) { width: 12%; }
    .cr-dash .cr-table th:nth-child(6), .cr-dash .cr-table td:nth-child(6) { width: 11%; }
    .cr-dash .cr-table th:last-child, .cr-dash .cr-table td:last-child { width: 13%; text-align: center; }
    .cr-dash .cr-requester { font-size: 15px; font-weight: 700; color: #20252c; }
    .cr-dash .cr-phone { display: block; margin-top: 4px; font-size: 13px; color: #6d7a8b; }
    .cr-dash .cr-location { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cr-dash .cr-assigned { color: #4b5868; }
    .cr-dash .cr-date { color: #4b5868; }
    .cr-dash .cr-badge {
        display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
        border-radius: 7px; min-width: 40px; text-align: center;
    }
    .cr-dash .cr-badge.bg-warning { background: #ffc107; color: #6b3b00; }
    .cr-dash .cr-badge.bg-success { background: #10b981; color: #fff; }
    .cr-dash .cr-badge.bg-danger { background: #ef4444; color: #fff; }
    .cr-dash .cr-badge.bg-info { background: #17a2b8; color: #fff; }
    .cr-dash .cr-badge.bg-secondary { background: #738197; color: #fff; }
    .cr-dash .cr-view-btn {
        width: 48px; height: 34px; border: 1px solid var(--cr-blue); color: var(--cr-blue);
        background: #fff; border-radius: 18px; display: inline-grid; place-items: center; text-decoration: none;
    }
    .cr-dash .cr-view-btn svg { width: 20px; height: 20px; stroke: currentColor; }
    .cr-dash .cr-view-btn:hover { background: #edf5ff; color: var(--cr-blue); }
    .cr-dash .cr-empty { padding: 45px 20px; text-align: center; color: #738197; }
    .cr-dash .cr-table-footer {
        min-height: 42px; display: flex; justify-content: space-between; align-items: center; gap: 16px;
        color: #74839a; font-size: 11px; padding: 12px 4px 0; flex-wrap: wrap;
    }
    .cr-dash .pagination { display: flex; align-items: center; gap: 0; margin-bottom: 0; }
    .cr-dash .pagination .page-link {
        width: 38px; height: 30px; background: #fff; border: 1px solid #dce3eb; color: #176ff2;
        margin-left: -1px; display: grid; place-items: center; font-size: 13px; border-radius: 0;
        padding: 0; text-decoration: none;
    }
    .cr-dash .pagination .page-item:first-child .page-link { border-radius: 5px 0 0 5px; color: #60728a; }
    .cr-dash .pagination .page-item:last-child .page-link { border-radius: 0 5px 5px 0; color: #60728a; }
    .cr-dash .pagination .page-item.active .page-link { background: #176ff2; border-color: #176ff2; color: #fff; }
    .cr-dash .pagination .page-item.disabled .page-link { background: #fff; color: #c3cdd9; }
    @media (max-width: 760px) {
        .cr-dash .cr-controls { height: auto; padding: 15px; flex-wrap: wrap; gap: 12px; }
        .cr-dash .cr-search-box { flex-basis: 100%; }
    }
</style>
@endpush

@section('content')
<div class="cr-dash">
    {{-- Stats (5 cards) --}}
    <div class="row g-3 mb-4" aria-label="Request statistics">
        <div class="col-xl-2 col-lg-2 col-md-3 col-6">
            <div class="stat-card h-100 text-center d-flex flex-column justify-content-center align-items-center">
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label justify-content-center">Total Requests</div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-2 col-md-3 col-6">
            <div class="stat-card state-{{ $stats['pending_state'] }} h-100 text-center d-flex flex-column justify-content-center align-items-center">
                <div class="stat-value">{{ $stats['pending'] }}</div>
                <div class="stat-label justify-content-center"><span class="state-dot"></span>Pending</div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-2 col-md-3 col-6">
            <div class="stat-card h-100 text-center d-flex flex-column justify-content-center align-items-center">
                <div class="stat-value">{{ $stats['approved'] }}</div>
                <div class="stat-label justify-content-center">Approved</div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-2 col-md-3 col-6">
            <div class="stat-card h-100 text-center d-flex flex-column justify-content-center align-items-center">
                <div class="stat-value">{{ $stats['rejected'] }}</div>
                <div class="stat-label justify-content-center">Rejected</div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-2 col-md-3 col-6">
            <div class="stat-card h-100 text-center d-flex flex-column justify-content-center align-items-center">
                <div class="stat-value">{{ $stats['resolved'] }}</div>
                <div class="stat-label justify-content-center">Resolved</div>
            </div>
        </div>
    </div>

    {{-- Control bar: filter dropdown + search --}}
    <section class="cr-controls" aria-label="Filter and search clamping requests">
        <form method="GET" action="{{ route('clamping-requests.index') }}" id="crForm" class="d-flex align-items-center gap-3 w-100">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="cr-filter-wrap">
                <button type="button" id="crFilterButton" class="cr-filter-button" aria-expanded="false" aria-controls="crFilterMenu">
                    Filters
                    <svg class="cr-filter-glyph" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z"/>
                    </svg>
                </button>
                <div id="crFilterMenu" class="cr-filter-menu" role="menu">
                    <a href="{{ route('clamping-requests.index', request()->only('search')) }}" class="cr-filter-option @if(!request('status')) selected @endif" role="menuitem">All</a>
                    <a href="{{ route('clamping-requests.index', array_merge(['status' => 'pending'], request()->only('search'))) }}" class="cr-filter-option @if(request('status') === 'pending') selected @endif" role="menuitem">Pending</a>
                    <a href="{{ route('clamping-requests.index', array_merge(['status' => 'approved'], request()->only('search'))) }}" class="cr-filter-option @if(request('status') === 'approved') selected @endif" role="menuitem">Approved</a>
                    <a href="{{ route('clamping-requests.index', array_merge(['status' => 'rejected'], request()->only('search'))) }}" class="cr-filter-option @if(request('status') === 'rejected') selected @endif" role="menuitem">Rejected</a>
                    <a href="{{ route('clamping-requests.index', array_merge(['status' => 'resolved'], request()->only('search'))) }}" class="cr-filter-option @if(request('status') === 'resolved') selected @endif" role="menuitem">Resolved</a>
                </div>
            </div>
            <label class="cr-search-box" aria-label="Search clamping requests">
                <span class="cr-search-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
                </span>
                <input type="search" name="search" id="crSearchInput" placeholder="Search by plate, name, or location..." value="{{ request('search') }}" autocomplete="off">
            </label>
        </form>
    </section>

    {{-- Table --}}
    <section class="cr-table-card" aria-label="Clamping requests table">
        <div class="cr-table-wrap">
            <table class="cr-table">
                <thead>
                    <tr>
                        <th scope="col">Requester</th>
                        <th scope="col">Vehicle Plate</th>
                        <th scope="col">Location</th>
                        <th scope="col">Assigned To</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $r)
                        <tr>
                            <td>
                                <span class="cr-requester">{{ $r->requester_name ?? '—' }}</span>
                                @if ($r->requester_phone)
                                    <span class="cr-phone">{{ $r->requester_phone }}</span>
                                @endif
                            </td>
                            <td>{{ $r->vehicle_plate }}</td>
                            <td><span class="cr-location" title="{{ $r->location_address }}">{{ $r->location_address }}</span></td>
                            <td><span class="cr-assigned">{{ $r->assignedTo?->name ?? '—' }}</span></td>
                            <td><span class="cr-date">{{ $r->created_at->format('M d, Y') }}</span></td>
                            <td><span class="cr-badge bg-{{ $r->getStatusBadgeClass() }}">{{ $r->getStatusLabel() }}</span></td>
                            <td>
                                <a href="{{ route('clamping-requests.show', $r) }}" class="cr-view-btn" aria-label="View clamping request for {{ $r->vehicle_plate }}" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="cr-empty">No clamping requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="cr-table-footer">
            <span id="crResultCount">Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} results</span>
            @if ($requests->hasPages())
                {{ $requests->withQueryString()->links() }}
            @endif
        </footer>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterButton = document.getElementById('crFilterButton');
    const filterMenu = document.getElementById('crFilterMenu');
    const form = document.getElementById('crForm');
    const searchInput = document.getElementById('crSearchInput');

    if (filterButton && filterMenu) {
        filterButton.addEventListener('click', e => {
            e.stopPropagation();
            const open = filterMenu.classList.toggle('open');
            filterButton.setAttribute('aria-expanded', String(open));
        });

        document.addEventListener('click', e => {
            if (!e.target.closest('.cr-filter-wrap')) {
                filterMenu.classList.remove('open');
                filterButton.setAttribute('aria-expanded', 'false');
            }
        });

        filterMenu.addEventListener('click', e => {
            if (e.target.closest('.cr-filter-option')) {
                filterMenu.classList.remove('open');
                filterButton.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                filterMenu.classList.remove('open');
                filterButton.setAttribute('aria-expanded', 'false');
            }
        });
    }

    if (form && searchInput) {
        let debounce;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => form.submit(), 300);
        });
    }
});
</script>
@endpush