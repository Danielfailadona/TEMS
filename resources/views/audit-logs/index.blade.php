@extends('layouts.app')

@section('title', 'Audit Logs')

@push('styles')
<style>
    /* ===== Audit Logs — port of Audit_Logs_HTML_CSS_Demo.html ===== */

    /* Toolbar card */
    .al-toolbar {
        position: relative;
        background: #fff;
        border: 1px solid #b8c1ce;
        border-radius: 10px;
        padding: 26px 14px 14px;
        margin-bottom: 24px;
    }
    .al-toolbar-row {
        display: flex;
        align-items: center;
    }
    .al-filter-wrap { position: relative; flex: 0 0 auto; }
    .al-filter-btn {
        height: 44px;
        padding: 0 16px;
        border: 1px solid #cfd6df;
        border-radius: 8px;
        background: #fff;
        color: #111827;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.02);
        white-space: nowrap;
    }
    .al-filter-btn:hover { background: #f7f9fb; }
    .al-arrow { font-size: 0.8rem; line-height: 1; color: #111827; }
    .al-search-wrap {
        flex: 1;
        height: 44px;
        border: 1px solid #d6dce5;
        border-left: 0;
        border-radius: 0 8px 8px 0;
        display: flex;
        overflow: hidden;
        background: #fff;
        min-width: 0;
    }
    .al-search-input {
        flex: 1;
        min-width: 0;
        border: 0;
        outline: 0;
        padding: 0 14px;
        color: #334155;
        font-size: 0.9rem;
        background: transparent;
    }
    .al-search-input::placeholder { color: #9aa4b5; }
    .al-search-btn {
        width: 54px;
        flex-shrink: 0;
        border: 1px solid #3478ff;
        border-radius: 7px;
        background: #fff;
        color: #2874ff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: -1px;
        font-size: 1.05rem;
    }
    .al-search-btn:hover { background: #edf2ff; }

    /* Filter overlay */
    .al-overlay {
        position: absolute;
        top: 52px;
        left: 0;
        width: 380px;
        background: #fff;
        border: 1px solid #d5dbe4;
        border-radius: 8px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
        z-index: 20;
        padding: 24px 28px;
        display: none;
    }
    .al-overlay.open { display: block; }
    .al-group { margin-bottom: 22px; }
    .al-group-label {
        display: block;
        font-size: 0.95rem;
        line-height: 1.2;
        font-weight: 700;
        margin-bottom: 7px;
        color: #111827;
    }
    .al-select {
        width: 100%;
        height: 48px;
        border: 1px solid #d1d7e0;
        border-radius: 7px;
        background: #fff
            url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%23647084' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")
            no-repeat right 14px center;
        background-size: 12px 8px;
        color: #475569;
        padding: 0 34px 0 17px;
        appearance: none;
        -webkit-appearance: none;
        font-size: 0.9rem;
    }
    .al-date-labels {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 28px;
        color: #707987;
        margin: 0 3px 3px;
        font-size: 0.82rem;
    }
    .al-date-row {
        display: grid;
        grid-template-columns: 1fr 28px 1fr;
        align-items: center;
    }
    .al-date-input {
        height: 48px;
        border: 1px solid #d1d7e0;
        border-radius: 6px;
        padding: 0 9px;
        color: #475569;
        width: 100%;
        outline: 0;
        font-size: 0.9rem;
        background: #fff;
    }
    .al-date-input:focus { border-color: #7290ee; }
    .al-dash {
        text-align: center;
        font-size: 1.2rem;
        color: #111827;
        font-weight: 700;
    }
    .al-apply {
        width: 100%;
        height: 46px;
        border: 0;
        border-radius: 8px;
        background: #2f5fe7;
        color: #fff;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.12s ease;
    }
    .al-apply:hover { background: #234fd1; }

    /* Chips */
    .al-chips {
        margin: 12px 0 0 108px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }
    .al-chip {
        height: 22px;
        border: 1px solid #d5dce5;
        border-radius: 3px;
        background: #fff;
        color: #9aa3b1;
        font-size: 0.72rem;
        padding: 0 8px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .al-chip:hover { background: #f7f9fb; color: #111827; text-decoration: none; }
    .al-chip strong { font-weight: 500; }
    .al-chip-x { color: #111827; font-size: 0.8rem; line-height: 1; }

    /* Table card */
    .al-table-card {
        background: #fff;
        border: 1px solid #b8c1ce;
        border-radius: 10px;
        padding: 26px 18px 14px;
    }
    .al-entry-count {
        color: #7b8495;
        font-size: 0.85rem;
        padding: 0 0 12px;
        border-bottom: 1px solid #e5e7eb;
        width: 64%;
    }
    .al-entry-count strong { color: #111827; font-weight: 700; }
    .al-table-wrap { overflow-x: auto; }
    .al-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        min-width: 820px;
    }
    .al-table th {
        text-align: left;
        height: 54px;
        padding: 0 18px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.85rem;
        font-weight: 750;
        color: #172033;
    }
    .al-table td {
        padding: 10px 11px;
        border-bottom: 1px solid #edf0f3;
        color: #435067;
        font-size: 0.85rem;
        vertical-align: middle;
    }
    .al-table th:nth-child(1), .al-table td:nth-child(1) { width: 17%; }
    .al-table th:nth-child(2), .al-table td:nth-child(2) { width: 12%; }
    .al-table th:nth-child(3), .al-table td:nth-child(3) { width: 16%; }
    .al-table th:nth-child(4), .al-table td:nth-child(4) { width: 34%; }
    .al-table th:nth-child(5), .al-table td:nth-child(5) { width: 14%; }
    .al-table th:nth-child(6), .al-table td:nth-child(6) { width: 5%; }
    .al-time { color: #202938 !important; font-weight: 600; white-space: nowrap; }
    .al-subject { color: #9ca7b8 !important; }
    .al-table td:nth-child(1) { padding: 0 11px; }
    .al-logo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        vertical-align: middle;
    }
    .al-logo img {
        width: 30px;
        height: 30px;
        object-fit: contain;
    }
    .al-event-tag {
        display: inline-flex;
        align-items: center;
        height: 28px;
        padding: 0 14px;
        border-radius: 14px;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
        margin-left: 10px;
        vertical-align: middle;
    }
    .al-event-tag.success { background: #22a85b; }
    .al-event-tag.danger  { background: #ef4444; }
    .al-event-tag.neutral { background: #6b7280; }
    .al-eye-btn {
        border: 1px solid #d5dbe4;
        border-radius: 6px;
        background: #fff;
        color: #647084;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        cursor: pointer;
    }
    .al-eye-btn:hover { background: #f3f6fa; color: #182235; }
    .al-properties { background: #f7f8fa; }
    .al-props { padding: 10px 6px; }
    .al-props-group { margin-bottom: 14px; }
    .al-props-group:last-child { margin-bottom: 0; }
    .al-props-group-title {
        font-size: 0.78rem;
        font-weight: 750;
        color: #202938;
        text-transform: capitalize;
        margin-bottom: 4px;
    }
    .al-props-row {
        display: flex;
        gap: 2px;
        padding: 2px 0;
    }
    .al-props-key {
        flex: 0 0 230px;
        font-weight: 600;
        color: #202938;
    }
    .al-props-val {
        color: #435067;
        word-break: break-word;
    }
    .al-empty { text-align: center; color: #8993a3; padding: 80px 0 !important; }
    .al-pagination {
        border-top: 1px solid #e5e7eb;
        margin-top: 14px;
        padding-top: 14px;
        display: flex;
        justify-content: flex-end;
    }
    .al-pagination nav .pagination { margin: 0; }

    /* Responsive */
    @media (max-width: 900px) {
        .al-toolbar-row { flex-wrap: wrap; }
        .al-filter-wrap { flex: 0 0 108px; }
        .al-search-wrap {
            flex: 1 1 100%;
            border: 1px solid #d6dce5;
            border-radius: 8px;
            margin-top: 8px;
            margin-left: 0;
        }
        .al-chips { margin-left: 0; }
        .al-overlay { max-width: calc(100vw - 60px); }
        .al-entry-count { width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $chips = [];
    if (request('log_name'))  $chips[] = ['label' => 'Log: ' . request('log_name'), 'clear' => route('audit-logs.index', request()->except('log_name'))];
    if (request('event'))     $chips[] = ['label' => 'Event: ' . ucfirst(request('event')), 'clear' => route('audit-logs.index', request()->except('event'))];
    if (request('date_from')) $chips[] = ['label' => 'From ' . request('date_from'), 'clear' => route('audit-logs.index', request()->except('date_from'))];
    if (request('date_to'))   $chips[] = ['label' => 'To ' . request('date_to'), 'clear' => route('audit-logs.index', request()->except('date_to'))];
    if (request('search'))    $chips[] = ['label' => 'Search: "' . request('search') . '"', 'clear' => route('audit-logs.index', request()->except('search'))];
@endphp

<div class="al-toolbar">
    <form method="GET" action="{{ route('audit-logs.index') }}" class="al-toolbar-row">
        <div class="al-filter-wrap" id="al-filter-wrap">
            <div role="button" tabindex="0" class="al-filter-btn" id="al-filter-btn" aria-expanded="false">
                <i class="bi bi-funnel-fill"></i>Filters<span class="al-arrow">▾</span>
            </div>

            <div class="al-overlay" id="al-overlay" role="dialog" aria-label="Filters">
                <div class="al-group">
                    <label class="al-group-label" for="al-log-select">Logs:</label>
                    <select name="log_name" id="al-log-select" class="al-select">
                        <option value="">All Logs</option>
                        @foreach ($logNames as $name)
                            <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="al-group">
                    <label class="al-group-label" for="al-event-select">Events:</label>
                    <select name="event" id="al-event-select" class="al-select">
                        <option value="">All Events</option>
                        @foreach ($events as $event)
                            <option value="{{ $event }}" {{ request('event') === $event ? 'selected' : '' }}>{{ ucfirst($event) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="al-group">
                    <label class="al-group-label">Date:</label>
                    <div class="al-date-labels"><span>From:</span><span>To:</span></div>
                    <div class="al-date-row">
                        <input type="date" name="date_from" class="al-date-input" value="{{ request('date_from') }}" aria-label="Date from">
                        <div class="al-dash">—</div>
                        <input type="date" name="date_to" class="al-date-input" value="{{ request('date_to') }}" aria-label="Date to">
                    </div>
                </div>

                <button type="submit" class="al-apply">Apply Filters</button>
            </div>
        </div>

        <div class="al-search-wrap">
            <input type="text" name="search" class="al-search-input" placeholder="Search..." value="{{ request('search') }}" aria-label="Search audit logs">
            <button type="submit" class="al-search-btn" aria-label="Search"><i class="bi bi-search"></i></button>
        </div>
    </form>

    @if (count($chips) > 0)
        <div class="al-chips">
            @foreach ($chips as $chip)
                <a href="{{ $chip['clear'] }}" class="al-chip" title="Remove this filter"><strong>{{ $chip['label'] }}</strong><span class="al-chip-x">×</span></a>
            @endforeach
        </div>
    @endif
</div>

<div class="al-table-card">
    <div class="al-entry-count">Showing <strong>{{ $activities->count() }}</strong> of <strong>{{ $activities->total() }}</strong> entries</div>

    <div class="al-table-wrap">
        <table class="al-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Time</th>
                    <th>User</th>
                    <th>Description</th>
                    <th>Subject</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td>
                            @php
                                $event = $activity->event;
                                $desc = strtolower((string) $activity->description);
                                if ($event === 'created') {
                                    $pillClass = 'success'; $pillText = 'Created'; $pillIcon = 'Createed.png';
                                } elseif ($event === 'updated') {
                                    $pillClass = 'neutral'; $pillText = 'Updated'; $pillIcon = 'Updated.png';
                                } elseif ($event === 'deleted') {
                                    $pillClass = 'danger'; $pillText = 'Deleted'; $pillIcon = 'Deleted.png';
                                } elseif (str_contains($desc, 'rejected')) {
                                    $pillClass = 'danger'; $pillText = 'Rejected'; $pillIcon = 'Rejected User.png';
                                } elseif (str_contains($desc, 'approved user')) {
                                    $pillClass = 'success'; $pillText = 'Approved'; $pillIcon = 'Approved User.png';
                                } elseif (str_contains($desc, 'suspended')) {
                                    $pillClass = 'danger'; $pillText = 'Suspended'; $pillIcon = 'Status Suspended.png';
                                } elseif (str_contains($desc, 'approved')) {
                                    $pillClass = 'success'; $pillText = 'Status Approved'; $pillIcon = 'Status Approved.png';
                                } elseif (str_contains($desc, 'force logged out')) {
                                    $pillClass = 'danger'; $pillText = 'Logout'; $pillIcon = 'Forced Logged out.png';
                                } else {
                                    $pillClass = 'neutral'; $pillText = $event ? ucfirst($event) : 'Action'; $pillIcon = null;
                                }
                            @endphp
                            @if ($pillIcon)
                                <span class="al-logo {{ $pillClass }}">
                                    <img src="{{ asset('images/Icons/' . $pillIcon) }}" alt="">
                                </span>
                            @endif
                            <span class="al-event-tag {{ $pillClass }}">{{ $pillText }}</span>
                        </td>
                        <td class="al-time">{{ $activity->created_at->format('M d, H:i') }}</td>
                        <td>{{ $activity->causer?->name ?? 'System' }}</td>
                        <td>{{ $activity->description }}</td>
                        <td class="al-subject">{{ class_basename($activity->subject_type ?? '') }} #{{ $activity->subject_id ?? '—' }}</td>
                        <td>
                            @if ($activity->properties && $activity->properties->isNotEmpty())
                                <button class="al-eye-btn" type="button" data-bs-toggle="collapse" data-bs-target="#log-{{ $activity->id }}" title="View details">
                                    <i class="bi bi-eye"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @if ($activity->properties && $activity->properties->isNotEmpty())
                        <tr class="collapse" id="log-{{ $activity->id }}">
                            <td colspan="6" class="al-properties">
                                @php
                                    $props = $activity->properties->toArray();
                                    $humanize = fn($key) => ucwords(str_replace('_', ' ', $key));
                                    $fmtVal = function ($v) use (&$fmtVal) {
                                        if (is_bool($v)) return $v ? 'true' : 'false';
                                        if (is_null($v)) return '-';
                                        if (is_array($v)) return '[' . implode(', ', array_map(fn($i) => is_scalar($i) ? (string) $i : json_encode($i), $v)) . ']';
                                        return (string) $v;
                                    };
                                @endphp
                                <div class="al-props">
                                    @foreach ($props as $groupKey => $groupVal)
                                        <div class="al-props-group">
                                            <div class="al-props-group-title">{{ $humanize($groupKey) }}</div>
                                            @if (is_array($groupVal))
                                                @foreach ($groupVal as $key => $value)
                                                    <div class="al-props-row">
                                                        <span class="al-props-key">{{ $humanize($key) }}:</span>
                                                        <span class="al-props-val">{{ $fmtVal($value) }}</span>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="al-props-row">
                                                    <span class="al-props-key">{{ $humanize($groupKey) }}:</span>
                                                    <span class="al-props-val">{{ $fmtVal($groupVal) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="al-empty">No audit log entries match your search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($activities->hasPages())
        <div class="al-pagination">{{ $activities->links() }}</div>
    @endif
</div>

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
    // other inputs (selects) can keep existing onchange submit if they have onchange attr
});
</script>
@endpush
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const wrap = document.getElementById('al-filter-wrap');
        const btn = document.getElementById('al-filter-btn');
        const overlay = document.getElementById('al-overlay');
        if (!wrap || !btn || !overlay) return;

        function closeFilter() {
            overlay.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const willOpen = !overlay.classList.contains('open');
            overlay.classList.toggle('open', willOpen);
            btn.setAttribute('aria-expanded', String(willOpen));
        });

        btn.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                btn.click();
            }
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#al-filter-wrap')) closeFilter();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeFilter();
        });
    });
</script>
@endpush