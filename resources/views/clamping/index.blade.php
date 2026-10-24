@extends('layouts.app')

@section('title', 'Clamping Records')

@php
    $qs = request()->only(['search', 'status', 'date_from', 'date_to']);
    $qs = array_filter($qs, fn ($v) => $v !== null && $v !== '');
    $baseUrl = fn (array $drop) => route('clamping.index', array_filter(array_diff_key($qs, array_flip($drop))));

    $activeFilters = [];
    if (($qs['search'] ?? null) !== null) {
        $activeFilters[] = ['label' => 'Search: "' . $qs['search'] . '"', 'href' => $baseUrl(['search'])];
    }
    if (($qs['status'] ?? null) !== null) {
        $statusLabels = [
            'awaiting_payment' => 'Awaiting Payment', 'paid' => 'Paid',
            'waiting_release' => 'Waiting Release', 'released' => 'Released',
        ];
        $activeFilters[] = ['label' => $statusLabels[$qs['status']] ?? \Illuminate\Support\Str::headline($qs['status']), 'href' => $baseUrl(['status'])];
    }
    if (($qs['date_from'] ?? null) !== null) {
        $activeFilters[] = ['label' => 'From ' . \Illuminate\Support\Carbon::parse($qs['date_from'])->format('M d, Y'), 'href' => $baseUrl(['date_from', 'date_to'])];
    }
    if (($qs['date_to'] ?? null) !== null) {
        $activeFilters[] = ['label' => 'To ' . \Illuminate\Support\Carbon::parse($qs['date_to'])->format('M d, Y'), 'href' => $baseUrl(['date_from', 'date_to'])];
    }
@endphp

