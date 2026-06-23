@extends('layouts.app')

@section('title', 'Team Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 animate-on-load">
    <div>
        <h1 class="h3 mb-1">Team Management</h1>
        <p class="text-muted mb-0">Group enforcers by patrol unit and assign coverage leaders.</p>
    </div>
    <a href="{{ route('teams.create') }}" class="btn btn-primary">Create Team</a>
</div>

<div class="row g-4">
    @forelse ($teams as $team)
        <div class="col-lg-6">
            <div class="card stat-card h-100 animate-on-load">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h5 class="mb-1">{{ $team->name }}</h5>
                            <p class="text-muted small mb-0">{{ $team->description ?: 'No description provided.' }}</p>
                        </div>
                        <span class="badge {{ $team->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $team->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="mt-3 small text-muted">
                        <div><strong>Lead:</strong> {{ $team->leader?->name ?? 'Unassigned' }}</div>
                        <div class="mt-2"><strong>Members:</strong> {{ $team->members->count() }}</div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('teams.edit', $team) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card stat-card"><div class="card-body text-muted">No teams created yet.</div></div></div>
    @endforelse
</div>
@endsection
