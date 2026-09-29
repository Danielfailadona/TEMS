@extends('layouts.app')

@section('title', 'Citations')

@php
    $statusValue = request('status');
    $statusLabel = 'Filters';
    if ($statusValue && in_array($statusValue, array_column(\App\Enums\CitationStatus::cases(), 'value'), true)) {
        $statusLabel = \App\Enums\CitationStatus::from($statusValue)->label();
    }
@endphp

@push('styles')
<style>
    .cit-dash {
        --cit-navy: #0d2b49;
        --cit-blue: #176ff2;
        --cit-ink: #182235;
        --cit-muted: #78869a;
        --cit-line: #dfe5eb;
    }
    .cit-dash .cit-toolbar { display: flex; align-items: center; gap: 16px; width: 100%; flex-wrap: wrap; }
    .cit-dash .cit-filter-wrap { position: relative; flex: 0 0 128px; }
    .cit-dash .cit-filter-button {
        height: 38px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
        background: #fff; border: 1px solid #d7dee7; color: #111827; font-weight: 650; font-size: 16px;
        border-radius: 4px; cursor: pointer;
    }
    .cit-dash .cit-filter-button svg { width: 15px; height: 15px; }
    .cit-dash .cit-filter-button[aria-expanded="true"] svg { transform: rotate(180deg); }
    .cit-dash .cit-status-menu {
        display: none; position: absolute; top: calc(100% + 1px); left: 0; width: 285px;
        max-width: min(285px, 85vw); z-index: 20; background: #fff; border: 1px solid #dfe5eb;
        border-radius: 4px; box-shadow: 0 8px 24px rgba(20, 39, 62, 0.16); overflow: hidden;
    }
    .cit-dash .cit-status-menu.open { display: block; }
    .cit-dash .cit-status-option {
        width: 100%; height: 46px; text-align: left; padding: 0 28px; border: 0;
        border-bottom: 1px solid #dfe5eb; background: #fff; color: #263247; cursor: pointer;
    }
    .cit-dash .cit-status-option:last-child { border-bottom: 0; }
    .cit-dash .cit-status-option:hover, .cit-dash .cit-status-option[aria-selected="true"] { background: #f0f6ff; color: #145fc8; }
    .cit-dash .cit-search-box { display: flex; height: 38px; min-width: 100px; flex: 1; }
    .cit-dash .cit-search-submit {
        display: grid; place-items: center; width: 34px; flex: 0 0 34px; background: #fff;
        color: var(--cit-blue); border: 1px solid var(--cit-blue); border-radius: 4px 0 0 4px; cursor: pointer;
    }
    .cit-dash .cit-search-submit svg { width: 19px; height: 19px; stroke: currentColor; }
    .cit-dash .cit-search-box input {
        min-width: 0; width: 100%; border: 1px solid #d7dee7; border-left: 0; padding: 0 22px;
        outline: none; background: #fff; color: #263247; font-size: 16px; border-radius: 0 4px 4px 0;
    }
    .cit-dash .cit-search-box input:focus { border-color: #7aa9f8; box-shadow: inset 0 0 0 1px #7aa9f8; }
    .cit-dash .cit-primary-btn {
        height: 38px; border: 0; background: var(--cit-blue); color: #fff; border-radius: 16px;
        padding: 0 16px; font-weight: 650; white-space: nowrap; display: inline-flex; align-items: center;
        gap: 6px; text-decoration: none; font-size: 14px;
    }
    .cit-dash .cit-primary-btn:hover { background: #075edb; color: #fff; }
    .cit-dash .cit-table-wrap { margin-top: 24px; overflow-x: auto; background: #fff; border: 1px solid var(--cit-line); border-radius: 12px; }
    .cit-dash .cit-table { width: 100%; border-collapse: collapse; min-width: 790px; table-layout: fixed; background: #fff; }
    .cit-dash .cit-table thead th {
        height: 51px; text-align: left; font-size: 14px; font-weight: 700; padding: 0 16px;
        border-bottom: 2px solid #e8edf2; white-space: nowrap; color: var(--cit-ink);
    }
    .cit-dash .cit-table tbody tr { height: 97px; border-bottom: 2px solid #e8edf2; }
    .cit-dash .cit-table tbody tr:last-child { border-bottom: 0; }
    .cit-dash .cit-table td { padding: 0 16px; white-space: nowrap; font-size: 14px; color: var(--cit-ink); }
    .cit-dash .cit-table th:nth-child(1), .cit-dash .cit-table td:nth-child(1) { width: 21.5%; }
    .cit-dash .cit-table th:nth-child(2), .cit-dash .cit-table td:nth-child(2) { width: 19.5%; }
    .cit-dash .cit-table th:nth-child(3), .cit-dash .cit-table td:nth-child(3) { width: 10%; }
    .cit-dash .cit-table th:nth-child(4), .cit-dash .cit-table td:nth-child(4) { width: 11%; }
    .cit-dash .cit-table th:nth-child(5), .cit-dash .cit-table td:nth-child(5) { width: 13.5%; }
    .cit-dash .cit-table th:nth-child(6), .cit-dash .cit-table td:nth-child(6) { width: 10%; }
    .cit-dash .cit-table th:last-child, .cit-dash .cit-table td:last-child { width: 8%; text-align: center; }
    .cit-dash .cit-pill {
        display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
        border-radius: 7px; min-width: 40px; text-align: center;
    }
    .cit-dash .cit-view-btn {
        width: 48px; height: 34px; border: 1px solid var(--cit-blue); color: var(--cit-blue);
        background: #fff; border-radius: 18px; display: inline-grid; place-items: center; text-decoration: none;
    }
    .cit-dash .cit-view-btn svg { width: 20px; height: 20px; stroke: currentColor; }
    .cit-dash .cit-view-btn:hover { background: #edf5ff; color: var(--cit-blue); }
    .cit-dash .cit-empty { padding: 45px 20px; text-align: center; color: #738197; }
    .cit-dash .cit-table-footer {
        min-height: 42px; display: flex; justify-content: space-between; align-items: center; gap: 16px;
        color: #74839a; font-size: 11px; padding: 12px 4px 0; flex-wrap: wrap;
    }
    .cit-dash .pagination { display: flex; align-items: center; gap: 0; margin-bottom: 0; }
    .cit-dash .pagination .page-link {
        width: 38px; height: 30px; background: #fff; border: 1px solid #dce3eb; color: #176ff2;
        margin-left: -1px; display: grid; place-items: center; font-size: 13px; border-radius: 0;
        padding: 0; text-decoration: none;
    }
    .cit-dash .pagination .page-item:first-child .page-link { border-radius: 5px 0 0 5px; color: #60728a; }
    .cit-dash .pagination .page-item:last-child .page-link { border-radius: 0 5px 5px 0; color: #60728a; }
    .cit-dash .pagination .page-item.active .page-link { background: #176ff2; border-color: #176ff2; color: #fff; }
    .cit-dash .pagination .page-item.disabled .page-link { background: #fff; color: #c3cdd9; }
    @media (max-width: 600px) {
        .cit-dash .cit-toolbar { flex-wrap: wrap; gap: 10px; }
        .cit-dash .cit-filter-wrap { flex-basis: 115px; }
        .cit-dash .cit-search-box { order: 3; flex-basis: 100%; }
        .cit-dash .cit-primary-btn { margin-left: auto; }
        .cit-dash .cit-table-wrap { margin-top: 18px; }
    }
</style>
@endpush

@section('content')
<div class="cit-dash">
    <form id="citForm" method="GET" class="cit-toolbar" aria-label="Citation controls">
        <input type="hidden" name="status" id="citStatus" value="{{ request('status') }}">

        <div class="cit-filter-wrap">
            <button type="button" class="cit-filter-button" id="citFilterBtn" aria-haspopup="listbox" aria-expanded="false">
                {{ $statusLabel }}
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 12.5 8 3l6 9.5H2Z"/></svg>
            </button>
            <div class="cit-status-menu" id="citStatusMenu" role="listbox" aria-label="Filter by status">
                <button type="button" class="cit-status-option" role="option" aria-selected="{{ request('status') === '' || request('status') === null ? 'true' : 'false' }}" data-status="" data-label="All Status">All Status</button>
                @foreach (\App\Enums\CitationStatus::cases() as $status)
                    <button type="button" class="cit-status-option" role="option"
                            aria-selected="{{ request('status') === $status->value ? 'true' : 'false' }}"
                            data-status="{{ $status->value }}" data-label="{{ $status->label() }}">{{ $status->label() }}</button>
                @endforeach
            </div>
        </div>

        <label class="cit-search-box" aria-label="Search citations">
            <button type="submit" class="cit-search-submit" aria-label="Search citations">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="search" id="citSearchInput" placeholder="Citation # or plate..." value="{{ request('search') }}" autocomplete="off">
        </label>

        @can('create', App\Models\Citation::class)
            <a href="{{ route('citations.create') }}" class="cit-primary-btn">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Issue Citation
            </a>
        @endcan
    </form>

    <section class="cit-table-wrap" aria-label="Citations table">
        <table class="cit-table">
            <thead>
                <tr>
                    <th scope="col">Citation #</th>
                    <th scope="col">Violation</th>
                    <th scope="col">Vehicle</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Issued</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($citations as $citation)
                    <tr>
                        <td>{{ $citation->citation_number }}</td>
                        <td>{{ $citation->violationType->name }}</td>
                        <td>{{ $citation->vehicle_plate }}</td>
                        <td>₱{{ number_format($citation->penalty_amount, 2) }}</td>
                        <td>{{ $citation->issued_at->format('M d, Y') }}</td>
                        <td><span class="cit-pill {{ $citation->status->badgeClass() }}">{{ $citation->status->label() }}</span></td>
                        <td>
                            <a href="{{ route('citations.show', $citation) }}" class="cit-view-btn" aria-label="View citation {{ $citation->citation_number }}" title="View">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="cit-empty">No citations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <footer class="cit-table-footer">
        <span id="citResultCount">Showing {{ $citations->firstItem() ?? 0 }} to {{ $citations->lastItem() ?? 0 }} of {{ $citations->total() }} results</span>
        @if ($citations->hasPages())
            {{ $citations->links() }}
        @endif
    </footer>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('citForm');
    if (!form) return;

    const filterBtn = document.getElementById('citFilterBtn');
    const menu = document.getElementById('citStatusMenu');
    const statusInput = document.getElementById('citStatus');
    const searchInput = document.getElementById('citSearchInput');
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
        if (!event.target.closest('.cit-filter-wrap')) closeFilter();
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