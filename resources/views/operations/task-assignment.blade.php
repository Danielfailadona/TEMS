@extends('layouts.app')

@section('title', 'Task Assignment')

@section('content')
<div class="mb-4">
    <p class="text-muted mb-0">Distribute citizen reports across available enforcers.</p>
</div>

<form method="POST" action="{{ route('operations.task-assignment.auto-assign-all') }}" class="mb-4">
    @csrf
    <button type="submit" class="btn btn-primary" @disabled($unassigned->isEmpty())>
        <i class="bi bi-magic me-1"></i>Auto-Assign All Unassigned ({{ $unassigned->count() }})
    </button>
</form>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card stat-card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-people me-2"></i>Enforcer Availability</strong>
                <span class="badge bg-secondary rounded-pill">{{ $enforcers->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse ($enforcers as $enforcer)
                    @php
                        $online = (bool) $enforcer->is_online;
                        $available = (bool) $enforcer->is_available;
                        $assignable = $enforcer->isAssignable();
                    @endphp
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                        <div class="min-width-0">
                            <div class="fw-semibold text-truncate">
                                {{ $enforcer->name }}
                                @if ($assignable)
                                    <span class="badge bg-success rounded-pill ms-1" style="font-size:0.6rem;">Ready</span>
                                @endif
                            </div>
                            <small class="text-muted d-block">
                                <i class="bi bi-{{ $online ? 'broadcast text-success' : 'wifi-off text-muted' }} me-1"></i>
                                {{ $online ? 'Online' : 'Offline' }}
                                &middot; {{ $enforcer->open_reports_count }} open
                                @if ($enforcer->enforcerLocation?->last_seen_at)
                                    &middot; seen {{ $enforcer->enforcerLocation->last_seen_at->diffForHumans() }}
                                @endif
                            </small>
                        </div>
                        <form method="POST" action="{{ route('operations.task-assignment.toggle-availability', $enforcer) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $available ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                <i class="bi bi-{{ $available ? 'check-circle' : 'pause-circle' }} me-1"></i>
                                {{ $available ? 'Available' : 'Unavailable' }}
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">No approved enforcers found.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card stat-card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-inbox me-2"></i>Unassigned Reports</strong>
                <span class="badge bg-warning text-dark rounded-pill">{{ $unassigned->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse ($unassigned as $report)
                    <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 border-bottom">
                        <div class="min-width-0">
                            <div class="fw-semibold text-truncate">{{ $report->report_number ?? '—' }} · {{ $report->vehicle_plate }}</div>
                            <small class="text-muted d-block text-truncate">
                                {{ $report->location_address }}
                                &middot; {{ $report->created_at->format('M d, Y') }}
                            </small>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <a href="{{ route('citizen-reports.show', $report) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <form method="POST" action="{{ route('operations.task-assignment.auto-assign', $report) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Auto-assign"><i class="bi bi-magic"></i></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">No unassigned reports.</div>
                @endforelse
            </div>
        </div>

        <div class="card stat-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-clipboard-check me-2"></i>Active Assignments</strong>
                <span class="badge bg-secondary rounded-pill">{{ $assigned->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse ($assigned as $report)
                    <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 border-bottom">
                        <div class="min-width-0">
                            <div class="fw-semibold text-truncate">{{ $report->report_number ?? '—' }} · {{ $report->vehicle_plate }}</div>
                            <small class="text-muted d-block text-truncate">
                                <i class="bi bi-person me-1"></i>{{ $report->assignedTo?->name ?? 'Unassigned' }}
                                &middot; <span class="badge {{ $report->getStatusBadgeClass() }} rounded-pill" style="font-size:0.6rem;">{{ $report->getStatusLabel() }}</span>
                            </small>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0 align-items-center">
                            <form method="POST" action="{{ route('operations.task-assignment.assign', $report) }}" class="d-flex gap-1">
                                @csrf
                                <select name="assigned_to" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                    <option value="">Reassign…</option>
                                    @foreach ($enforcers as $enforcer)
                                        <option value="{{ $enforcer->id }}" @selected($report->assigned_to === $enforcer->id)>{{ $enforcer->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <a href="{{ route('citizen-reports.show', $report) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">No active assignments.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
