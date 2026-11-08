@extends('layouts.app')

@section('title', 'Task Management')

@php
    $statusValue = request('availability');
    $statusLabel = 'Filters';
    $availabilityValues = array_column(\App\Enums\ReportStatus::cases(), 'value');
    if ($statusValue && in_array($statusValue, $availabilityValues, true)) {
        $statusLabel = \App\Enums\ReportStatus::from($statusValue)->label();
    }
@endphp

@push('styles')
<style>
    .tm-dash {
        --tm-navy: #0d2b49;
        --tm-blue: #176ff2;
        --tm-ink: #182235;
        --tm-muted: #78869a;
        --tm-line: #dfe5eb;
        --tm-green: #10b981;
        --tm-amber: #f59e0b;
        --tm-red: #ef4444;
    }
    .tm-dash .tm-toolbar { display: flex; align-items: center; gap: 16px; width: 100%; flex-wrap: wrap; }
    .tm-dash .tm-filter-wrap { position: relative; flex: 0 0 128px; }
    .tm-dash .tm-filter-button {
        height: 38px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
        background: #fff; border: 1px solid #d7dee7; color: #111827; font-weight: 650; font-size: 16px;
        border-radius: 4px; cursor: pointer;
    }
    .tm-dash .tm-filter-button svg { width: 15px; height: 15px; }
    .tm-dash .tm-filter-button[aria-expanded="true"] .tm-caret { transform: rotate(180deg); }
    .tm-dash .tm-caret { transition: transform .18s ease; }

    .tm-dash .tm-filter-panel {
        display: none; position: absolute; top: calc(100% + 8px); left: 0; z-index: 30;
        width: 421px; max-width: min(421px, 88vw); padding: 22px 18px 28px;
        background: #fff; border: 1px solid #dfe5eb; border-radius: 8px;
        box-shadow: 0 12px 32px rgba(13, 43, 72, .16), 0 2px 6px rgba(13, 43, 72, .08);
    }
    .tm-dash .tm-filter-panel.open { display: grid; gap: 22px; }
    .tm-dash .tm-field { border: 0; padding: 0; margin: 0; min-width: 0; }
    .tm-dash .tm-field__label { font-size: 16px; font-weight: 700; color: #000; padding: 0; margin-bottom: 10px; display: block; }

    .tm-dash .tm-select { position: relative; }
    .tm-dash .tm-select select {
        appearance: none; -webkit-appearance: none; width: 100%; height: 46px; padding: 0 44px 0 16px;
        background: #fff; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: var(--tm-ink);
    }
    .tm-dash .tm-select select:focus { border-color: #3b82f6; outline: 2px solid rgba(59,130,246,.25); outline-offset: 0; }
    .tm-dash .tm-select svg {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        width: 16px; height: 16px; color: var(--tm-ink); pointer-events: none;
    }

    .tm-dash .tm-panel-actions { display: flex; gap: 14px; margin-top: 4px; }
    .tm-dash .tm-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        height: 40px; padding: 0 14px; border-radius: 4px; border: 1px solid transparent;
        font-size: 11px; font-weight: 700; text-decoration: none; white-space: nowrap; cursor: pointer;
    }
    .tm-dash .tm-btn svg { width: 14px; height: 14px; }
    .tm-dash .tm-btn--primary { background: var(--tm-blue); color: #fff; }
    .tm-dash .tm-btn--primary:hover { background: #075edb; color: #fff; }
    .tm-dash .tm-btn--neutral { background: #6b7280; color: #fff; }
    .tm-dash .tm-btn--neutral:hover { background: #565d6a; color: #fff; }

    .tm-dash .tm-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .tm-dash .tm-chip {
        display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 6px 0 10px;
        background: #eef5ff; border: 1px solid #cfe0fb; border-radius: 13px;
        font-size: 12px; color: #1b4fa8; text-decoration: none;
    }
    .tm-dash .tm-chip:hover { background: #e2eeff; color: #145fc8; }
    .tm-dash .tm-chip__x { display: grid; place-items: center; width: 18px; height: 18px; border-radius: 50%; color: inherit; }
    .tm-dash .tm-chip__x svg { width: 11px; height: 11px; stroke: currentColor; stroke-width: 2.2; }

    .tm-dash .enforcer-avatar {
        width: 2rem; height: 2rem; font-size: 1rem; border-radius: 50%;
        display: grid; place-items: center; background: transparent; color: var(--tm-ink);
        border: 2px solid #000; flex-shrink: 0;
    }

    .tm-dash .tm-primary-btn {
        height: 38px; border: 0; background: var(--tm-blue); color: #fff; border-radius: 16px;
        padding: 0 16px; font-weight: 650; white-space: nowrap; display: inline-flex; align-items: center;
        gap: 6px; text-decoration: none; font-size: 14px;
    }
    .tm-dash .tm-primary-btn:hover { background: #075edb; color: #fff; }
    .tm-dash .tm-search-box { display: flex; height: 38px; min-width: 100px; flex: 1; }
    .tm-dash .tm-search-submit {
        display: grid; place-items: center; width: 34px; flex: 0 0 34px; background: #fff;
        color: var(--tm-blue); border: 1px solid var(--tm-blue); border-radius: 4px 0 0 4px; cursor: pointer;
    }
    .tm-dash .tm-search-submit svg { width: 19px; height: 19px; stroke: currentColor; }
    .tm-dash .tm-search-box input {
        min-width: 0; width: 100%; border: 1px solid #d7dee7; border-left: 0; padding: 0 22px;
        outline: none; background: #fff; color: #263247; font-size: 16px; border-radius: 0 4px 4px 0;
    }
    .tm-dash .tm-search-box input:focus { border-color: #7aa9f8; box-shadow: inset 0 0 0 1px #7aa9f8; }
    .tm-dash .tm-table-wrap { margin-top: 24px; overflow-x: auto; background: #fff; border: 1px solid var(--tm-line); border-radius: 12px; }
    .tm-dash .tm-table { width: 100%; border-collapse: collapse; min-width: 900px; table-layout: fixed; background: #fff; }
    .tm-dash .tm-table thead th {
        height: 51px; text-align: left; font-size: 14px; font-weight: 700; padding: 0 16px;
        border-bottom: 2px solid #e8edf2; white-space: nowrap; color: var(--tm-ink);
    }
    .tm-dash .tm-table tbody tr { height: 78px; border-bottom: 2px solid #e8edf2; }
    .tm-dash .tm-table tbody tr:last-child { border-bottom: 0; }
    .tm-dash .tm-table td { padding: 0 16px; white-space: nowrap; font-size: 14px; color: var(--tm-ink); }
    .tm-dash .tm-table th:nth-child(1), .tm-dash .tm-table td:nth-child(1) { width: 20%; }
    .tm-dash .tm-table th:nth-child(2), .tm-dash .tm-table td:nth-child(2) { width: 10%; }
    .tm-dash .tm-table th:nth-child(3), .tm-dash .tm-table td:nth-child(3) { width: 13%; }
    .tm-dash .tm-table th:nth-child(4), .tm-dash .tm-table td:nth-child(4) { width: 13%; }
    .tm-dash .tm-table th:nth-child(5), .tm-dash .tm-table td:nth-child(5) { width: 10%; }
    .tm-dash .tm-table th:nth-child(6), .tm-dash .tm-table td:nth-child(6) { width: 10%; }
    .tm-dash .tm-table th:nth-child(7), .tm-dash .tm-table td:nth-child(7) { width: 12%; }
    .tm-dash .tm-table th:nth-child(8), .tm-dash .tm-table td:nth-child(8) { width: 20%; text-align: center; }
    .tm-dash .tm-pill {
        display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
        border-radius: 7px; min-width: 40px; text-align: center;
    }
    .tm-dash .tm-view-btn {
        width: 48px; height: 34px; border: 1px solid var(--tm-blue); color: var(--tm-blue);
        background: #fff; border-radius: 18px; display: inline-grid; place-items: center; text-decoration: none;
    }
    .tm-dash .tm-view-btn svg { width: 20px; height: 20px; stroke: currentColor; }
    .tm-dash .tm-view-btn:hover { background: #edf5ff; color: var(--tm-blue); }
    .tm-dash .tm-empty { padding: 45px 20px; text-align: center; color: #738197; }
    .tm-dash .tm-table-footer {
        min-height: 42px; display: flex; justify-content: space-between; align-items: center; gap: 16px;
        color: #74839a; font-size: 11px; padding: 12px 4px 0; flex-wrap: wrap;
    }
    .tm-dash .pagination { display: flex; align-items: center; gap: 0; margin-bottom: 0; }
    .tm-dash .pagination .page-link {
        width: 38px; height: 30px; background: #fff; border: 1px solid #dce3eb; color: #176ff2;
        margin-left: -1px; display: grid; place-items: center; font-size: 13px; border-radius: 0;
        padding: 0; text-decoration: none;
    }
    .tm-dash .pagination .page-item:first-child .page-link { border-radius: 5px 0 0 5px; color: #60728a; }
    .tm-dash .pagination .page-item:last-child .page-link { border-radius: 0 5px 5px 0; color: #60728a; }
    .tm-dash .pagination .page-item.active .page-link { background: #176ff2; border-color: #176ff2; color: #fff; }
    .tm-dash .pagination .page-item.disabled .page-link { background: #fff; color: #c3cdd9; }
    .tm-dash .tm-kpi-row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .tm-dash .tm-kpi-card { flex: 1; min-width: 140px; background: #fff; border: 1px solid var(--tm-line); border-radius: 12px; padding: 16px; }
    .tm-dash .tm-kpi-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tm-muted); margin-bottom: 4px; }
    .tm-dash .tm-kpi-value { font-size: 1.5rem; font-weight: 700; color: #182235; }
    .tm-dash .tm-kpi-value.force-green { color: #10b981; }
    .tm-dash .tm-kpi-value.force-amber { color: #f59e0b; }
    .tm-dash .tm-kpi-value.force-red { color: #ef4444; }
    @media (max-width: 600px) {
        .tm-dash .tm-toolbar { flex-wrap: wrap; gap: 10px; }
        .tm-dash .tm-filter-wrap { flex-basis: 115px; }
        .tm-dash .tm-search-box { order: 3; flex-basis: 100%; }
        .tm-dash .tm-table-wrap { margin-top: 18px; }
    }
</style>
@endpush

@section('content')
<div class="tm-dash">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4 animate-on-load">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-clipboard-check me-2"></i>Task Management</h1>
            <p class="text-muted mb-0">Manage enforcer schedules, availability overrides, and task assignments</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="tm-primary-btn" data-bs-toggle="modal" data-bs-target="#autoAssignModal">
                <i class="bi bi-magic me-1"></i>Auto-Assign All
            </button>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="tm-kpi-row animate-on-load">
        <div class="tm-kpi-card">
            <div class="tm-kpi-label">Total Enforcers</div>
            <div class="tm-kpi-value">{{ number_format($stats['total_enforcers']) }}</div>
        </div>
        <div class="tm-kpi-card">
            <div class="tm-kpi-label">Schedule Assignable</div>
            <div class="tm-kpi-value">{{ number_format($stats['schedule_assignable']) }}</div>
        </div>
        <div class="tm-kpi-card">
            <div class="tm-kpi-label">Force Available</div>
            <div class="tm-kpi-value force-{{ $stats['force_available_state'] }}">{{ number_format($stats['force_available']) }}</div>
        </div>
        <div class="tm-kpi-card">
            <div class="tm-kpi-label">Force Unavailable</div>
            <div class="tm-kpi-value force-{{ $stats['force_unavailable_state'] }}">{{ number_format($stats['force_unavailable']) }}</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <form id="tmForm" method="GET" class="tm-toolbar" aria-label="Task management controls">
        <div class="tm-filter-wrap">
            <button type="button" class="tm-filter-button" id="tmFilterBtn" aria-haspopup="dialog" aria-expanded="false" aria-controls="tmFilterPanel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filters
                <svg class="tm-caret" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 12.5 8 3l6 9.5H2Z"/></svg>
            </button>

            <div class="tm-filter-panel" id="tmFilterPanel" role="dialog" aria-label="Task management filters">
                <fieldset class="tm-field">
                    <label class="tm-field__label" for="tmAvailabilitySelect">Status</label>
                    <div class="tm-select">
                        <select name="availability" id="tmAvailabilitySelect">
                            <option value="">All Status</option>
                            <option value="schedule_assignable" {{ request('availability') === 'schedule_assignable' ? 'selected' : '' }}>Schedule Assignable</option>
                            <option value="available" {{ request('availability') === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="unavailable" {{ request('availability') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                            <option value="force_available" {{ request('availability') === 'force_available' ? 'selected' : '' }}>Force Available</option>
                            <option value="force_unavailable" {{ request('availability') === 'force_unavailable' ? 'selected' : '' }}>Force Unavailable</option>
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </fieldset>

                <fieldset class="tm-field">
                    <label class="tm-field__label" for="tmZoneSelect">Zone / Team</label>
                    <div class="tm-select">
                        <select name="zone" id="tmZoneSelect">
                            <option value="">All Zones</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}" {{ request('zone') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </fieldset>

                <fieldset class="tm-field">
                    <label class="tm-field__label" for="tmOverrideSelect">Override</label>
                    <div class="tm-select">
                        <select name="override" id="tmOverrideSelect">
                            <option value="">All Overrides</option>
                            <option value="auto" {{ request('override') === 'auto' ? 'selected' : '' }}>Auto</option>
                            <option value="force_available" {{ request('override') === 'force_available' ? 'selected' : '' }}>Force Available</option>
                            <option value="force_unavailable" {{ request('override') === 'force_unavailable' ? 'selected' : '' }}>Force Unavailable</option>
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </fieldset>

                <div class="tm-panel-actions">
                    <button type="button" class="tm-btn tm-btn--primary" id="tmApplyBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                        Apply Filter
                    </button>
                    <a class="tm-btn tm-btn--neutral" href="{{ route('operations.task-management.index') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        Clear Filter
                    </a>
                </div>
            </div>
        </div>

        <label class="tm-search-box" aria-label="Search enforcers">
            <button type="submit" class="tm-search-submit" aria-label="Search enforcers">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="search" id="tmSearchInput" placeholder="Search by name, email, badge ID..." value="{{ request('search') }}" autocomplete="off">
        </label>
    </form>

    {{-- Active Filter Chips --}}
    @php
        $activeFilters = [];
        if (request('availability')) {
            $labels = [
                'schedule_assignable' => 'Schedule Assignable',
                'available' => 'Available',
                'unavailable' => 'Unavailable',
                'force_available' => 'Force Available',
                'force_unavailable' => 'Force Unavailable',
            ];
            $activeFilters[] = ['label' => $labels[request('availability')] ?? request('availability'), 'href' => route('operations.task-management.index', array_diff_key(request()->query(), array_flip(['availability'])))];
        }
        if (request('zone')) {
            $zone = \App\Models\Team::find(request('zone'));
            if ($zone) {
                $activeFilters[] = ['label' => 'Zone: ' . $zone->name, 'href' => route('operations.task-management.index', array_diff_key(request()->query(), array_flip(['zone'])))];
            }
        }
        if (request('override')) {
            $labels = ['auto' => 'Auto', 'force_available' => 'Force Available', 'force_unavailable' => 'Force Unavailable'];
            $activeFilters[] = ['label' => 'Override: ' . ($labels[request('override')] ?? request('override')), 'href' => route('operations.task-management.index', array_diff_key(request()->query(), array_flip(['override'])))];
        }
        if (request('search')) {
            $activeFilters[] = ['label' => 'Search: "' . request('search') . '"', 'href' => route('operations.task-management.index', array_diff_key(request()->query(), array_flip(['search'])))];
        }
    @endphp

    @if (!empty($activeFilters))
        <div class="tm-chips" aria-live="polite">
            @foreach ($activeFilters as $chip)
                <a href="{{ $chip['href'] }}" class="tm-chip">
                    {{ $chip['label'] }}
                    <span class="tm-chip__x" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Table --}}
    <section class="tm-table-wrap" aria-label="Enforcers table">
        <table class="tm-table">
            <thead>
                <tr>
                    <th scope="col" style="width: 20%;">Enforcer</th>
                    <th scope="col" style="width: 10%;">Badge ID</th>
                    <th scope="col" style="width: 13%;">Zone/Team</th>
                    <th scope="col" style="width: 13%;">Today's Shift</th>
                    <th scope="col" style="width: 10%;">Status</th>
                    <th scope="col" style="width: 10%;">Override</th>
                    <th scope="col" style="width: 12%;">Open Reports</th>
                    <th scope="col" style="width: 20%;"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($enforcers as $data)
                    @php
                        $enforcer = $data['enforcer'];
                        $todaysSchedule = $data['todays_schedule'];
                        $isWithinSchedule = $data['is_within_schedule'];
                        $isScheduleAssignable = $data['is_schedule_assignable'];
                        $overrideStatus = $data['override_status'];

                        $scheduleStatusClass = $isScheduleAssignable
                            ? 'bg-success-soft text-success'
                            : ($isWithinSchedule
                                ? 'bg-warning-soft text-warning'
                                : ($enforcer->enforcerSchedules->isNotEmpty() ? 'bg-danger-soft text-danger' : 'bg-gray-soft text-gray'));
                        $scheduleStatusText = $isScheduleAssignable
                            ? 'On Duty'
                            : ($isWithinSchedule
                                ? 'On Duty (Offline)'
                                : ($enforcer->enforcerSchedules->isNotEmpty() ? 'Off Duty' : 'No Schedule'));

                        $overrideClass = match($overrideStatus) {
                            'force_available' => 'bg-success-soft text-success',
                            'force_unavailable' => 'bg-danger-soft text-danger',
                            default => 'bg-gray-soft text-gray',
                        };
                        $overrideText = match($overrideStatus) {
                            'force_available' => 'Force Available',
                            'force_unavailable' => 'Force Unavailable',
                            default => 'Auto (Schedule)',
                        };

                        $onlineClass = $enforcer->is_online ? 'bg-success' : 'bg-danger';
                        $onlineText = $enforcer->is_online ? 'Online' : 'Offline';
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="enforcer-avatar">{{ strtoupper($enforcer->name[0]) }}</div>
                                <div>
                                    <div class="fw-semibold text-truncate">{{ $enforcer->name }}</div>
                                    <small class="text-muted text-truncate d-block">{{ $enforcer->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $enforcer->badge_id ?? '—' }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @if ($enforcer->teams->isNotEmpty())
                                    @foreach ($enforcer->teams as $team)
                                        <span class="badge bg-info-soft text-info small">{{ $team->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if ($todaysSchedule)
                                <div class="d-inline-flex align-items-center gap-2 px-2 py-1 bg-success-soft text-success rounded-pill small">
                                    <i class="bi bi-clock"></i> {{ $todaysSchedule->start_time_short }} - {{ $todaysSchedule->end_time_short }}
                                </div>
                            @else
                                <span class="text-muted small">No shift scheduled</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <span class="tm-pill {{ $onlineClass }}">{{ $onlineText }}</span>
                                <span class="tm-pill {{ $scheduleStatusClass }}">{{ $scheduleStatusText }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="tm-pill {{ $overrideClass }}">{{ $overrideText }}</span>
                        </td>
                        <td>
                            @if ($enforcer->open_reports_count > 0)
                                <span class="tm-pill bg-primary-soft text-primary">{{ $enforcer->open_reports_count }}</span>
                            @else
                                <span class="text-muted small">0</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal-{{ $enforcer->id }}" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#scheduleModal-{{ $enforcer->id }}" title="Edit Schedule">
                                    <i class="bi bi-calendar-event"></i>
                                </button>
                                <form method="POST" action="{{ route('operations.task-management.override.update', $enforcer) }}" class="d-inline">
                                    @csrf
                                    <select name="availability_override" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                        <option value="auto" {{ $overrideStatus === 'auto' ? 'selected' : '' }}>Auto</option>
                                        <option value="force_available" {{ $overrideStatus === 'force_available' ? 'selected' : '' }}>Force Available</option>
                                        <option value="force_unavailable" {{ $overrideStatus === 'force_unavailable' ? 'selected' : '' }}>Force Unavailable</option>
                                    </select>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- View Details Modal --}}
                    <div class="modal fade" id="detailModal-{{ $enforcer->id }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $enforcer->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="detailModalLabel{{ $enforcer->id }}">User Details — {{ $enforcer->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Name</label>
                                        <p class="mb-0 fw-semibold">{{ $enforcer->name }}</p>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Email</label>
                                        <p class="mb-0">{{ $enforcer->email }}</p>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Badge ID</label>
                                        <p class="mb-0">{{ $enforcer->badge_id ?? '—' }}</p>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Role</label>
                                        <p class="mb-0">{{ $enforcer->role ?? '—' }}</p>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Status</label>
                                        <span class="tm-pill {{ $onlineClass }}">{{ $onlineText }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Zone / Team</label>
                                        <p class="mb-0">
                                            @if ($enforcer->teams->isNotEmpty())
                                                {{ $enforcer->teams->pluck('name')->implode(', ') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Today's Shift</label>
                                        <p class="mb-0">
                                            @if ($todaysSchedule)
                                                {{ $todaysSchedule->start_time_short }} - {{ $todaysSchedule->end_time_short }}
                                            @else
                                                <span class="text-muted">No shift scheduled</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Override</label>
                                        <p class="mb-0"><span class="tm-pill {{ $overrideClass }}">{{ $overrideText }}</span></p>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Open Reports</label>
                                        <p class="mb-0">
                                            @if ($enforcer->open_reports_count > 0)
                                                {{ $enforcer->open_reports_count }}
                                            @else
                                                0
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Schedule Modal --}}
                    <div class="modal fade" id="scheduleModal-{{ $enforcer->id }}" tabindex="-1" aria-labelledby="scheduleModalLabel{{ $enforcer->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="scheduleModalLabel{{ $enforcer->id }}">Edit Schedule — {{ $enforcer->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Current Shift</label>
                                        <p class="mb-0">
                                            @if ($todaysSchedule)
                                                {{ $todaysSchedule->start_time_short }} - {{ $todaysSchedule->end_time_short }}
                                            @else
                                                <span class="text-muted">No shift scheduled</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted d-block">Current Override</label>
                                        <p class="mb-0"><span class="tm-pill {{ $overrideClass }}">{{ $overrideText }}</span></p>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label" for="scheduleStart{{ $enforcer->id }}">Shift Start Time</label>
                                        <input type="time" name="shift_start" class="form-control" id="scheduleStart{{ $enforcer->id }}" value="{{ old('shift_start_' . $enforcer->id, $todaysSchedule->start_time ?? '') }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label" for="scheduleEnd{{ $enforcer->id }}">Shift End Time</label>
                                        <input type="time" name="shift_end" class="form-control" id="scheduleEnd{{ $enforcer->id }}" value="{{ old('shift_end_' . $enforcer->id, $todaysSchedule->end_time ?? '') }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label" for="availabilityOverride{{ $enforcer->id }}">Availability Override</label>
                                        <select name="availability_override" class="form-select" id="availabilityOverride{{ $enforcer->id }}">
                                            <option value="auto" {{ $overrideStatus === 'auto' ? 'selected' : '' }}>Auto</option>
                                            <option value="force_available" {{ $overrideStatus === 'force_available' ? 'selected' : '' }}>Force Available</option>
                                            <option value="force_unavailable" {{ $overrideStatus === 'force_unavailable' ? 'selected' : '' }}>Force Unavailable</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="8" class="tm-empty">No enforcers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <footer class="tm-table-footer">
        <span>Showing {{ $enforcers->firstItem() ?? 0 }} to {{ $enforcers->lastItem() ?? 0 }} of {{ $enforcers->total() }} results</span>
        @if ($enforcers->hasPages())
            {{ $enforcers->links('pagination::bootstrap-5') }}
        @endif
    </footer>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('tmForm');
    if (!form) return;

    const filterBtn = document.getElementById('tmFilterBtn');
    const panel = document.getElementById('tmFilterPanel');
    const applyBtn = document.getElementById('tmApplyBtn');
    const searchInput = document.getElementById('tmSearchInput');
    const availabilitySelect = document.getElementById('tmAvailabilitySelect');
    const zoneSelect = document.getElementById('tmZoneSelect');
    const overrideSelect = document.getElementById('tmOverrideSelect');

    const closePanel = () => {
        panel.classList.remove('open');
        filterBtn.setAttribute('aria-expanded', 'false');
    };

    filterBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = panel.classList.toggle('open');
        filterBtn.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.tm-filter-wrap')) closePanel();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closePanel();
    });

    applyBtn.addEventListener('click', () => {
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