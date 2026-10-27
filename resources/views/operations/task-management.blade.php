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
    .tm-dash .tm-filter-button[aria-expanded="true"] svg { transform: rotate(180deg); }
    .tm-dash .tm-caret { transition: transform .18s ease; }
    .tm-dash .tm-status-menu {
        display: none; position: absolute; top: calc(100% + 1px); left: 0; width: 285px;
        max-width: min(285px, 85vw); z-index: 20; background: #fff; border: 1px solid #dfe5eb;
        border-radius: 4px; box-shadow: 0 8px 24px rgba(20, 39, 62, 0.16); overflow: hidden;
    }
    .tm-dash .tm-status-menu.open { display: block; }
    .tm-dash .tm-status-option {
        width: 100%; height: 46px; text-align: left; padding: 0 28px; border: 0;
        border-bottom: 1px solid #dfe5eb; background: #fff; color: #263247; cursor: pointer;
    }
    .tm-dash .tm-status-option:last-child { border-bottom: 0; }
    .tm-dash .tm-status-option:hover, .tm-dash .tm-status-option[aria-selected="true"] { background: #f0f6ff; color: #145fc8; }
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
    .tm-dash .tm-table th:nth-child(1), .tm-dash .tm-table td:nth-child(1) { width: 25%; }
    .tm-dash .tm-table th:nth-child(2), .tm-dash .tm-table td:nth-child(2) { width: 10%; }
    .tm-dash .tm-table th:nth-child(3), .tm-dash .tm-table td:nth-child(3) { width: 15%; }
    .tm-dash .tm-table th:nth-child(4), .tm-dash .tm-table td:nth-child(4) { width: 15%; }
    .tm-dash .tm-table th:nth-child(5), .tm-dash .tm-table td:nth-child(5) { width: 10%; }
    .tm-dash .tm-table th:nth-child(6), .tm-dash .tm-table td:nth-child(6) { width: 10%; }
    .tm-dash .tm-table th:nth-child(7), .tm-dash .tm-table td:nth-child(7) { width: 15%; }
    .tm-dash .tm-table th:last-child, .tm-dash .tm-table td:last-child { width: 10%; text-align: center; }
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
        <input type="hidden" name="availability" id="tmAvailability" value="{{ request('availability') }}">
        <input type="hidden" name="zone" id="tmZone" value="{{ request('zone') }}">
        <input type="hidden" name="override" id="tmOverride" value="{{ request('override') }}">

        <div class="tm-filter-wrap">
            <button type="button" class="tm-filter-button" id="tmFilterBtn" aria-haspopup="listbox" aria-expanded="false">
                {{ $statusLabel }}
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 12.5 8 3l6 9.5H2Z"/></svg>
            </button>
            <div class="tm-status-menu" id="tmStatusMenu" role="listbox" aria-label="Filter by availability">
                <button type="button" class="tm-status-option" role="option"
                        aria-selected="{{ request('availability') === '' || request('availability') === null ? 'true' : 'false' }}"
                        data-availability="" data-label="All Status">All Status</button>
                <button type="button" class="tm-status-option" role="option"
                        aria-selected="{{ request('availability') === 'schedule_assignable' ? 'true' : 'false' }}"
                        data-availability="schedule_assignable" data-label="Schedule Assignable">Schedule Assignable</button>
                <button type="button" class="tm-status-option" role="option"
                        aria-selected="{{ request('availability') === 'available' ? 'true' : 'false' }}"
                        data-availability="available" data-label="Available">Available</button>
                <button type="button" class="tm-status-option" role="option"
                        aria-selected="{{ request('availability') === 'unavailable' ? 'true' : 'false' }}"
                        data-availability="unavailable" data-label="Unavailable">Unavailable</button>
                <button type="button" class="tm-status-option" role="option"
                        aria-selected="{{ request('availability') === 'force_available' ? 'true' : 'false' }}"
                        data-availability="force_available" data-label="Force Available">Force Available</button>
                <button type="button" class="tm-status-option" role="option"
                        aria-selected="{{ request('availability') === 'force_unavailable' ? 'true' : 'false' }}"
                        data-availability="force_unavailable" data-label="Force Unavailable">Force Unavailable</button>
            </div>
        </div>

        <div class="tm-filter-wrap" style="flex: 0 0 150px;">
            <select name="zone" id="tmZoneSelect" class="form-select form-select-sm" onchange="document.getElementById('tmZone').value = this.value; document.getElementById('tmForm').submit();">
                <option value="">All Zones</option>
                @foreach ($zones as $zone)
                    <option value="{{ $zone->id }}" {{ request('zone') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="tm-filter-wrap" style="flex: 0 0 150px;">
            <select name="override" id="tmOverrideSelect" class="form-select form-select-sm" onchange="document.getElementById('tmOverride').value = this.value; document.getElementById('tmForm').submit();">
                <option value="">All Overrides</option>
                <option value="auto" {{ request('override') === 'auto' ? 'selected' : '' }}>Auto</option>
                <option value="force_available" {{ request('override') === 'force_available' ? 'selected' : '' }}>Force Available</option>
                <option value="force_unavailable" {{ request('override') === 'force_unavailable' ? 'selected' : '' }}>Force Unavailable</option>
            </select>
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
        <div class="pym-dash pym-chips" style="margin-top: 12px;">
            @foreach ($activeFilters as $chip)
                <a href="{{ $chip['href'] }}" class="pym-dash pym-chip">
                    {{ $chip['label'] }}
                    <span class="pym-chip__x" aria-hidden="true">
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
                    <th scope="col">Enforcer</th>
                    <th scope="col">Badge ID</th>
                    <th scope="col">Zone/Team</th>
                    <th scope="col">Today's Shift</th>
                    <th scope="col">Status</th>
                    <th scope="col">Override</th>
                    <th scope="col">Open Reports</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
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
    const menu = document.getElementById('tmStatusMenu');
    const availabilityInput = document.getElementById('tmAvailability');
    const searchInput = document.getElementById('tmSearchInput');
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
        if (!event.target.closest('.tm-filter-wrap')) closeFilter();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeFilter();
    });

    menu.addEventListener('click', (event) => {
        const option = event.target.closest('[data-availability]');
        if (!option) return;
        availabilityInput.value = option.dataset.availability;
        menu.querySelectorAll('[data-availability]').forEach(el => el.setAttribute('aria-selected', String(el === option)));
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