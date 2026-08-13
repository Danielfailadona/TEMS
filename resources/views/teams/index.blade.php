@extends('layouts.app')

@section('title', 'Team Management')

@push('styles')
<style>
    .stat-card-sm {
        flex: 1;
        min-width: 140px;
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        background: var(--itevcms-card);
        border: 1px solid var(--itevcms-border);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stat-card-sm:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06); }
    .stat-card-sm .stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1.2; }
    .stat-card-sm .stat-label {
        font-size: 0.7rem;
        color: var(--itevcms-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 600;
    }
    .filter-bar { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
    .filter-bar .form-select,
    .filter-bar .form-control {
        font-size: 0.8rem;
        padding: 0.4rem 0.7rem;
        border-radius: 0.5rem;
        min-width: 130px;
    }
    .filter-bar .form-control { min-width: 200px; }
    .team-card { transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .team-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06); }
    .avatar-stack { display: inline-flex; align-items: center; }
    .avatar-stack > .avatar-stack-item { margin-left: -8px; border: 2px solid var(--itevcms-card); }
    .avatar-stack > .avatar-stack-item:first-child { margin-left: 0; }
    .dashed-create-card {
        border: 2px dashed var(--itevcms-border);
        border-radius: 0.75rem;
        background: transparent;
        transition: all 0.15s ease;
        cursor: pointer;
        min-height: 100%;
    }
    .dashed-create-card:hover {
        border-color: var(--bs-primary);
        background: rgba(37, 99, 235, 0.04);
        transform: translateY(-2px);
    }
    .empty-state { padding: 3.5rem 1rem; text-align: center; }
    .empty-state i {
        font-size: 3.5rem;
        color: var(--itevcms-text-muted);
        opacity: 0.4;
        display: block;
        margin-bottom: 1rem;
    }
    .min-w-0 { min-width: 0; }
    .team-lead-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.75rem;
        margin-bottom: 0.75rem;
        border-radius: 0.6rem;
        background: rgba(245, 158, 11, 0.08);
        border: 1px solid rgba(245, 158, 11, 0.25);
    }
    .team-lead-avatar {
        position: relative;
        flex-shrink: 0;
    }
    .team-lead-avatar .avatar-initial {
        width: 46px;
        height: 46px;
        border: 2px solid #f59e0b;
    }
    .team-lead-star {
        position: absolute;
        bottom: -3px;
        right: -3px;
        color: #f59e0b;
        font-size: 1rem;
        background: var(--itevcms-card);
        border-radius: 50%;
        padding: 1px;
        line-height: 1;
    }
    .team-lead-label {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #b45309;
    }
    .team-no-lead {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin-bottom: 0.75rem;
        border-radius: 0.6rem;
        border: 1px dashed var(--itevcms-border);
        background: transparent;
        font-size: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 animate-on-load">
    <p class="text-muted mb-0">Group enforcers by patrol unit and assign coverage leaders.</p>
    <a href="{{ route('teams.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Create Team
    </a>
</div>

{{-- Stat Strip --}}
<div class="d-flex gap-3 mb-4 flex-wrap animate-on-load">
    <div class="stat-card-sm">
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">Total Teams</div>
    </div>
    <div class="stat-card-sm">
        <div class="stat-value" style="color:#059669;">{{ $stats['active'] }}</div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card-sm">
        <div class="stat-value" style="color:#2563eb;">{{ $stats['members'] }}</div>
        <div class="stat-label">Enforcers in Teams</div>
    </div>
    <div class="stat-card-sm">
        <div class="stat-value" style="color:#d97706;">{{ $stats['citations_this_month'] }}</div>
        <div class="stat-label">Citations This Month</div>
    </div>
</div>

{{-- Filter Bar --}}
<form method="GET" action="{{ route('teams.index') }}" class="mb-4 animate-on-load">
    <div class="filter-bar">
        <input type="text" name="search" class="form-control" placeholder="Search by name or description..." value="{{ request('search') }}">
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <div class="form-check d-flex align-items-center gap-2 ms-1" style="font-size:0.8rem;padding-left:1.5em;">
            <input type="checkbox" name="has_zones" value="1" id="has-zones" class="form-check-input" {{ request('has_zones') ? 'checked' : '' }}>
            <label for="has-zones" class="form-check-label mb-0">Has zones</label>
        </div>
        <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-funnel me-1"></i>Filter
        </button>
        @if($hasFilters)
            <a href="{{ route('teams.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i>Clear
            </a>
        @endif
    </div>
</form>

{{-- Teams Grid --}}
<div class="row g-4">
    @forelse ($teams as $team)
        @php
            $palette = ['primary', 'success', 'warning', 'danger', 'info', 'secondary', 'dark'];
            $teamInitial = strtoupper(substr($team->name, 0, 1));
            $teamColor = $palette[crc32($team->name) % count($palette)];
            $zoneCount = (int) ($zoneCounts[$team->id] ?? 0);
            $citationCount = (int) ($citationByTeam[$team->id] ?? 0);
            $memberTotal = $team->members->count();
            $visibleMembers = $team->members->take(4);
            $extraCount = max(0, $memberTotal - 4);
            $memberNames = $team->members->pluck('name')->take(6)->implode(', ') . ($memberTotal > 6 ? '...' : '');
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card stat-card team-card h-100 animate-on-load">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                        <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                            <span class="avatar-initial rounded-circle bg-{{ $teamColor }} bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">
                                <span class="fw-bold text-{{ $teamColor }}">{{ $teamInitial }}</span>
                            </span>
                            <h5 class="mb-0 text-truncate" title="{{ $team->name }}">{{ $team->name }}</h5>
                        </div>
                        <span class="badge {{ $team->is_active ? 'bg-success' : 'bg-secondary' }} flex-shrink-0">{{ $team->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>

                    <p class="text-muted small mb-3" style="min-height:2.5rem;">
                        {{ $team->description ? \Illuminate\Support\Str::limit($team->description, 90) : 'No description provided.' }}
                    </p>

                    {{-- Lead --}}
                    @if($team->leader)
                        @php
                            $leadInitial = strtoupper(substr($team->leader->name, 0, 1));
                            $leadColor = $palette[crc32($team->leader->email) % count($palette)];
                        @endphp
                        <div class="team-lead-card">
                            <div class="team-lead-avatar">
                                <span class="avatar-initial rounded-circle bg-{{ $leadColor }} bg-opacity-10 d-inline-flex align-items-center justify-content-center" title="Lead: {{ $team->leader->name }}">
                                    <span class="fw-bold text-{{ $leadColor }}" style="font-size:1.05rem;">{{ $leadInitial }}</span>
                                </span>
                                <i class="bi bi-star-fill team-lead-star"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="team-lead-label">Team Lead</div>
                                <div class="fw-bold text-truncate" title="{{ $team->leader->name }}">{{ $team->leader->name }}</div>
                                <div class="text-muted small text-truncate" title="{{ $team->leader->email }}">{{ $team->leader->email }}</div>
                            </div>
                        </div>
                    @else
                        <div class="team-no-lead text-muted">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            <span>No lead assigned yet</span>
                        </div>
                    @endif

                    {{-- Member avatar stack --}}
                    <div class="d-flex align-items-center gap-2 mb-3" title="{{ $memberNames }}">
                        <div class="avatar-stack">
                            @foreach($visibleMembers as $member)
                                @php
                                    $mInitial = strtoupper(substr($member->name, 0, 1));
                                    $mColor = $palette[crc32($member->email) % count($palette)];
                                @endphp
                                <span class="avatar-initial avatar-stack-item rounded-circle bg-{{ $mColor }} bg-opacity-25 d-inline-flex align-items-center justify-content-center" style="width:26px;height:26px;" title="{{ $member->name }}">
                                    <span class="fw-bold text-{{ $mColor }}" style="font-size:0.7rem;">{{ $mInitial }}</span>
                                </span>
                            @endforeach
                            @if($extraCount > 0)
                                <span class="avatar-initial avatar-stack-item rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width:26px;height:26px;" title="+{{ $extraCount }} more">
                                    <span class="fw-bold text-muted" style="font-size:0.65rem;">+{{ $extraCount }}</span>
                                </span>
                            @endif
                        </div>
                        <span class="text-muted small">
                            {{ $memberTotal }} {{ \Illuminate\Support\Str::plural('member', $memberTotal) }}
                        </span>
                    </div>

                    {{-- Footer metrics --}}
                    <div class="d-flex flex-wrap gap-3 small text-muted border-top pt-3 mt-auto mb-3">
                        <span><i class="bi bi-geo-alt me-1"></i>{{ $zoneCount }} {{ \Illuminate\Support\Str::plural('zone', $zoneCount) }}</span>
                        <span><i class="bi bi-receipt me-1"></i>{{ $citationCount }} {{ \Illuminate\Support\Str::plural('citation', $citationCount) }}/mo</span>
                        <span title="{{ $team->updated_at }}"><i class="bi bi-clock-history me-1"></i>{{ $team->updated_at->diffForHumans() }}</span>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('teams.edit', $team) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                        <form action="{{ route('teams.toggle-active', $team) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-{{ $team->is_active ? 'warning' : 'success' }}" title="{{ $team->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="bi bi-{{ $team->is_active ? 'pause-fill' : 'play-fill' }}"></i>
                            </button>
                        </form>
                        <form action="{{ route('teams.destroy', $team) }}" method="POST" class="d-inline ms-auto"
                              onsubmit="return confirm('Delete team {{ $team->name }}?\n\nAssigned zones will be released and members detached.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete team">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card stat-card">
                <div class="card-body empty-state">
                    <i class="bi bi-people"></i>
                    @if($hasFilters)
                        <h4 class="mb-2">No teams match your filters</h4>
                        <p class="text-muted mb-3">Try adjusting the search or clearing the filters</p>
                        <a href="{{ route('teams.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-x-lg me-1"></i>Clear filters
                        </a>
                        <a href="{{ route('teams.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Create Team
                        </a>
                    @else
                        <h4 class="mb-2">No teams yet</h4>
                        <p class="text-muted mb-3">Create your first team to start organizing enforcers by patrol unit</p>
                        <a href="{{ route('teams.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Create Team
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endforelse

    @if($teams->isNotEmpty())
        <div class="col-md-6 col-xl-4">
            <a href="{{ route('teams.create') }}" class="card stat-card dashed-create-card h-100 d-flex align-items-center justify-content-center text-decoration-none" style="min-height:280px;">
                <div class="card-body text-center">
                    <i class="bi bi-plus-circle" style="font-size:2.5rem;color:var(--itevcms-text-muted);"></i>
                    <div class="mt-2 fw-semibold text-muted">Create another team</div>
                </div>
            </a>
        </div>
    @endif
</div>
@endsection
