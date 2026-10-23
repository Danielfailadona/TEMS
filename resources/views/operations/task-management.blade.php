@extends('layouts.app')

@section('title', 'Task Management')

@push('styles')
<style>
    .task-mgmt {
        --tm-blue: #2563eb;
        --tm-blue-soft: #dbeafe;
        --tm-success: #10b981;
        --tm-success-soft: #d1fae5;
        --tm-warning: #f59e0b;
        --tm-warning-soft: #fef3c7;
        --tm-danger: #ef4444;
        --tm-danger-soft: #fee2e2;
        --tm-gray: #6b7280;
        --tm-gray-soft: #f3f4f6;
    }
    .task-mgmt .stat-card {
        background: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(15,23,42,0.05);
        border: 1px solid #e5e7eb;
    }
    .task-mgmt .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .task-mgmt .stat-card .stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }
    .task-mgmt .enforcer-card {
        background: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(15,23,42,0.04);
        border: 1px solid #e5e7eb;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .task-mgmt .enforcer-card:hover {
        box-shadow: 0 8px 24px rgba(15,23,42,0.08);
        transform: translateY(-2px);
    }
    .task-mgmt .enforcer-card .schedule-badge {
        font-size: 0.65rem;
        font-weight: 600;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
    }
    .task-mgmt .override-badge {
        font-size: 0.6rem;
        font-weight: 600;
        padding: 0.15rem 0.45rem;
        border-radius: 999px;
    }
    .task-mgmt .schedule-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0;
        font-size: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .task-mgmt .schedule-row:last-child { border-bottom: none; }
    .task-mgmt .day-cell { min-width: 80px; font-weight: 500; color: #374151; }
    .task-mgmt .time-cell { color: #6b7280; font-family: monospace; font-size: 0.7rem; }
    .task-mgmt .filter-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #fff;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(15,23,42,0.04);
    }
    .task-mgmt .filter-group { display: flex; align-items: center; gap: 0.5rem; }
    .task-mgmt .filter-group label { font-size: 0.75rem; font-weight: 600; color: #374151; white-space: nowrap; }
    .task-mgmt .filter-select {
        min-width: 160px;
        padding: 0.5rem 2.5rem 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E") right 0.5rem center no-repeat;
        background-size: 1.5em 1.5em;
        appearance: none;
    }
    .task-mgmt .filter-select:focus { outline: none; border-color: var(--tm-blue); box-shadow: 0 0 0 3px var(--tm-blue-soft); }
    .task-mgmt .search-input {
        flex: 1;
        min-width: 200px;
        padding: 0.5rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
    }
    .task-mgmt .search-input:focus { outline: none; border-color: var(--tm-blue); box-shadow: 0 0 0 3px var(--tm-blue-soft); }
    .task-mgmt .enforcer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1rem;
    }
    .task-mgmt .enforcer-avatar {
        width: 48px; height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--tm-blue), #0f2b4a);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 1.1rem;
    }
    .task-mgmt .status-indicator {
        width: 10px; height: 10px; border-radius: 50%;
        display: inline-block; margin-right: 0.5rem;
    }
    .task-mgmt .modal-full .modal-dialog { max-width: 900px; }
    .task-mgmt .day-select-group {
        display: inline-flex; border: 1px solid #d1d5db; border-radius: 0.5rem;
        overflow: hidden; background: #f8fafc;
    }
    .task-mgmt .day-select-item { position: relative; }
    .task-mgmt .day-select-check {
        position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none;
    }
    .task-mgmt .day-select-label {
        display: flex; align-items: center; justify-content: center;
        min-width: 52px; padding: 0.5rem 0.75rem; cursor: pointer;
        font-weight: 600; font-size: 0.8125rem; color: #6b7280;
        background: #f8fafc; border-right: 1px solid #e5e7eb;
        transition: background 0.15s ease, color 0.15s ease;
        user-select: none;
    }
    .task-mgmt .day-select-item:last-child .day-select-label { border-right: none; }
    .task-mgmt .day-select-label:hover { background: #eef2f7; color: #374151; }
    .task-mgmt .day-select-check:checked + .day-select-label {
        background: #198754; color: #fff;
    }
    .task-mgmt .day-select-check:focus-visible + .day-select-label {
        outline: 2px solid #198754; outline-offset: -2px;
    }
    .task-mgmt .empty-state { text-align: center; padding: 3rem; color: #9ca3af; }
    .task-mgmt .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }
</style>
@endpush

@section('content')
<div class="task-mgmt">
    <div class="mb-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-clipboard-check me-2"></i>Task Management</h1>
            <p class="text-muted mb-0">Manage enforcer schedules, availability overrides, and task assignments</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#autoAssignModal">
                <i class="bi bi-magic me-1"></i>Auto-Assign All
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="card-body p-3">
                    <div class="stat-label">Total Enforcers</div>
                    <div class="stat-value text-primary">{{ $stats['total_enforcers'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="card-body p-3">
                    <div class="stat-label">Schedule Assignable</div>
                    <div class="stat-value text-success">{{ $stats['schedule_assignable'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="card-body p-3">
                    <div class="stat-label">Force Available</div>
                    <div class="stat-value text-warning">{{ $stats['force_available'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="card-body p-3">
                    <div class="stat-label">Force Unavailable</div>
                    <div class="stat-value text-danger">{{ $stats['force_unavailable'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="filter-toolbar">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center w-100">
            <div class="filter-group flex-grow-1">
                <label for="search" class="visually-hidden">Search</label>
                <input type="search" name="search" id="search" class="search-input"
                       placeholder="Search by name, email, badge ID..."
                       value="{{ request('search') }}">
            </div>

            <div class="filter-group">
                <label for="availability">Availability</label>
                <select name="availability" id="availability" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="schedule_assignable" {{ request('availability') === 'schedule_assignable' ? 'selected' : '' }}>Schedule Assignable</option>
                    <option value="available" {{ request('availability') === 'available' ? 'selected' : '' }}>Available (Manual)</option>
                    <option value="unavailable" {{ request('availability') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                    <option value="force_available" {{ request('availability') === 'force_available' ? 'selected' : '' }}>Force Available</option>
                    <option value="force_unavailable" {{ request('availability') === 'force_unavailable' ? 'selected' : '' }}>Force Unavailable</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="zone">Zone</label>
                <select name="zone" id="zone" class="filter-select">
                    <option value="">All Zones</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" {{ request('zone') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="override">Override</label>
                <select name="override" id="override" class="filter-select">
                    <option value="">All Overrides</option>
                    <option value="auto" {{ request('override') === 'auto' ? 'selected' : '' }}>Auto</option>
                    <option value="force_available" {{ request('override') === 'force_available' ? 'selected' : '' }}>Force Available</option>
                    <option value="force_unavailable" {{ request('override') === 'force_unavailable' ? 'selected' : '' }}>Force Unavailable</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if (request()->hasAny(['search', 'availability', 'zone', 'override']))
                    <a href="{{ route('operations.task-management.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Enforcer Grid --}}
    @if ($enforcers->isNotEmpty())
        <div class="enforcer-grid">
            @foreach ($enforcers as $data)
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
                @endphp
                <div class="enforcer-card">
                    <div class="p-3 border-bottom d-flex align-items-center gap-3">
                        <div class="enforcer-avatar">{{ strtoupper($enforcer->name[0]) }}</div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold text-truncate">{{ $enforcer->name }}</div>
                            <small class="text-muted d-block text-truncate">{{ $enforcer->email }}</small>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-1">
                            <span class="override-badge {{ $overrideClass }}">{{ $overrideText }}</span>
                            <span class="schedule-badge {{ $scheduleStatusClass }}">{{ $scheduleStatusText }}</span>
                        </div>
                    </div>

                    <div class="p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="status-indicator {{ $enforcer->is_online ? 'bg-success' : 'bg-danger' }}"></span>
                            <small class="text-muted">{{ $enforcer->is_online ? 'Online' : 'Offline' }}</small>
                            @if ($enforcer->enforcerLocation && $enforcer->enforcerLocation->last_seen_at)
                                <small class="text-muted">· seen {{ $enforcer->enforcerLocation->last_seen_at->diffForHumans() }}</small>
                            @endif
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block mb-1"><strong>Today's Schedule:</strong></small>
                            @php
                                $today = now()->dayOfWeek;
                                $now = now()->format('H:i:s');
                                $todaysSchedule = $enforcer->enforcerSchedules->filter(function ($s) use ($today, $now) {
                                    return in_array($today, $s->days ?? []) &&
                                           ($s->is_active_days[$today] ?? false) &&
                                           $s->start_time <= $now &&
                                           $s->end_time > $now;
                                })->first();
                            @endphp
                            @if ($todaysSchedule)
                                <div class="d-inline-flex align-items-center gap-2 px-2 py-1 bg-success-soft text-success rounded-pill small">
                                    <i class="bi bi-clock"></i> {{ $todaysSchedule->start_time_short }} - {{ $todaysSchedule->end_time_short }}
                                </div>
                            @else
                                <span class="text-muted small">No shift scheduled</span>
                            @endif
                        </div>

                        <div class="d-flex flex-wrap gap-1">
                            @foreach ($enforcer->teams as $team)
                                <span class="badge bg-info-soft text-info small">{{ $team->name }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-3 border-top bg-gray-soft rounded-bottom d-flex flex-wrap gap-2 justify-content-between">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal-{{ $enforcer->id }}">
                            <i class="bi bi-eye me-1"></i>View Details
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#scheduleModal-{{ $enforcer->id }}">
                            <i class="bi bi-calendar-event me-1"></i>Edit Schedule
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
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <h5>No enforcers found</h5>
            <p class="text-muted">Try adjusting your filters or search terms.</p>
        </div>
    @endif
</div>

{{-- Enforcer Detail Modals --}}
@foreach ($enforcers as $data)
    @php $enforcer = $data['enforcer']; @endphp
    <div class="modal fade" id="detailModal-{{ $enforcer->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-full">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-badge me-2"></i>{{ $enforcer->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><strong class="text-muted d-block">Email</strong>{{ $enforcer->email }}</div>
                        <div class="col-md-6"><strong class="text-muted d-block">Badge ID</strong>{{ $enforcer->badge_id }}</div>
                        <div class="col-md-6"><strong class="text-muted d-block">Phone</strong>{{ $enforcer->phone ?? '—' }}</div>
                        <div class="col-md-6"><strong class="text-muted d-block">Availability Override</strong>
                            <span class="badge {{ match($enforcer->availability_override) { 'force_available' => 'bg-success-soft text-success', 'force_unavailable' => 'bg-danger-soft text-danger', default => 'bg-gray-soft text-gray' } }}">
                                {{ match($enforcer->availability_override) { 'force_available' => 'Force Available', 'force_unavailable' => 'Force Unavailable', default => 'Auto (Schedule)' } }}
                            </span>
                        </div>
                        <div class="col-md-6"><strong class="text-muted d-block">Online Status</strong>
                            <span class="badge {{ $enforcer->is_online ? 'bg-success' : 'bg-danger' }}">{{ $enforcer->is_online ? 'Online' : 'Offline' }}</span>
                        </div>
                        <div class="col-md-6"><strong class="text-muted d-block">Duty Status</strong>
                            <span class="badge {{ $enforcer->duty_state_class }}">{{ $enforcer->duty_state_label }}</span>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-3"><i class="bi bi-calendar-event me-2"></i>Weekly Schedule</h6>
                    @if ($enforcer->enforcerSchedules->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Day</th><th>Start</th><th>End</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    @foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $day)
                                        @php
                                            $sched = $enforcer->enforcerSchedules->filter(function ($s) use ($i) {
                                                return in_array($i, $s->days ?? []) && ($s->is_active_days[$i] ?? false);
                                            })->first();
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $day }}</strong></td>
                                            <td>{{ $sched ? $sched->start_time_short : '—' }}</td>
                                            <td>{{ $sched ? $sched->end_time_short : '—' }}</td>
                                            <td>
                                                @if ($sched)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="text-muted">Off</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">No schedules configured</div>
                    @endif

                    <hr>

                    <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2"></i>Zone Assignment</h6>
                    @if ($enforcer->teams->isNotEmpty())
                        @foreach ($enforcer->teams as $team)
                            <span class="badge bg-info-soft text-info me-1 mb-1">{{ $team->name }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">No zone assigned</span>
                    @endif

                    <hr>

                    <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2"></i>Open Reports</h6>
                    @if ($enforcer->open_reports_count > 0)
                        <p class="text-muted small">{{ $enforcer->open_reports_count }} active report(s) assigned</p>
                    @else
                        <p class="text-muted small">No active reports assigned</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal-{{ $enforcer->id }}" data-bs-dismiss="modal">
                        <i class="bi bi-calendar-event me-1"></i>Edit Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach

{{-- Schedule Editor Modals --}}
@foreach ($enforcers as $data)
    @php $enforcer = $data['enforcer']; @endphp
<div class="modal fade" id="scheduleModal-{{ $enforcer->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="{{ route('operations.task-management.schedule.update', $enforcer) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold"><i class="bi bi-calendar-event me-2"></i>Edit Schedule — {{ $enforcer->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="schedule-editor" id="scheduleEditor-{{ $enforcer->id }}">
                                @php
                                    $dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                                    // Get the enforcer's single schedule
                                    $schedule = $enforcer->enforcerSchedules->first();
                                    $days = $schedule?->days ?? [];
                                    $isActiveDays = $schedule?->is_active_days ?? [];
                                    $startTime = $schedule?->start_time_short ?? '';
                                    $endTime = $schedule?->end_time_short ?? '';
                                @endphp

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Start Time</label>
                                        <input type="time" name="start_time" class="form-control" 
                                               value="{{ $startTime }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">End Time</label>
                                        <input type="time" name="end_time" class="form-control" 
                                               value="{{ $endTime }}" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold small mb-2">Days of Week</label>
                                    <div class="day-select-group" id="dayGroup_{{ $enforcer->id }}">
                                        @php
                                            $dayNamesShort = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                                            $isActiveDays = $schedule?->is_active_days ?? [];
                                        @endphp
                                        @foreach ($dayNamesShort as $dayIdx => $dayShort)
                                            @php $isActive = (bool) ($isActiveDays[$dayIdx] ?? false); @endphp
                                            <div class="day-select-item">
                                                <input type="checkbox" class="day-select-check"
                                                       id="daySelect_{{ $enforcer->id }}_{{ $dayIdx }}"
                                                       {{ $isActive ? 'checked' : '' }}
                                                       onchange="toggleDayPill({{ $dayIdx }}, {{ $enforcer->id }})">
                                                <label class="day-select-label" for="daySelect_{{ $enforcer->id }}_{{ $dayIdx }}">
                                                    {{ $dayShort }}
                                                </label>
                                                <input type="hidden" class="day-input" name="days[]"
                                                       value="{{ $dayIdx }}"
                                                       id="dayInput_{{ $enforcer->id }}_{{ $dayIdx }}"
                                                       {{ $isActive ? '' : 'disabled' }}>
                                                <input type="hidden" class="day-active-input" name="is_active_days[{{ $dayIdx }}]"
                                                       value="1"
                                                       id="dayActiveInput_{{ $enforcer->id }}_{{ $dayIdx }}"
                                                       {{ $isActive ? '' : 'disabled' }}>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted">Select the days this enforcer is on duty.</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Schedule</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    {{-- Auto Assign Modal --}}
    <div class="modal fade" id="autoAssignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('operations.task-management.auto-assign-all') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-magic me-2"></i>Auto-Assign All Unassigned Reports</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">This will automatically assign all unassigned accepted reports to available enforcers based on their schedules and online status.</p>
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle me-1"></i>Only enforcers who are <strong>within their scheduled hours</strong> and <strong>online</strong> will be assigned. If no enforcer is available, reports remain unassigned.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-magic me-1"></i>Auto-Assign All</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Auto-submit filter form on change
    document.querySelectorAll('.filter-toolbar select').forEach(select => {
        select.addEventListener('change', () => select.closest('form').submit());
    });

    // Debounced search
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let debounce;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => searchInput.closest('form').submit(), 300);
        });
    }

    // Schedule editor: day select toggle (segmented buttons)
    window.toggleDayPill = function(dayIdx, enforcerId) {
        const checkbox = document.getElementById('daySelect_' + enforcerId + '_' + dayIdx);
        const dayInput = document.getElementById('dayInput_' + enforcerId + '_' + dayIdx);
        const activeInput = document.getElementById('dayActiveInput_' + enforcerId + '_' + dayIdx);
        if (!checkbox || !dayInput || !activeInput) return;

        dayInput.disabled = !checkbox.checked;
        activeInput.disabled = !checkbox.checked;
    };

    // Schedule editor: time validation (start must be before end)
    document.querySelectorAll('.schedule-editor').forEach(editor => {
        const form = editor.closest('form');
        if (!form) return;
        const start = form.querySelector('input[name="start_time"]');
        const end = form.querySelector('input[name="end_time"]');
        if (!start || !end) return;
        const validate = () => {
            end.setCustomValidity(start.value && end.value && start.value >= end.value
                ? 'End time must be after start time' : '');
        };
        start.addEventListener('change', validate);
        end.addEventListener('change', validate);
    });
});
</script>

@endpush 