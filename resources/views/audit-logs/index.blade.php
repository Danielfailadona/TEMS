@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Audit Logs</h1>
    <span class="text-muted small">{{ $activities->total() }} total entries</span>
</div>

<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="log_name" class="form-select">
                    <option value="">All Logs</option>
                    @foreach ($logNames as $name)
                        <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="event" class="form-select">
                    <option value="">All Events</option>
                    @foreach ($events as $event)
                        <option value="{{ $event }}" {{ request('event') === $event ? 'selected' : '' }}>{{ ucfirst($event) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                    @if (request()->anyFilled(['log_name', 'event', 'date_from', 'date_to', 'search']))
                        <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Description</th>
                    <th>Subject</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td class="text-nowrap small">{{ $activity->created_at->format('M d, H:i') }}</td>
                        <td>{{ $activity->causer?->name ?? 'System' }}</td>
                        <td><span class="badge bg-{{ $activity->event === 'created' ? 'success' : ($activity->event === 'updated' ? 'warning' : ($activity->event === 'deleted' ? 'danger' : 'secondary')) }}">{{ $activity->event }}</span></td>
                        <td>{{ $activity->description }}</td>
                        <td class="small text-muted">{{ class_basename($activity->subject_type ?? '') }} #{{ $activity->subject_id ?? '—' }}</td>
                        <td>
                            @if ($activity->properties && $activity->properties->isNotEmpty())
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#log-{{ $activity->id }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @if ($activity->properties && $activity->properties->isNotEmpty())
                        <tr class="collapse" id="log-{{ $activity->id }}">
                            <td colspan="6" class="bg-light small">
                                <pre class="mb-0">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT) }}</pre>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No audit log entries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($activities->hasPages())
        <div class="card-footer bg-white">{{ $activities->links() }}</div>
    @endif
</div>
@endsection