@push('styles')
<style>
    .pym-dash {
        --pym-navy: #0d2b49;
        --pym-blue: #176ff2;
        --pym-ink: #182235;
        --pym-muted: #78869a;
        --pym-line: #dfe5eb;
    }
    .pym-dash .pym-toolbar { display: flex; align-items: center; gap: 16px; width: 100%; flex-wrap: wrap; }

    /* Filters dropdown */
    .pym-dash .pym-filter-wrap { position: relative; flex: 0 0 128px; }
    .pym-dash .pym-filter-button {
        height: 38px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
        background: #fff; border: 1px solid #d7dee7; color: #111827; font-weight: 650; font-size: 16px;
        border-radius: 4px; cursor: pointer;
    }
    .pym-dash .pym-filter-button svg { width: 15px; height: 15px; }
    .pym-dash .pym-filter-button[aria-expanded="true"] .pym-caret { transform: rotate(180deg); }
    .pym-dash .pym-caret { transition: transform .18s ease; }

    .pym-dash .pym-filter-panel {
        display: none; position: absolute; top: calc(100% + 8px); left: 0; z-index: 30;
        width: 421px; max-width: min(421px, 88vw); padding: 22px 18px 28px;
        background: #fff; border: 1px solid #dfe5eb; border-radius: 8px;
        box-shadow: 0 12px 32px rgba(13, 43, 72, .16), 0 2px 6px rgba(13, 43, 72, .08);
    }
    .pym-dash .pym-filter-panel.open { display: grid; gap: 22px; }
    .pym-dash .pym-field { border: 0; padding: 0; margin: 0; min-width: 0; }
    .pym-dash .pym-field__label { font-size: 16px; font-weight: 700; color: #000; padding: 0; margin-bottom: 10px; display: block; }

    .pym-dash .pym-select { position: relative; }
    .pym-dash .pym-select select {
        appearance: none; -webkit-appearance: none; width: 100%; height: 46px; padding: 0 44px 0 16px;
        background: #fff; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: var(--pym-ink);
    }
    .pym-dash .pym-select select:focus { border-color: #3b82f6; outline: 2px solid rgba(59,130,246,.25); outline-offset: 0; }
    .pym-dash .pym-select svg {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        width: 16px; height: 16px; color: var(--pym-ink); pointer-events: none;
    }

    .pym-dash .pym-date-field { flex: 1; min-width: 0; }
    .pym-dash .pym-input-wrap__btn {
        position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
        width: 36px; height: 36px; display: grid; place-items: center;
        border: 0; background: transparent; color: var(--pym-ink); border-radius: 6px; cursor: pointer;
    }
    .pym-dash .pym-input-wrap__btn:hover { background: #f1f4f8; }
    .pym-dash .pym-input-wrap__btn svg { width: 18px; height: 18px; }

    .pym-dash .pym-panel-actions { display: flex; gap: 14px; margin-top: 4px; }
    .pym-dash .pym-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        height: 40px; padding: 0 14px; border-radius: 4px; border: 1px solid transparent;
        font-size: 11px; font-weight: 700; text-decoration: none; white-space: nowrap; cursor: pointer;
    }
    .pym-dash .pym-btn svg { width: 14px; height: 14px; }
    .pym-dash .pym-btn--primary { background: var(--pym-blue); color: #fff; }
    .pym-dash .pym-btn--primary:hover { background: #075edb; color: #fff; }
    .pym-dash .pym-btn--neutral { background: #6b7280; color: #fff; }
    .pym-dash .pym-btn--neutral:hover { background: #565d6a; color: #fff; }

    /* Search */
    .pym-dash .pym-search-box { display: flex; height: 38px; min-width: 100px; flex: 1; }
    .pym-dash .pym-search-submit {
        display: grid; place-items: center; width: 34px; flex: 0 0 34px; background: #fff;
        color: var(--pym-blue); border: 1px solid var(--pym-blue); border-radius: 4px 0 0 4px; cursor: pointer;
    }
    .pym-dash .pym-search-submit svg { width: 19px; height: 19px; stroke: currentColor; }
    .pym-dash .pym-search-box input {
        min-width: 0; width: 100%; border: 1px solid #d7dee7; border-left: 0; padding: 0 22px;
        outline: none; background: #fff; color: #263247; font-size: 16px; border-radius: 0 4px 4px 0;
    }
    .pym-dash .pym-search-box input:focus { border-color: #7aa9f8; box-shadow: inset 0 0 0 1px #7aa9f8; }

    .pym-dash .pym-primary-btn {
        height: 38px; border: 0; background: var(--pym-blue); color: #fff; border-radius: 16px;
        padding: 0 16px; font-weight: 650; white-space: nowrap; display: inline-flex; align-items: center;
        gap: 6px; text-decoration: none; font-size: 14px;
    }
    .pym-dash .pym-primary-btn:hover { background: #075edb; color: #fff; }

    /* Filter chips */
    .pym-dash .pym-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px; }
    .pym-dash .pym-chip {
        display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 6px 0 10px;
        background: #eef5ff; border: 1px solid #cfe0fb; border-radius: 13px;
        font-size: 13px; color: #1473F9; text-decoration: none;
    }
    .pym-dash .pym-chip__x { font-size: 15px; line-height: 1; color: #1473F9; }

    /* Table */
    .pym-dash .pym-table-wrap { overflow-x: auto; margin-top: 18px; }
    .pym-dash .pym-table { width: 100%; border-collapse: collapse; font-size: 14px; color: var(--pym-ink); background: #fff; }
    .pym-dash .pym-table th,
    .pym-dash .pym-table td { padding: 10px 12px; border-bottom: 1px solid var(--pym-line); text-align: left; vertical-align: middle; }
    .pym-dash .pym-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--pym-muted); white-space: nowrap; }
    .pym-dash .pym-table tbody tr:hover { background: #f4f7fa; }

    .pym-dash .pym-pill {
        display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
        border-radius: 7px; min-width: 40px; text-align: center; white-space: nowrap;
    }

    .pym-dash .pym-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .pym-dash .pym-pill-btn {
        display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 14px;
        min-width: 100px;
        border: 1px solid var(--pym-line); border-radius: 15px; font-size: 12px; color: var(--pym-ink);
        text-decoration: none; background: #fff; cursor: pointer;
    }
    .pym-dash .pym-pill-btn:hover { background: #f4f7fa; }

    .pym-dash .pym-empty { padding: 30px; text-align: center; color: var(--pym-muted); }

    .pym-dash .pym-table-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; font-size: 13px; color: var(--pym-muted); }
</style>
@endpush

@section('content')
<div class="pym-dash">
    <form id="clampForm" method="GET" class="pym-toolbar" aria-label="Clamping search and filters">
        <div class="pym-filter-wrap">
            <button type="button" class="pym-filter-button" id="clampFilterBtn" aria-haspopup="dialog" aria-expanded="false" aria-controls="clampFilterPanel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filters
                <svg class="pym-caret" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 12.5 8 3l6 9.5H2Z"/></svg>
            </button>

            <div class="pym-filter-panel" id="clampFilterPanel" role="dialog" aria-label="Clamping filters">
                <fieldset class="pym-field">
                    <label class="pym-field__label" for="clampStatus">Status</label>
                    <div class="pym-select">
                        <select id="clampStatus" name="status">
                            <option value="">All Statuses</option>
                            @foreach (\App\Enums\ClampingStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </fieldset>

                <fieldset class="pym-field pym-field--dates">
                    <legend class="pym-field__label">Date:</legend>
                    <div class="pym-date-range" style="display:flex; align-items:flex-end; gap:12px;">
                        <div class="pym-date-field" style="flex:1; min-width:0;">
                            <label class="pym-date-sublabel" style="font-size:14px; color:#6b7280; display:block; margin-bottom:4px;">From:</label>
                            <div class="pym-input-wrap" style="position:relative;">
                                <input type="date" name="date_from" id="clampFromDate" value="{{ request('date_from') }}" style="width:100%; height:46px; padding:0 12px; box-sizing:border-box; background:#fff; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:var(--pym-ink);">
                            </div>
                        </div>
                        <span class="pym-date-range__dash" style="width:18px; height:3px; margin-bottom:22px; background:#000; flex:none;" aria-hidden="true"></span>
                        <div class="pym-date-field" style="flex:1; min-width:0;">
                            <label class="pym-date-sublabel" style="font-size:14px; color:#6b7280; display:block; margin-bottom:4px;">To:</label>
                            <div class="pym-input-wrap" style="position:relative;">
                                <input type="date" name="date_to" id="clampToDate" value="{{ request('date_to') }}" style="width:100%; height:46px; padding:0 12px; box-sizing:border-box; background:#fff; border:1px solid #d1d5db; border-radius:6px; font-size:14px; color:var(--pym-ink);">
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div class="pym-panel-actions">
                    <button class="pym-btn pym-btn--primary" type="button" id="clampApplyBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                        Apply Filter
                    </button>
                    <a class="pym-btn pym-btn--neutral" href="{{ route('clamping.index') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        Clear Filter
                    </a>
                </div>
            </div>
        </div>

        <label class="pym-search-box" aria-label="Search clamping records">
            <button type="submit" class="pym-search-submit" aria-label="Search clamping records">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="search" id="clampSearchInput" placeholder="Notice #, plate, or officer..." value="{{ request('search') }}" autocomplete="off">
        </label>

        @can('create', App\Models\ClampingRecord::class)
            <a href="{{ route('clamping.create') }}" class="pym-primary-btn">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Record Clamping
            </a>
        @endcan
    </form>

    @if (! empty($activeFilters))
        <div class="pym-chips" aria-live="polite">
            @foreach ($activeFilters as $chip)
                <a href="{{ $chip['href'] }}" class="pym-chip">
                    {{ $chip['label'] }}
                    <span class="pym-chip__x" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    <section class="pym-table-wrap" aria-label="Clamping records table">
        <table class="pym-table">
            <thead>
                <tr>
                    <th scope="col">Notice #</th>
                    <th scope="col">Plate</th>
                    <th scope="col">Officer</th>
                    <th scope="col">Clamped At</th>
                    <th scope="col">Status</th>
                    <th scope="col">Fee</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grid as $row)
                    @php
                        $statusLabel = $row->status->label();
                        $statusBadge = $row->status->badgeClass();
                    @endphp
                    <tr>
                        <th scope="row">{{ $row->notice_number }}</th>
                        <td>{{ $row->vehicle_plate }}</td>
                        <td>{{ $row->officer->name ?? '—' }}</td>
                        <td>{{ $row->clamped_at?->format('M d, Y') ?? '—' }}</td>
                        <td><span class="pym-pill {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                        <td>₱{{ number_format($row->clamping_fee ?? ($row->citation?->penalty_amount ?? 0), 2) }}</td>
                        <td>
                            <div class="pym-actions">
                                <a href="{{ route('clamping.show', $row) }}" class="pym-pill-btn" aria-label="View {{ $row->notice_number }}" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="16" height="16"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View
                                </a>
                                @can('markPaid', $row)
                                    <form method="POST" action="{{ route('clamping.mark-paid', $row) }}" class="d-inline" onsubmit="return confirm('Record payment for {{ addslashes($row->notice_number) }}?')">
                                        @csrf
                                        <input type="hidden" name="clamping_fee" value="{{ $row->citation?->penalty_amount ?? 0 }}">
                                        <input type="hidden" name="payment_method" value="cash">
                                        <button type="submit" class="pym-pill-btn" style="background:#10B981; border-color:#10B981; color:#fff;">
                                            <i class="bi bi-cash-coin" style="font-size:1rem;"></i>Mark Paid
                                        </button>
                                    </form>
                                @endcan
                                @can('markWaitingRelease', $row)
                                    <form method="POST" action="{{ route('clamping.mark-waiting-release', $row) }}" class="d-inline" onsubmit="return confirm('Mark as waiting for release?')">
                                        @csrf
                                        <button type="submit" class="pym-pill-btn" style="background:#f59e0b; border-color:#f59e0b; color:#fff;">
                                            <i class="bi bi-hourglass-split" style="font-size:1rem;"></i>Waiting
                                        </button>
                                    </form>
                                @endcan
                                @can('processRelease', $row)
                                    <form method="POST" action="{{ route('clamping.process-release', $row) }}" class="d-inline" onsubmit="return confirm('Release this vehicle?')">
                                        @csrf
                                        <button type="submit" class="pym-pill-btn" style="background:#69757F; border-color:#69757F; color:#fff;">
                                            <i class="bi bi-unlock" style="font-size:1rem;"></i>Release
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="pym-empty">No clamping records match these filters. Clear a filter or try a different notice number.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <footer class="pym-table-footer">
        <span>Showing {{ $grid->firstItem() ?? 0 }} to {{ $grid->lastItem() ?? 0 }} of {{ $grid->total() }} results</span>
        @if ($grid->hasPages())
            {{ $grid->withQueryString()->links() }}
        @endif
    </footer>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('clampForm');
    if (!form) return;

    const filterBtn = document.getElementById('clampFilterBtn');
    const panel = document.getElementById('clampFilterPanel');

    const closePanel = () => {
        panel.classList.remove('open');
        filterBtn.setAttribute('aria-expanded', 'false');
    };
    filterBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = panel.classList.toggle('open');
        filterBtn.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.pym-filter-wrap')) closePanel();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closePanel();
    });

    const searchInput = document.getElementById('clampSearchInput');
    let debounce;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => form.submit(), 300);
    });

    const dateFrom = document.getElementById('clampFromDate');
    const dateTo = document.getElementById('clampToDate');
    [dateFrom, dateTo].forEach(el => {
        el.addEventListener('change', () => {
            if (dateFrom.value && dateTo.value) form.submit();
        });
    });
});
</script>
@endpush
