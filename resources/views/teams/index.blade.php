@extends('layouts.app')

@section('title', 'Team Management')

@push('styles')
<style>
    /* ===== Team Management — port of Team_Management_HTML_CSS_Demo.html ===== */
    .tm-top {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 26px;
    }
    .tm-kicker {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--itevcms-text-muted, #7f90aa);
        margin-bottom: 6px;
    }
    .tm-title {
        font-size: 1.9rem;
        font-weight: 800;
        line-height: 1.1;
        color: var(--itevcms-text, #18243a);
        margin: 0;
    }
    .tm-desc {
        color: var(--itevcms-text-muted, #5f6f89);
        font-size: 0.95rem;
        margin: 8px 0 0;
    }
    .tm-create {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #2f5fe7;
        color: #fff;
        border-radius: 9px;
        padding: 12px 22px;
        font-size: 0.95rem;
        font-weight: 700;
        text-decoration: none;
        transition: background 0.12s ease;
        white-space: nowrap;
    }
    .tm-create:hover { background: #234fd1; color: #fff; text-decoration: none; }

    /* Stats */
    .tm-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 26px; }
    .tm-stat-card {
        height: 96px;
        background: #fff;
        border: 1px solid #e4e8ee;
        border-radius: 12px;
        padding: 20px 22px;
        box-shadow: 0 1px 2px rgba(20, 35, 60, 0.02);
    }
    .tm-stat-number { font-size: 1.95rem; line-height: 1.15; font-weight: 800; color: #18243a; }
    .tm-stat-number.green { color: #299b3d; }
    .tm-stat-label {
        font-size: 0.66rem;
        font-weight: 750;
        color: #8494ad;
        margin-top: 8px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* Controls: attached search + Filters popover */
    .tm-controls { display: flex; align-items: stretch; margin-bottom: 26px; }
    .tm-controls form { display: flex; flex: 0 1 480px; min-width: 0; }
    .tm-search {
        flex: 1;
        min-width: 0;
        height: 46px;
        border: 1px solid #d9dfe8;
        border-radius: 7px 0 0 7px;
        background: #fff;
        color: #35435a;
        padding: 0 16px;
        font-size: 0.9rem;
        outline: none;
    }
    .tm-search:focus { border-color: #7290ee; }
    .tm-filters { position: relative; display: flex; }
    .tm-filter-trigger {
        height: 46px;
        padding: 0 18px;
        border: 1px solid #d9dfe8;
        border-left: 0;
        border-radius: 0 7px 7px 0;
        background: #fff;
        color: #172239;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
    }
    .tm-filter-trigger:hover { background: #f8f9fb; }
    .tm-filter-trigger.is-active { background: #2f5fe7; border-color: #2f5fe7; color: #fff; }
    .tm-filter-trigger.is-active:hover { background: #2f5fe7; }
    .tm-clear { color: #fff; text-decoration: none; display: inline-flex; margin-left: 2px; }
    .tm-clear:hover { color: #dbe4ff; }
    .tm-popover {
        position: absolute;
        z-index: 40;
        left: 0;
        top: calc(100% + 6px);
        width: 300px;
        background: #fff;
        border: 1px solid #dde1e8;
        border-radius: 9px;
        padding: 16px;
        box-shadow: 0 12px 28px rgba(23, 38, 61, 0.16);
        display: none;
    }
    .tm-popover.open { display: block; }
    .tm-select {
        position: relative;
        height: 46px;
        border: 1px solid #dde1e8;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 16px;
        background: #fff;
        color: #263249;
        font-size: 0.9rem;
        cursor: pointer;
    }
    .tm-select + .tm-check-row { margin-top: 12px; }
    .tm-select:hover { border-color: #b9c5d5; }
    .tm-select-right { display: flex; align-items: center; gap: 8px; color: #7d8fa9; }
    .tm-arrow { font-size: 1.1rem; line-height: 1; }
    .tm-menu {
        position: absolute;
        z-index: 50;
        left: -1px;
        top: calc(100% + 4px);
        width: calc(100% + 2px);
        background: #fff;
        border: 1px solid #dde1e8;
        border-radius: 6px;
        box-shadow: 0 10px 22px rgba(23, 38, 61, 0.16);
        display: none;
        overflow: hidden;
    }
    .tm-menu.open { display: block; }
    .tm-menu-item {
        height: 44px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 16px;
        color: #263249;
        font-size: 0.9rem;
        text-decoration: none;
        border-bottom: 1px solid #eef1f6;
    }
    .tm-menu-item:last-child { border-bottom: 0; }
    .tm-menu-item:hover { background: #f3f6fa; color: #172239; text-decoration: none; }
    .tm-menu-item.is-selected { font-weight: 700; color: #2f5fe7; }
    .tm-menu-item i { color: #2f5fe7; }
    .tm-check-row {
        height: 46px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0 14px;
        border: 1px solid #dde1e8;
        border-radius: 6px;
        color: #263249;
        font-size: 0.9rem;
        text-decoration: none;
        background: #fff;
    }
    .tm-check-row:hover { background: #f8fafd; color: #263249; text-decoration: none; }
    .tm-check {
        width: 19px;
        height: 19px;
        border: 1.5px solid #bdc8d8;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        color: transparent;
        font-size: 0.75rem;
        flex-shrink: 0;
    }
    .tm-check-row.is-checked .tm-check { background: #2f5fe7; border-color: #2f5fe7; color: #fff; }

    /* Team grid + cards */
    .tm-grid { display: grid; grid-template-columns: repeat(3, minmax(280px, 1fr)); gap: 24px; }
    .tm-card {
        height: 100%;
        min-height: 390px;
        background: #fff;
        border: 1px solid #e1e5eb;
        border-radius: 12px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 1px 2px rgba(20, 35, 60, 0.025);
    }
    .tm-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .tm-identity { display: flex; gap: 12px; align-items: flex-start; min-width: 0; }
    .tm-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #e7eaf0;
        color: #68758b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .tm-name-wrap { min-width: 0; }
    .tm-name {
        font-size: 1.15rem;
        font-weight: 750;
        line-height: 1.25;
        color: #18243a;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .tm-desc {
        font-size: 0.8rem;
        color: #8b9ab0;
        margin-top: 7px;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .tm-status {
        padding: 5px 11px;
        border-radius: 13px;
        background: #dff5e8;
        color: #168b4c;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .tm-status.inactive { background: #edf0f3; color: #758398; }
    .tm-warning {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
        min-height: 44px;
        padding: 0 12px;
        border: 1px solid #edae2c;
        border-radius: 9px;
        background: #fff5dc;
        color: #333;
        font-size: 0.8rem;
    }
    .tm-warning i { color: #d99713; font-size: 1.05rem; }
    .tm-lead {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 20px;
        min-height: 76px;
        padding: 12px 14px;
        border: 1px solid #496de7;
        border-radius: 10px;
        background: #7e9cf0;
        color: #fff;
    }
    .tm-lead-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #5275db;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .tm-lead-label { font-size: 0.6rem; font-weight: 750; text-transform: uppercase; opacity: 0.9; }
    .tm-lead-name { font-size: 0.92rem; font-weight: 700; margin-top: 3px; line-height: 1.2; }
    .tm-lead-email {
        font-size: 0.72rem;
        margin-top: 3px;
        opacity: 0.85;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .tm-members {
        margin-top: 16px;
        color: #3f4c62;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 9px;
    }
    .tm-stack { display: flex; }
    .tm-mini {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #e9ecf2;
        color: #69758a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        font-weight: 700;
        margin-left: -6px;
        border: 2px solid #fff;
    }
    .tm-mini:first-child { margin-left: 0; }
    .tm-card-footer { margin-top: auto; padding-top: 14px; border-top: 1px solid #edf0f4; }
    .tm-meta { display: flex; flex-wrap: wrap; gap: 14px; color: #718199; font-size: 0.76rem; margin-bottom: 12px; }
    .tm-meta span { white-space: nowrap; display: inline-flex; align-items: center; gap: 5px; }
    .tm-actions { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .tm-actions form { display: inline-flex; margin: 0; }
    .tm-btn-edit {
        height: 36px;
        padding: 0 16px;
        border: 1.5px solid #2f5fe7;
        border-radius: 8px;
        background: #fff;
        color: #2f5fe7;
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }
    .tm-btn-edit:hover { background: #edf2ff; color: #234fd1; text-decoration: none; }
    .tm-round {
        width: 36px;
        height: 36px;
        border: 1.5px solid #2f5fe7;
        border-radius: 50%;
        background: #fff;
        color: #2f5fe7;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
    }
    .tm-round.play { border-color: #22a85b; color: #22a85b; }
    .tm-round.delete { border-color: #f23449; color: #f23449; }
    .tm-round:hover { background: #f6f8fb; }
    .tm-round.delete:hover { background: #fdf1f2; }
    .tm-create-card {
        height: 100%;
        min-height: 390px;
        border: 2px dashed #cbd3df;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 14px;
        color: #243149;
        text-decoration: none;
        background: transparent;
        transition: all 0.15s ease;
    }
    .tm-create-card:hover {
        border-color: #2f5fe7;
        background: rgba(37, 99, 235, 0.04);
        color: #243149;
        text-decoration: none;
    }
    .tm-plus {
        width: 50px;
        height: 50px;
        border: 1.5px solid #61718a;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.7rem;
        font-weight: 200;
        color: #61718a;
    }
    .tm-empty {
        grid-column: 1 / -1;
        border: 1px solid #e1e5eb;
        border-radius: 12px;
        background: #fff;
        text-align: center;
        padding: 56px 20px;
    }
    .tm-empty i {
        font-size: 3.5rem;
        color: #8494ad;
        opacity: 0.4;
        display: block;
        margin-bottom: 1rem;
    }
    .tm-empty h4 { font-weight: 750; color: #18243a; }
    .tm-empty p { color: #7f90aa; }

    /* Responsive */
    @media (max-width: 1199.98px) {
        .tm-grid { grid-template-columns: repeat(2, minmax(280px, 1fr)); }
    }
    @media (max-width: 991.98px) {
        .tm-stats { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 575.98px) {
        .tm-stats { grid-template-columns: 1fr; }
        .tm-grid { grid-template-columns: 1fr; }
        .tm-controls { flex-wrap: wrap; }
        .tm-controls form { flex-basis: 100%; }
        .tm-filter-trigger {
            margin-top: 8px;
            border: 1px solid #d9dfe8;
            border-radius: 7px;
        }
        .tm-popover { left: auto; right: 0; width: 100%; min-width: 280px; }
    }
</style>
@endpush

@section('content')
@php
    $searchOnly = array_filter(request()->only(['search']), fn ($v) => $v !== null && $v !== '');
    $hasZonesOn = request()->boolean('has_zones');
    $statusBase = $hasZonesOn ? array_merge($searchOnly, ['has_zones' => 1]) : $searchOnly;
    $curStatus = request('status');
    $anyFilter = request()->anyFilled(['search', 'status']) || $hasZonesOn;
    $statusLabel = $curStatus === 'active' ? 'Active' : ($curStatus === 'inactive' ? 'Inactive' : 'All Statuses');
    $statusOptions = [
        'All Statuses' => route('teams.index', array_merge($statusBase, ['status' => null])),
        'Active' => route('teams.index', array_merge($statusBase, ['status' => 'active'])),
        'Inactive' => route('teams.index', array_merge($statusBase, ['status' => 'inactive'])),
    ];
    $zonesToggleHref = $hasZonesOn
        ? route('teams.index', $searchOnly)
        : route('teams.index', array_merge($searchOnly, ['has_zones' => 1]));
@endphp

<div class="tm-top">
    <div>
        <div class="tm-kicker">Administration</div>
        <div class="tm-title">Team Management</div>
        <p class="tm-desc">Group enforcers by patrol unit and assign coverage leaders.</p>
    </div>
    <a href="{{ route('teams.create') }}" class="tm-create"><i class="bi bi-plus-lg"></i>Create Team</a>
</div>

{{-- Stats --}}
<section class="tm-stats" aria-label="Team statistics">
    <div class="tm-stat-card">
        <div class="tm-stat-number">{{ $stats['citations_this_month'] }}</div>
        <div class="tm-stat-label">Citations This Month</div>
    </div>
    <div class="tm-stat-card">
        <div class="tm-stat-number">{{ $stats['total'] }}</div>
        <div class="tm-stat-label">Total Teams</div>
    </div>
    <div class="tm-stat-card">
        <div class="tm-stat-number">{{ $stats['members'] }}</div>
        <div class="tm-stat-label">Enforcers in Teams</div>
    </div>
    <div class="tm-stat-card">
        <div class="tm-stat-number green">{{ $stats['active'] }}</div>
        <div class="tm-stat-label">Active</div>
    </div>
</section>

{{-- Controls: search + Filters popover --}}
<div class="tm-controls">
    <form method="GET" action="{{ route('teams.index') }}" role="search">
        <input type="text" name="search" class="tm-search" placeholder="Search by name or description..." value="{{ request('search') }}" aria-label="Search teams">
    </form>

    <div class="tm-filters" id="tm-filters">
        <div role="button" tabindex="0" class="tm-filter-trigger {{ $anyFilter ? 'is-active' : '' }}" id="tm-filter-btn" aria-expanded="false">
            <i class="bi bi-funnel-fill"></i>Filters
            @if ($anyFilter)
                <a href="{{ route('teams.index') }}" class="tm-clear" title="Clear filters" onclick="event.stopPropagation();"><i class="bi bi-x-lg"></i></a>
            @endif
        </div>

        <div class="tm-popover" id="tm-popover" role="dialog" aria-label="Filters">
            <div class="tm-select" id="tm-status-row">
                <span>Status</span>
                <span class="tm-select-right"><span id="tm-status-value">{{ $statusLabel }}</span><span class="tm-arrow">⌄</span></span>
                <div class="tm-menu" id="tm-status-menu">
                    @foreach ($statusOptions as $label => $href)
                        <a href="{{ $href }}" class="tm-menu-item {{ $statusLabel === $label ? 'is-selected' : '' }}">
                            <span>{{ $label }}</span><i class="bi bi-check-lg"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ $zonesToggleHref }}" class="tm-check-row {{ $hasZonesOn ? 'is-checked' : '' }}">
                <span class="tm-check"><i class="bi bi-check-lg"></i></span>
                <span>Has zones</span>
            </a>
        </div>
    </div>
</div>

{{-- Teams grid --}}
<div class="tm-grid">
    @forelse ($teams as $team)
        @php
            $teamInitial = strtoupper(substr($team->name, 0, 1));
            $zoneCount = (int) ($zoneCounts[$team->id] ?? 0);
            $citationCount = (int) ($citationByTeam[$team->id] ?? 0);
            $memberTotal = $team->members->count();
            $visibleMembers = $team->members->take(4);
            $extraCount = max(0, $memberTotal - 4);
            $memberNames = $team->members->pluck('name')->take(6)->implode(', ') . ($memberTotal > 6 ? '...' : '');
            $leadInitial = $team->leader ? strtoupper(substr($team->leader->name, 0, 1)) : '';
        @endphp
        <article class="tm-card">
            <div class="tm-card-head">
                <div class="tm-identity">
                    <span class="tm-avatar">{{ $teamInitial }}</span>
                    <div class="tm-name-wrap">
                        <div class="tm-name" title="{{ $team->name }}">{{ $team->name }}</div>
                        <div class="tm-desc">{{ $team->description ? \Illuminate\Support\Str::limit($team->description, 90) : 'No description provided.' }}</div>
                    </div>
                </div>
                <span class="tm-status {{ $team->is_active ? '' : 'inactive' }}">{{ $team->is_active ? 'Active' : 'Inactive' }}</span>
            </div>

            @if ($team->leader)
                <div class="tm-lead">
                    <div class="tm-lead-avatar">{{ $leadInitial }}</div>
                    <div class="min-w-0" style="min-width:0;">
                        <div class="tm-lead-label">Team Lead</div>
                        <div class="tm-lead-name" title="{{ $team->leader->name }}">{{ $team->leader->name }}</div>
                        <div class="tm-lead-email" title="{{ $team->leader->email }}">{{ $team->leader->email }}</div>
                    </div>
                </div>
            @else
                <div class="tm-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>No lead assigned yet</span>
                </div>
            @endif

            <div class="tm-members" title="{{ $memberNames }}">
                @if ($memberTotal > 0)
                    <div class="tm-stack">
                        @foreach ($visibleMembers as $member)
                            <span class="tm-mini" title="{{ $member->name }}">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                        @endforeach
                        @if ($extraCount > 0)
                            <span class="tm-mini" title="+{{ $extraCount }} more">+{{ $extraCount }}</span>
                        @endif
                    </div>
                    <span>{{ $memberTotal }} {{ \Illuminate\Support\Str::plural('member', $memberTotal) }}</span>
                @else
                    <span>0 members</span>
                @endif
            </div>

            <div class="tm-card-footer">
                <div class="tm-meta">
                    <span><i class="bi bi-geo-alt"></i>{{ $zoneCount }} {{ \Illuminate\Support\Str::plural('zone', $zoneCount) }}</span>
                    <span><i class="bi bi-receipt"></i>{{ $citationCount }} {{ \Illuminate\Support\Str::plural('citation', $citationCount) }}/mo</span>
                    <span title="{{ $team->updated_at }}"><i class="bi bi-clock-history"></i>{{ $team->updated_at->diffForHumans() }}</span>
                </div>
                <div class="tm-actions">
                    <a href="{{ route('teams.edit', $team) }}" class="tm-btn-edit"><i class="bi bi-pencil"></i>Edit</a>
                    <div style="display:flex;gap:8px;">
                        <form action="{{ route('teams.toggle-active', $team) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tm-round" title="{{ $team->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="bi bi-{{ $team->is_active ? 'pause-fill' : 'play-fill' }}"></i>
                            </button>
                        </form>
                        <form action="{{ route('teams.destroy', $team) }}" method="POST"
                              onsubmit="return confirm('Delete team {{ $team->name }}?\n\nAssigned zones will be released and members detached.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tm-round delete" title="Delete team">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="tm-empty">
            <i class="bi bi-people"></i>
            @if ($hasFilters)
                <h4 class="mb-2">No teams match your filters</h4>
                <p class="text-muted mb-3">Try adjusting the search or clearing the filters</p>
                <a href="{{ route('teams.index') }}" class="btn btn-outline-secondary me-2"><i class="bi bi-x-lg me-1"></i>Clear filters</a>
                <a href="{{ route('teams.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Team</a>
            @else
                <h4 class="mb-2">No teams yet</h4>
                <p class="text-muted mb-3">Create your first team to start organizing enforcers by patrol unit</p>
                <a href="{{ route('teams.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Team</a>
            @endif
        </div>
    @endforelse

    @if ($teams->isNotEmpty())
        <a href="{{ route('teams.create') }}" class="tm-create-card">
            <span class="tm-plus">＋</span>
            <strong>Create another team</strong>
        </a>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Filters popover toggle (mirrors demo behavior, instant GET links)
    document.addEventListener('DOMContentLoaded', function () {
        const popover = document.getElementById('tm-popover');
        const trigger = document.getElementById('tm-filter-btn');
        const statusRow = document.getElementById('tm-status-row');
        const statusMenu = document.getElementById('tm-status-menu');
        if (!popover || !trigger) return;

        function closeDropdowns() {
            statusMenu?.classList.remove('open');
        }
        function closeAll() {
            popover.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
            closeDropdowns();
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const willOpen = !popover.classList.contains('open');
            popover.classList.toggle('open', willOpen);
            trigger.setAttribute('aria-expanded', String(willOpen));
            if (!willOpen) closeDropdowns();
        });

        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                trigger.click();
            }
        });

        statusRow?.addEventListener('click', function (e) {
            e.stopPropagation();
            statusMenu.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#tm-filters')) closeAll();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll();
        });
    });
</script>
@endpush