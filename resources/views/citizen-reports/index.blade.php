@extends('layouts.app')

@section('title', 'Citizen Reports')

@php
    $statusValue = request('status');
    $statusLabel = 'Filters';
    $statusValues = array_column(\App\Enums\ReportStatus::cases(), 'value');
    if ($statusValue && in_array($statusValue, $statusValues, true)) {
        $statusLabel = \App\Enums\ReportStatus::from($statusValue)->label();
    }
@endphp

@push('styles')
<style>
    .cr-dash {
        --cr-navy: #0d2b49;
        --cr-blue: #176ff2;
        --cr-ink: #182235;
        --cr-muted: #78869a;
        --cr-line: #dfe5eb;
    }
    .cr-dash .cr-toolbar { display: flex; align-items: center; gap: 16px; width: 100%; flex-wrap: wrap; }
    .cr-dash .cr-filter-wrap { position: relative; flex: 0 0 128px; }
    .cr-dash .cr-filter-button {
        height: 38px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
        background: #fff; border: 1px solid #d7dee7; color: #111827; font-weight: 650; font-size: 16px;
        border-radius: 4px; cursor: pointer;
    }
    .cr-dash .cr-filter-button svg { width: 15px; height: 15px; }
    .cr-dash .cr-filter-button[aria-expanded="true"] svg { transform: rotate(180deg); }
    .cr-dash .cr-status-menu {
        display: none; position: absolute; top: calc(100% + 1px); left: 0; width: 285px;
        max-width: min(285px, 85vw); z-index: 20; background: #fff; border: 1px solid #dfe5eb;
        border-radius: 4px; box-shadow: 0 8px 24px rgba(20, 39, 62, 0.16); overflow: hidden;
    }
    .cr-dash .cr-status-menu.open { display: block; }
    .cr-dash .cr-status-option {
        width: 100%; height: 46px; text-align: left; padding: 0 28px; border: 0;
        border-bottom: 1px solid #dfe5eb; background: #fff; color: #263247; cursor: pointer;
    }
    .cr-dash .cr-status-option:last-child { border-bottom: 0; }
    .cr-dash .cr-status-option:hover, .cr-dash .cr-status-option[aria-selected="true"] { background: #f0f6ff; color: #145fc8; }
    .cr-dash .cr-search-box { display: flex; height: 38px; min-width: 100px; flex: 1; }
    .cr-dash .cr-search-submit {
        display: grid; place-items: center; width: 34px; flex: 0 0 34px; background: #fff;
        color: var(--cr-blue); border: 1px solid var(--cr-blue); border-radius: 4px 0 0 4px; cursor: pointer;
    }
    .cr-dash .cr-search-submit svg { width: 19px; height: 19px; stroke: currentColor; }
    .cr-dash .cr-search-box input {
        min-width: 0; width: 100%; border: 1px solid #d7dee7; border-left: 0; padding: 0 22px;
        outline: none; background: #fff; color: #263247; font-size: 16px; border-radius: 0 4px 4px 0;
    }
    .cr-dash .cr-search-box input:focus { border-color: #7aa9f8; box-shadow: inset 0 0 0 1px #7aa9f8; }
    .cr-dash .cr-table-wrap { margin-top: 24px; overflow-x: auto; background: #fff; border: 1px solid var(--cr-line); border-radius: 12px; }
    .cr-dash .cr-table { width: 100%; border-collapse: collapse; min-width: 790px; table-layout: fixed; background: #fff; }
    .cr-dash .cr-table thead th {
        height: 51px; text-align: left; font-size: 14px; font-weight: 700; padding: 0 16px;
        border-bottom: 2px solid #e8edf2; white-space: nowrap; color: var(--cr-ink);
    }
    .cr-dash .cr-table tbody tr { height: 78px; border-bottom: 2px solid #e8edf2; }
    .cr-dash .cr-table tbody tr:last-child { border-bottom: 0; }
    .cr-dash .cr-table td { padding: 0 16px; white-space: nowrap; font-size: 14px; color: var(--cr-ink); }
    .cr-dash .cr-table th:nth-child(1), .cr-dash .cr-table td:nth-child(1) { width: 20%; }
    .cr-dash .cr-table th:nth-child(2), .cr-dash .cr-table td:nth-child(2) { width: 15%; }
    .cr-dash .cr-table th:nth-child(3), .cr-dash .cr-table td:nth-child(3) { width: 20%; }
    .cr-dash .cr-table th:nth-child(4), .cr-dash .cr-table td:nth-child(4) { width: 15%; }
    .cr-dash .cr-table th:nth-child(5), .cr-dash .cr-table td:nth-child(5) { width: 15%; }
    .cr-dash .cr-table th:nth-child(6), .cr-dash .cr-table td:nth-child(6) { width: 15%; }
    .cr-dash .cr-table th:last-child, .cr-dash .cr-table td:last-child { width: 10%; text-align: center; }
    .cr-dash .cr-pill {
        display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
        border-radius: 7px; min-width: 40px; text-align: center;
    }
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
    .cr-dash .cr-kpi-row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .cr-dash .cr-kpi-card { flex: 1; min-width: 140px; background: #fff; border: 1px solid var(--cr-line); border-radius: 12px; padding: 16px; }
    .cr-dash .cr-kpi-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--cr-muted); margin-bottom: 4px; }
    .cr-dash .cr-kpi-value { font-size: 1.5rem; font-weight: 700; color: #182235; }
    .cr-dash .cr-kpi-value.pending-green { color: #10b981; }
    .cr-dash .cr-kpi-value.pending-amber { color: #f59e0b; }
    .cr-dash .cr-kpi-value.pending-red { color: #ef4444; }
    @media (max-width: 600px) {
        .cr-dash .cr-toolbar { flex-wrap: wrap; gap: 10px; }
        .cr-dash .cr-filter-wrap { flex-basis: 115px; }
        .cr-dash .cr-search-box { order: 3; flex-basis: 100%; }
        .cr-dash .cr-table-wrap { margin-top: 18px; }
    }
</style>
@endpush

@section('content')
<div class="cr-dash">
    <p class="text-muted mb-4 small">Manage citizen-reported violations and clamping concerns</p>

    {{-- KPI Cards --}}
    <div class="cr-kpi-row">
        <div class="cr-kpi-card">
            <div class="cr-kpi-label">Total Reports</div>
            <div class="cr-kpi-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="cr-kpi-card">
            <div class="cr-kpi-label">Under Review</div>
            <div class="cr-kpi-value pending-{{ $stats['pending_state'] }}">{{ number_format($stats['pending']) }}</div>
        </div>
        <div class="cr-kpi-card">
            <div class="cr-kpi-label">Accepted</div>
            <div class="cr-kpi-value">{{ number_format($stats['accepted']) }}</div>
        </div>
        <div class="cr-kpi-card">
            <div class="cr-kpi-label">Rejected</div>
            <div class="cr-kpi-value">{{ number_format($stats['rejected']) }}</div>
        </div>
        <div class="cr-kpi-card">
            <div class="cr-kpi-label">Resolved</div>
            <div class="cr-kpi-value">{{ number_format($stats['resolved']) }}</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <form id="crForm" method="GET" class="cr-toolbar" aria-label="Citizen report controls">
        <input type="hidden" name="status" id="crStatus" value="{{ request('status') }}">

        <div class="cr-filter-wrap">
            <button type="button" class="cr-filter-button" id="crFilterBtn" aria-haspopup="listbox" aria-expanded="false">
                {{ $statusLabel }}
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 12.5 8 3l6 9.5H2Z"/></svg>
            </button>
            <div class="cr-status-menu" id="crStatusMenu" role="listbox" aria-label="Filter by status">
                <button type="button" class="cr-status-option" role="option"
                        aria-selected="{{ request('status') === '' || request('status') === null ? 'true' : 'false' }}"
                        data-status="" data-label="All Status">All Status</button>
                <button type="button" class="cr-status-option" role="option"
                        aria-selected="{{ request('status') === 'pending' ? 'true' : 'false' }}"
                        data-status="pending" data-label="Pending">Pending</button>
                <button type="button" class="cr-status-option" role="option"
                        aria-selected="{{ request('status') === 'accepted' ? 'true' : 'false' }}"
                        data-status="accepted" data-label="Accepted">Accepted</button>
                <button type="button" class="cr-status-option" role="option"
                        aria-selected="{{ request('status') === 'rejected' ? 'true' : 'false' }}"
                        data-status="rejected" data-label="Rejected">Rejected</button>
                <button type="button" class="cr-status-option" role="option"
                        aria-selected="{{ request('status') === 'resolved' ? 'true' : 'false' }}"
                        data-status="resolved" data-label="Resolved">Resolved</button>
            </div>
        </div>

        <label class="cr-search-box" aria-label="Search citizen reports">
            <button type="submit" class="cr-search-submit" aria-label="Search citizen reports">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="search" id="crSearchInput" placeholder="Search by ticket, plate, name, or location..." value="{{ request('search') }}" autocomplete="off">
        </label>
    </form>

    {{-- Table --}}
    <section class="cr-table-wrap" aria-label="Citizen reports table">
        <table class="cr-table">
            <thead>
                <tr>
                    <th scope="col">Report #</th>
                    <th scope="col">Vehicle Plate</th>
                    <th scope="col">Location</th>
                    <th scope="col">Status</th>
                    <th scope="col">Reported</th>
                    <th scope="col">Assigned To</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $r)
                    <tr>
                        <td>{{ $r->report_number }}</td>
                        <td>{{ $r->vehicle_plate }}</td>
                        <td>{{ $r->location_address }}</td>
                        <td><span class="cr-pill {{ $r->getStatusBadgeClass() }}">{{ $r->getStatusLabel() }}</span></td>
                        <td>{{ $r->created_at->format('M d, Y') }}</td>
                        <td>{{ $r->assignedTo?->name ?? '—' }}</td>
                        <td style="text-align: center;">
                            <a href="{{ route('citizen-reports.show', $r) }}" class="cr-view-btn" aria-label="View report {{ $r->report_number }}" title="View">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="cr-empty">No citizen reports found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <footer class="cr-table-footer">
        <span id="crResultCount">Showing {{ $reports->firstItem() ?? 0 }} to {{ $reports->lastItem() ?? 0 }} of {{ $reports->total() }} results</span>
        @if ($reports->hasPages())
            {{ $reports->withQueryString()->links('pagination::bootstrap-5') }}
        @endif
    </footer>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('crForm');
    if (!form) return;

    const filterBtn = document.getElementById('crFilterBtn');
    const menu = document.getElementById('crStatusMenu');
    const statusInput = document.getElementById('crStatus');
    const searchInput = document.getElementById('crSearchInput');
    const chevron = '<svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 12.5 8 3l6 9.5H2Z"/></svg>';

    const closeFilter = () => {
        menu.classList.remove('open');
        filterBtn.setAttribute('aria-expanded', 'false');
    };

    filterBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = menu.classList.toggle('open');
        filterBtn.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.cr-filter-wrap')) closeFilter();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeFilter();
    });

    menu.addEventListener('click', (event) => {
        const option = event.target.closest('[data-status]');
        if (!option) return;
        statusInput.value = option.dataset.status;
        menu.querySelectorAll('[data-status]').forEach(el => el.setAttribute('aria-selected', String(el === option)));
        const label = option.dataset.label || 'Filters';
        filterBtn.innerHTML = label + ' ' + chevron;
        closeFilter();
        form.submit();
    });

    let debounce;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => form.submit(), 300);
    });
});
</script>
@endpush