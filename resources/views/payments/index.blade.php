@extends('layouts.app')

@section('title', 'Payments')

@php
    $qs = request()->only(['search', 'category', 'payment_method', 'date_from', 'date_to', 'online']);
    $qs = array_filter($qs, fn ($v) => $v !== null && $v !== '');
    $baseUrl = fn (array $drop) => route('payments.index', array_filter(array_diff_key($qs, array_flip($drop))));

    $activeFilters = [];
    if (($qs['category'] ?? null) !== null) {
        $label = \Illuminate\Support\Str::headline($qs['category']);
        $activeFilters[] = ['label' => $label, 'href' => $baseUrl(['category'])];
    }
    if (($qs['payment_method'] ?? null) !== null) {
        $activeFilters[] = ['label' => \App\Enums\PaymentMethod::tryFrom($qs['payment_method'])?->label() ?? $qs['payment_method'], 'href' => $baseUrl(['payment_method'])];
    }
    if (($qs['date_from'] ?? null) !== null) {
        $activeFilters[] = ['label' => 'From ' . \Illuminate\Support\Carbon::parse($qs['date_from'])->format('M d, Y'), 'href' => $baseUrl(['date_from', 'date_to'])];
    }
    if (($qs['date_to'] ?? null) !== null) {
        $activeFilters[] = ['label' => 'To ' . \Illuminate\Support\Carbon::parse($qs['date_to'])->format('M d, Y'), 'href' => $baseUrl(['date_from', 'date_to'])];
    }
    if (($qs['online'] ?? null) !== null) {
        $activeFilters[] = ['label' => 'Online only', 'href' => $baseUrl(['online'])];
    }
    if (($qs['search'] ?? null) !== null) {
        $activeFilters[] = ['label' => 'Search: "' . $qs['search'] . '"', 'href' => $baseUrl(['search'])];
    }

    $fromYmd = $qs['date_from'] ?? '';
    $toYmd = $qs['date_to'] ?? '';
    $fromText = $fromYmd !== '' ? \Illuminate\Support\Carbon::parse($fromYmd)->format('d/m/Y') : '';
    $toText = $toYmd !== '' ? \Illuminate\Support\Carbon::parse($toYmd)->format('d/m/Y') : '';
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

    /* Date range */
    .pym-dash .pym-field--dates .pym-field__label { margin-bottom: 6px; }
    .pym-dash .pym-date-range { display: flex; align-items: flex-end; gap: 12px; }
    .pym-dash .pym-date-field { flex: 1; min-width: 0; }
    .pym-dash .pym-date-sublabel { font-size: 14px; color: #6b7280; display: block; margin-bottom: 4px; }
    .pym-dash .pym-date-range__dash { width: 18px; height: 3px; margin-bottom: 22px; background: #000; flex: none; }
    .pym-dash .pym-input-wrap { position: relative; }
    .pym-dash .pym-input {
        width: 100%; height: 46px; padding: 0 44px 0 12px; box-sizing: border-box;
        background: #fff; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: var(--pym-ink);
    }
    .pym-dash .pym-input::placeholder { color: #9ca3af; }
    .pym-dash .pym-input:focus { border-color: #3b82f6; outline: 2px solid rgba(59,130,246,.25); outline-offset: 0; }
    .pym-dash .pym-input[aria-invalid="true"] { border-color: #d92d20; }
    .pym-dash .pym-input-wrap__btn {
        position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
        width: 36px; height: 36px; display: grid; place-items: center;
        border: 0; background: transparent; color: var(--pym-ink); border-radius: 6px; cursor: pointer;
    }
    .pym-dash .pym-input-wrap__btn:hover { background: #f1f4f8; }
    .pym-dash .pym-input-wrap__btn svg { width: 18px; height: 18px; }
    .pym-dash .pym-date-native { position: absolute; right: 0; bottom: 0; width: 1px; height: 1px; opacity: 0; pointer-events: none; }

    /* Checkbox */
    .pym-dash .pym-checkbox { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: var(--pym-ink); font-size: 14px; }
    .pym-dash .pym-checkbox input { position: absolute; opacity: 0; width: 16px; height: 16px; margin: 0; }
    .pym-dash .pym-checkbox__box {
        width: 16px; height: 16px; flex: none; background: #d9d9d9; border-radius: 2px;
        display: grid; place-items: center; transition: background-color .12s;
    }
    .pym-dash .pym-checkbox input:checked + .pym-checkbox__box { background: var(--pym-blue); }
    .pym-dash .pym-checkbox input:checked + .pym-checkbox__box::after {
        content: ""; width: 4px; height: 8px; margin-top: -2px;
        border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg);
    }
    .pym-dash .pym-checkbox input:focus-visible + .pym-checkbox__box { outline: 2px solid var(--pym-blue); outline-offset: 2px; }
    .pym-dash .pym-checkbox svg { width: 16px; height: 16px; color: var(--pym-muted); }

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
        font-size: 12px; color: #1b4fa8; text-decoration: none;
    }
    .pym-dash .pym-chip:hover { background: #e2eeff; color: #145fc8; }
    .pym-dash .pym-chip__x { display: grid; place-items: center; width: 18px; height: 18px; border-radius: 50%; color: inherit; }
    .pym-dash .pym-chip__x svg { width: 11px; height: 11px; stroke: currentColor; stroke-width: 2.2; }

    /* Table */
    .pym-dash .pym-table-wrap { margin-top: 24px; overflow-x: auto; background: #fff; border: 1px solid var(--pym-line); border-radius: 12px; }
    .pym-dash .pym-table { width: 100%; border-collapse: collapse; min-width: 900px; table-layout: fixed; background: #fff; }
    .pym-dash .pym-table thead th {
        height: 51px; text-align: left; font-size: 14px; font-weight: 600; padding: 0 16px;
        border-bottom: 2px solid #e8edf2; white-space: nowrap; color: var(--pym-ink);
    }
    .pym-dash .pym-table tbody tr { height: 78px; border-bottom: 2px solid #e8edf2; }
    .pym-dash .pym-table tbody tr:last-child { border-bottom: 0; }
    .pym-dash .pym-table tbody tr:hover td, .pym-dash .pym-table tbody tr:hover th { background: #f8fafd; }
    .pym-dash .pym-table tbody td, .pym-dash .pym-table tbody th { padding: 0 16px; white-space: nowrap; font-size: 14px; color: var(--pym-ink); overflow: hidden; text-overflow: ellipsis; }
    .pym-dash .pym-table tbody th { font-weight: 600; text-align: left; }
    .pym-dash .pym-table th:nth-child(1), .pym-dash .pym-table td:nth-child(1) { width: 23%; }
    .pym-dash .pym-table th:nth-child(2), .pym-dash .pym-table td:nth-child(2) { width: 12%; }
    .pym-dash .pym-table th:nth-child(3), .pym-dash .pym-table td:nth-child(3) { width: 11%; }
    .pym-dash .pym-table th:nth-child(4), .pym-dash .pym-table td:nth-child(4) { width: 13%; }
    .pym-dash .pym-table th:nth-child(5), .pym-dash .pym-table td:nth-child(5) { width: 13%; }
    .pym-dash .pym-table th:nth-child(6), .pym-dash .pym-table td:nth-child(6) { width: 14%; overflow: hidden; text-overflow: clip; }
    .pym-dash .pym-table th:nth-child(7), .pym-dash .pym-table td:nth-child(7) { width: 14%; }

    .pym-dash .pym-pill {
        display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
        border-radius: 7px; min-width: 40px; text-align: center; white-space: nowrap;
    }
    .pym-dash .pym-actions { display: flex; align-items: center; gap: 6px; }
    .pym-dash .pym-pill-btn {
        height: 30px; min-width: 40px; border-radius: 15px; display: inline-grid; place-items: center;
        text-decoration: none; border: 1px solid var(--pym-blue); color: var(--pym-blue); background: #fff;
    }
    .pym-dash .pym-pill-btn svg { width: 16px; height: 16px; stroke: currentColor; }
    .pym-dash .pym-pill-btn:hover { background: #edf5ff; }
    .pym-dash .pym-pill-btn--edit { border-color: #d0d7de; color: #4b5563; }
    .pym-dash .pym-pill-btn--edit:hover { background: #f8fafc; }
    .pym-dash .pym-collect-btn {
        height: 30px; padding: 0 14px; border-radius: 15px; border: 1px solid var(--pym-blue);
        background: var(--pym-blue); color: #fff; font-size: 11px; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
    }
    .pym-dash .pym-collect-btn:hover { background: #075edb; color: #fff; }
    .pym-dash .pym-collect-btn svg { width: 14px; height: 14px; }

    .pym-dash .pym-empty { padding: 45px 20px; text-align: center; color: #738197; }
    .pym-dash .pym-table-footer {
        min-height: 42px; display: flex; justify-content: space-between; align-items: center; gap: 16px;
        color: #74839a; font-size: 11px; padding: 12px 4px 0; flex-wrap: wrap;
    }
    .pym-dash .pagination { display: flex; align-items: center; gap: 0; margin-bottom: 0; }
    .pym-dash .pagination .page-link {
        width: 38px; height: 30px; background: #fff; border: 1px solid #dce3eb; color: #176ff2;
        margin-left: -1px; display: grid; place-items: center; font-size: 13px; border-radius: 0;
        padding: 0; text-decoration: none;
    }
    .pym-dash .pagination .page-item:first-child .page-link { border-radius: 5px 0 0 5px; color: #60728a; }
    .pym-dash .pagination .page-item:last-child .page-link { border-radius: 0 5px 5px 0; color: #60728a; }
    .pym-dash .pagination .page-item.active .page-link { background: #176ff2; border-color: #176ff2; color: #fff; }
    .pym-dash .pagination .page-item.disabled .page-link { background: #fff; color: #c3cdd9; }

    @media (max-width: 600px) {
        .pym-dash .pym-toolbar { flex-wrap: wrap; gap: 10px; }
        .pym-dash .pym-filter-wrap { flex-basis: 115px; }
        .pym-dash .pym-search-box { order: 3; flex-basis: 100%; }
        .pym-dash .pym-primary-btn { margin-left: auto; }
        .pym-dash .pym-table-wrap { margin-top: 18px; }
    }
</style>
@endpush

@section('content')
<div class="pym-dash">
    <form id="pymForm" method="GET" class="pym-toolbar" aria-label="Payment search and filters">
        <div class="pym-filter-wrap">
            <button type="button" class="pym-filter-button" id="pymFilterBtn" aria-haspopup="dialog" aria-expanded="false" aria-controls="pymFilterPanel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filters
                <svg class="pym-caret" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 12.5 8 3l6 9.5H2Z"/></svg>
            </button>

            <div class="pym-filter-panel" id="pymFilterPanel" role="dialog" aria-label="Payment filters">
                <fieldset class="pym-field">
                    <label class="pym-field__label" for="pymMethod">Methods</label>
                    <div class="pym-select">
                        <select id="pymMethod" name="payment_method">
                            <option value="">All Methods</option>
                            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                <option value="{{ $method->value }}" {{ request('payment_method') === $method->value ? 'selected' : '' }}>{{ $method->label() }}</option>
                            @endforeach
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </fieldset>

                <fieldset class="pym-field">
                    <label class="pym-field__label" for="pymCategory">Categories</label>
                    <div class="pym-select">
                        <select id="pymCategory" name="category">
                            <option value="">All Categories</option>
                            <option value="citation" {{ request('category') === 'citation' ? 'selected' : '' }}>Citation Fine</option>
                            <option value="clamping" {{ request('category') === 'clamping' ? 'selected' : '' }}>Clamping Fee</option>
                            <option value="impounding" {{ request('category') === 'impounding' ? 'selected' : '' }}>Impounding Fee</option>
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </fieldset>

                <fieldset class="pym-field pym-field--dates">
                    <legend class="pym-field__label">Date:</legend>
                    <div class="pym-date-range">
                        <div class="pym-date-field">
                            <label class="pym-date-sublabel" for="pymFromText">From:</label>
                            <div class="pym-input-wrap">
                                <input class="pym-input" id="pymFromText" type="text" inputmode="numeric"
                                       placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" value="{{ $fromText }}">
                                <button class="pym-input-wrap__btn" type="button" data-date-picker="pymFromText" aria-label="Pick start date">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg>
                                </button>
                                <input class="pym-date-native" type="date" name="date_from" id="pymFromNative"
                                       tabindex="-1" aria-hidden="true" data-native-for="pymFromText" value="{{ $fromYmd }}">
                            </div>
                        </div>
                        <span class="pym-date-range__dash" aria-hidden="true"></span>
                        <div class="pym-date-field">
                            <label class="pym-date-sublabel" for="pymToText">To:</label>
                            <div class="pym-input-wrap">
                                <input class="pym-input" id="pymToText" type="text" inputmode="numeric"
                                       placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" value="{{ $toText }}">
                                <button class="pym-input-wrap__btn" type="button" data-date-picker="pymToText" aria-label="Pick end date">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg>
                                </button>
                                <input class="pym-date-native" type="date" name="date_to" id="pymToNative"
                                       tabindex="-1" aria-hidden="true" data-native-for="pymToText" value="{{ $toYmd }}">
                            </div>
                        </div>
                    </div>
                    <p class="pym-field__error text-danger small mt-2 mb-0" id="pymDateError" role="alert" hidden></p>
                </fieldset>

                <div class="pym-field">
                    <p class="pym-field__label" id="pymStatusLabel">Activity Status</p>
                    <label class="pym-checkbox">
                        <input type="checkbox" id="pymOnline" name="online" value="1" aria-labelledby="pymStatusLabel" {{ request('online') ? 'checked' : '' }}>
                        <span class="pym-checkbox__box" aria-hidden="true"></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Online only
                    </label>
                </div>

                <div class="pym-panel-actions">
                    <button class="pym-btn pym-btn--primary" type="button" id="pymApplyBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                        Apply Filter
                    </button>
                    <a class="pym-btn pym-btn--neutral" href="{{ route('payments.index') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        Clear Filter
                    </a>
                </div>
            </div>
        </div>

        <label class="pym-search-box" aria-label="Search payments">
            <button type="submit" class="pym-search-submit" aria-label="Search payments">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="search" id="pymSearchInput" placeholder="Citation # or plate..." value="{{ request('search') }}" autocomplete="off">
        </label>

        @can('create', App\Models\Payment::class)
            <a href="{{ route('payments.create') }}" class="pym-primary-btn">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Record Payment
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

    <section class="pym-table-wrap" aria-label="Payments table">
        <table class="pym-table">
            <thead>
                <tr>
                    <th scope="col">Citation #</th>
                    <th scope="col">Vehicle</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Method</th>
                    <th scope="col">Paid At</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grid as $row)
                    @php
                        $isReceipt = $row['kind'] === 'receipt';
                        $payment = $row['payment'];
                        $payable = $row['payable'];
                        $category = $row['category'];
                    @endphp
                    <tr>
                        <th scope="row" title="{{ $isReceipt ? $payment->payableNoticeNumber() : ($payable->citation_number ?? $payable->notice_number) }}">
                            @if ($isReceipt)
                                {{ $payment->payableNoticeNumber() }}
                            @else
                                {{ $payable->citation_number ?? $payable->notice_number }}
                            @endif
                        </th>
                        <td>{{ $isReceipt ? $payment->payableVehicle() : ($payable->vehicle_plate ?: '—') }}</td>
                        <td>
                            @if ($isReceipt)
                                ₱{{ number_format($payment->amount, 2) }}
                            @else
                                @php
                                    $due = match ($category) {
                                        'clamping' => $payable->clamping_fee ?? $payable->citation?->penalty_amount ?? 0,
                                        'impounding' => $payable->getTotalFees(),
                                        default => $payable->penalty_amount,
                                    };
                                @endphp
                                ₱{{ number_format((float) $due, 2) }}
                            @endif
                        </td>
                        <td>
                            @if ($isReceipt)
                                {{ $payment->isOnlinePayment() ? ucfirst($payment->online_payment_method ?? 'Online') : ($payment->payment_method?->label() ?? '—') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $row['date']?->format('M d, Y') ?? '—' }}</td>
                        <td>
                            @if ($isReceipt)
                                <span class="pym-pill {{ $payment->getStatusBadgeClass() }}">{{ $payment->getStatusLabel() }}</span>
                            @else
                                <span class="pym-pill {{ $payable->status->badgeClass() }}">{{ $payable->status->label() }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="pym-actions">
                                @if ($isReceipt)
                                    <a href="{{ route('payments.show', $payment) }}" class="pym-pill-btn" aria-label="View {{ $payment->receipt_number }}" title="View">
                                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    @can('update', $payment)
                                        <a href="{{ route('payments.edit', $payment) }}" class="pym-pill-btn pym-pill-btn--edit" aria-label="Edit {{ $payment->receipt_number }}" title="Edit">
                                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </a>
                                    @endcan
                                @else
                                    @php
                                        $detailRoute = match ($category) {
                                            'clamping' => route('clamping.show', $payable),
                                            'impounding' => route('impounding.show', $payable),
                                            default => route('citations.show', $payable),
                                        };
                                        $payRoute = route('payments.create', array_filter([
                                            'category' => $category,
                                            $category === 'citation' ? 'citation_id' : $category . '_id' => $payable->id,
                                        ]));
                                    @endphp
                                    <a href="{{ $detailRoute }}" class="pym-pill-btn" aria-label="View record" title="View">
                                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    @can('create', App\Models\Payment::class)
                                        <a href="{{ $payRoute }}" class="pym-collect-btn">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                            Collect
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="pym-empty">No payments match these filters. Clear a filter or try a different citation number.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <footer class="pym-table-footer">
        <span>Showing {{ $grid->firstItem() ?? 0 }} to {{ $grid->lastItem() ?? 0 }} of {{ $grid->total() }} results</span>
        @if ($grid->hasPages())
            {{ $grid->links() }}
        @endif
    </footer>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('pymForm');
    if (!form) return;

    const filterBtn = document.getElementById('pymFilterBtn');
    const panel = document.getElementById('pymFilterPanel');

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

    const parseInputDate = (str) => {
        const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(str.trim());
        if (!m) return null;
        const dd = Number(m[1]), mm = Number(m[2]), yyyy = Number(m[3]);
        const d = new Date(yyyy, mm - 1, dd);
        return d.getFullYear() === yyyy && d.getMonth() === mm - 1 && d.getDate() === dd ? d : null;
    };
    const fmtInput = (d) => `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;

    const errorEl = document.getElementById('pymDateError');
    form.querySelectorAll('.pym-input').forEach((input) => {
        input.addEventListener('input', () => {
            const digits = input.value.replace(/\D/g, '').slice(0, 8);
            input.value = [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4)].filter(Boolean).join('/');
            input.removeAttribute('aria-invalid');
            if (errorEl) errorEl.hidden = true;
        });
    });

    form.querySelectorAll('[data-date-picker]').forEach((btn) => {
        const text = document.getElementById(btn.dataset.datePicker);
        const native = document.getElementById(btn.dataset.nativeFor);
        btn.addEventListener('click', () => {
            const parsed = parseInputDate(text.value);
            native.value = parsed ? `${parsed.getFullYear()}-${String(parsed.getMonth() + 1).padStart(2, '0')}-${String(parsed.getDate()).padStart(2, '0')}` : '';
            if (typeof native.showPicker === 'function') native.showPicker(); else text.focus();
        });
        native.addEventListener('change', () => {
            if (!native.value) return;
            const [y, m, d] = native.value.split('-').map(Number);
            text.value = fmtInput(new Date(y, m - 1, d));
            text.removeAttribute('aria-invalid');
            if (errorEl) errorEl.hidden = true;
        });
    });

    const applyBtn = document.getElementById('pymApplyBtn');
    applyBtn.addEventListener('click', () => {
        const fromEl = document.getElementById('pymFromText');
        const toEl = document.getElementById('pymToText');
        const from = fromEl.value ? parseInputDate(fromEl.value) : null;
        const to = toEl.value ? parseInputDate(toEl.value) : null;
        const showError = (msg, ...els) => {
            if (!errorEl) return;
            errorEl.textContent = msg;
            errorEl.hidden = false;
            els.forEach((el) => el.setAttribute('aria-invalid', 'true'));
        };
        if (fromEl.value && !from) return showError('Enter the start date as dd/mm/yyyy.', fromEl);
        if (toEl.value && !to) return showError('Enter the end date as dd/mm/yyyy.', toEl);
        if (from && to && from > to) return showError('The start date must be on or before the end date.', fromEl, toEl);
        const nativeFrom = document.getElementById('pymFromNative');
        const nativeTo = document.getElementById('pymToNative');
        nativeFrom.value = from ? `${from.getFullYear()}-${String(from.getMonth() + 1).padStart(2, '0')}-${String(from.getDate()).padStart(2, '0')}` : '';
        nativeTo.value = to ? `${to.getFullYear()}-${String(to.getMonth() + 1).padStart(2, '0')}-${String(to.getDate()).padStart(2, '0')}` : '';
        form.submit();
    });

    const searchInput = document.getElementById('pymSearchInput');
    let debounce;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => form.submit(), 300);
    });
});
</script>
@endpush