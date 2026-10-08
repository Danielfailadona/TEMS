@extends('layouts.app')

@section('title', 'Zone Management')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
<style>
    main.page-bg {
        padding: 0 !important;
        background-color: #081b2c !important;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    main.page-bg > .alert { margin: 1rem; }

    .zone-console {
        flex: 1 1 auto;
        min-height: 0;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 303px;
    }

    /* ---------- MAP PANE ---------- */
    .zone-map-pane {
        position: relative;
        min-width: 0;
        overflow: hidden;
        background: #1b5271;
        border-right: 1px solid #234966;
    }
    .zone-map-container {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border-radius: 0;
    }
    .empty-map-state {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #8197ad;
        text-align: center;
        padding: 1.5rem;
    }
    .empty-map-state i { font-size: 3rem; opacity: 0.4; }
    .empty-map-state .fw-semibold { color: #eef5fb; }

    /* ---------- RIGHT PANEL ---------- */
    .zone-panel {
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: #10283e;
        border-left: 1px solid #183b56;
        padding: 24px 18px 18px;
        overflow: hidden;
    }
    .zone-panel-head {
        display: flex;
        flex-direction: column;
    }
    .upper-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .lower-header { margin-top: 4px; }
    .zone-panel-title {
        font-size: 1rem;
        font-weight: 750;
        color: #eef5fb;
        line-height: 1.2;
    }
    .zone-panel-desc {
        font-size: 0.68rem;
        color: #8297aa;
        margin-top: 4px;
    }
    .zone-create-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        flex-shrink: 0;
        border: 0;
        background: #2d76e8;
        color: #fff;
        border-radius: 0.35rem;
        padding: 0.45rem 0.7rem;
        font-size: 0.72rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.12s ease;
    }
    .zone-create-btn:hover { background: #3a87fa; color: #fff; }

    /* Status box */
    .zone-status-box {
        margin-top: 18px;
        border: 1px solid #214866;
        border-radius: 9px;
        background: #0c2236;
        padding: 11px;
    }
    .zone-status-heading {
        font-size: 0.72rem;
        font-weight: 700;
        color: #eef5fb;
        margin-bottom: 11px;
    }
    .zone-status-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 7px;
    }
    .zone-stat {
        border: 1px solid #244967;
        border-radius: 7px;
        min-height: 65px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #0d263c;
        color: #c4ced7;
        text-decoration: none;
        transition: border-color 0.12s ease, background 0.12s ease;
    }
    .zone-stat:hover { border-color: #3a78aa; background: #102c44; text-decoration: none; }
    .zone-stat-num {
        font-size: 1.55rem;
        line-height: 1.25;
        color: #2db9f4;
        font-weight: 750;
    }
    .zone-stat-num.level-ok { color: #13a956; }
    .zone-stat-num.level-warn { color: #f2b63f; }
    .zone-stat-num.level-danger { color: #ed4448; }
    .zone-stat-label { font-size: 0.6rem; color: #c4ced7; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.03em; }

    /* Tools row */
    .zone-tools {
        display: flex;
        margin-top: 18px;
        height: 32px;
    }
    .zone-tools form { display: flex; flex: 1; min-width: 0; }
    .zone-search {
        flex: 1;
        min-width: 0;
        border: 1px solid #244967;
        border-radius: 6px 0 0 6px;
        background: #0c2237;
        color: #b7c5d0;
        padding: 0 10px;
        font-size: 0.78rem;
        outline: none;
        width: 100%;
    }
    .zone-search:focus { border-color: #3a78aa; }
    .zone-search::placeholder { color: #71859a; }
    .filter-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-width: 84px;
        padding: 0 12px;
        border: 1px solid #244967;
        border-left: 0;
        border-radius: 0 6px 6px 0;
        background: #0d263c;
        color: #d7e0e7;
        font-size: 0.72rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.12s ease;
    }
    .filter-trigger:hover { background: #173651; }
    .filter-trigger.is-active { background: #2d76e8; border-color: #2d76e8; color: #fff; }
    .filter-clear { opacity: 0.85; flex-shrink: 0; color: #fff; text-decoration: none; }

    .zone-count {
        text-align: right;
        color: #7d91a5;
        font-size: 0.7rem;
        margin-top: 14px;
        margin-bottom: 8px;
    }

    /* Zone cards */
    .zone-list {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
        overflow-y: auto;
        padding-right: 2px;
    }
    .zone-list::-webkit-scrollbar { width: 5px; }
    .zone-list::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.3); border-radius: 4px; }
    .zone-card {
        flex-shrink: 0;
        border: 1px solid #1e4663;
        border-radius: 8px;
        background: #0c2338;
        padding: 12px;
        cursor: pointer;
        transition: border-color 0.12s ease, background 0.12s ease;
    }
    .zone-card:hover { border-color: #2f6485; background: #0e2740; }
    .zone-card.is-highlighted { border-color: #2f8cff; box-shadow: inset 0 0 0 1px #2f8cff; }
    .zone-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
    .zone-card-head > div { min-width: 0; }
    .zone-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: #eef5fb;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .zone-name i { color: #2f8cff; flex-shrink: 0; }
    .zone-desc {
        font-size: 0.65rem;
        color: #8095a9;
        margin-top: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .zone-state {
        flex-shrink: 0;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 999px;
        background: #124f31;
        color: #3be17d;
    }
    .zone-state.inactive { background: #283746; color: #9aa9b7; }
    .zone-card-bottom { display: flex; align-items: center; gap: 6px; margin-top: 14px; }
    .zone-team-badge {
        font-size: 0.62rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 999px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 110px;
    }
    .zone-team-badge.unassigned { background: #4c1719; color: #ff5656; }
    .zone-radius {
        font-size: 0.68rem;
        color: #b7c5d0;
        white-space: nowrap;
    }
    .zone-card-actions {
        margin-left: auto;
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }
    .zone-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 26px;
        padding: 0;
        border: 1px solid #234967;
        border-radius: 5px;
        background: #173653;
        color: #d8e3eb;
        cursor: pointer;
        font-size: 0.78rem;
        text-decoration: none;
        transition: background 0.12s ease;
    }
    .zone-action-btn:hover { background: #214764; color: #fff; }
    .zone-action-btn.delete { background: #4c1719; border-color: #822728; color: #ff7777; }
    .zone-action-btn.delete:hover { background: #5d1d1f; }

    .zone-list-empty {
        padding: 2rem 0;
        text-align: center;
        color: #8197ad;
        font-size: 0.75rem;
    }

    /* ---------- FILTER POPOVER ---------- */
    .filters-wrap { position: relative; display: flex; }
    .filters-popover {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        z-index: 120;
        width: 255px;
        background: #10283e;
        border: 1px solid #4a6984;
        border-radius: 8px;
        padding: 20px 22px;
        box-shadow: 0 16px 32px rgba(0, 0, 0, 0.38);
        display: none;
    }
    .filters-popover.open { display: block; }
    .select {
        position: relative;
        width: 100%;
        height: 42px;
        border: 1px solid #52708a;
        border-radius: 6px;
        background: #0d2338;
        color: #eef5fb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 14px;
        font-size: 0.78rem;
        cursor: pointer;
        user-select: none;
    }
    .select + .select { margin-top: 20px; }
    .select:hover { border-color: #6b8ca7; }
    .select-arrow { color: #71859a; font-size: 0.8rem; }
    .menu {
        position: absolute;
        left: 0;
        top: calc(100% + 2px);
        width: 100%;
        background: #10283e;
        border: 1px solid #4b6c87;
        border-radius: 6px;
        overflow: hidden;
        display: none;
        z-index: 130;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.35);
    }
    .menu.open { display: block; }
    .menu-item {
        width: 100%;
        height: 38px;
        border: 0;
        border-bottom: 1px solid #476681;
        background: #10283e;
        color: #eef5fb;
        text-align: left;
        padding: 0 14px;
        font-size: 0.78rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .menu-item:last-child { border-bottom: 0; }
    .menu-item:hover { background: #173852; }
    .menu-item.is-selected { color: #27b9f5; font-weight: 600; }
    .menu-item .filter-check { opacity: 0; }
    .menu-item.is-selected .filter-check { opacity: 1; }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 1199.98px) {
        .zone-console { grid-template-columns: minmax(0, 1fr) 285px; }
    }
    @media (max-width: 991.98px) {
        main.page-bg { overflow: auto; }
        .zone-console {
            grid-template-columns: 1fr;
            grid-template-rows: minmax(500px, 60vh) auto;
        }
        .zone-map-pane { border-right: 0; border-bottom: 1px solid #234966; }
        .zone-panel { border-left: 0; border-top: 1px solid #183b56; min-height: 60vh; }
        .filters-popover { position: fixed; right: 15px; top: 85px; }
    }
</style>
@endpush

@section('content')
@php
    $chipBase = array_filter(request()->only(['search', 'team_id']), fn ($v) => $v !== null && $v !== '');
    $curStatus = request('status');
    $curAssignment = request('assignment');
    $anyFilter = request()->anyFilled(['search', 'status', 'team_id', 'assignment']);
    $selectedTeam = $teams->firstWhere('id', request('team_id'));
    $teamLabel = $selectedTeam?->name ?? 'All Teams';
    $statusOptions = [
        'All' => route('zones.index', array_merge($chipBase, ['status' => null, 'assignment' => null])),
        'Active' => route('zones.index', array_merge($chipBase, ['status' => 'active', 'assignment' => null])),
        'Inactive' => route('zones.index', array_merge($chipBase, ['status' => 'inactive', 'assignment' => null])),
        'Assigned' => route('zones.index', array_merge($chipBase, ['status' => null, 'assignment' => 'assigned'])),
        'Unassigned' => route('zones.index', array_merge($chipBase, ['status' => null, 'assignment' => 'unassigned'])),
    ];
    $statusLabel = $curAssignment === 'assigned' ? 'Assigned'
        : ($curAssignment === 'unassigned' ? 'Unassigned'
        : ($curStatus === 'active' ? 'Active'
        : ($curStatus === 'inactive' ? 'Inactive' : 'All')));
    $unassignedCount = (int) $stats['unassigned'];
    $unassignedLevel = $unassignedCount <= 2 ? 'level-ok' : ($unassignedCount <= 5 ? 'level-warn' : 'level-danger');
@endphp

<div class="zone-console">
    <div class="zone-map-pane">
        @if ($zones->isEmpty())
            <div class="empty-map-state">
                <i class="bi bi-map"></i>
                <span class="fw-semibold">No zones found</span>
                <small>
                    @if ($anyFilter)
                        No zones match the current filters.
                    @else
                        Create your first zone to see it here.
                    @endif
                </small>
                @if ($anyFilter)
                    <a href="{{ route('zones.index') }}" class="zone-create-btn mt-2">
                        <i class="bi bi-x-lg"></i>Clear filters
                    </a>
                @endif
            </div>
        @else
            <div id="zone-index-map" class="zone-map-container"></div>
        @endif
    </div>

    <aside class="zone-panel">
        <div class="zone-panel-head">
            <div class="upper-header">
                <div class="zone-panel-title">Zones</div>
                <a href="{{ route('zones.create') }}" class="zone-create-btn">
                    Create Zone<i class="bi bi-plus-lg"></i>
                </a>
            </div>
            <div class="lower-header">
                <div class="zone-panel-desc">Define patrol zones and assign them to response teams.</div>
            </div>
        </div>

        {{-- Zone Status stats (clickable filters, like the old top cards) --}}
        <section class="zone-status-box" aria-label="Zone status">
            <div class="zone-status-heading">Zone Status</div>
            <div class="zone-status-grid">
                <a href="{{ route('zones.index', array_merge($chipBase, ['assignment' => 'assigned'])) }}" class="zone-stat text-decoration-none" title="View assigned zones">
                    <div class="zone-stat-num">{{ $stats['assigned'] }}</div>
                    <div class="zone-stat-label">Assigned</div>
                </a>
                <a href="{{ route('zones.index', $chipBase) }}" class="zone-stat text-decoration-none" title="View all zones">
                    <div class="zone-stat-num">{{ $stats['teams'] }}</div>
                    <div class="zone-stat-label">Teams</div>
                </a>
                <a href="{{ route('zones.index', array_merge($chipBase, ['assignment' => 'unassigned'])) }}" class="zone-stat text-decoration-none" title="View unassigned zones">
                    <div class="zone-stat-num unassigned {{ $unassignedLevel }}">{{ $stats['unassigned'] }}</div>
                    <div class="zone-stat-label">Unassigned</div>
                </a>
            </div>
        </section>

        {{-- Tools: search + filters --}}
        <div class="zone-tools">
            <form method="GET" action="{{ route('zones.index') }}" role="search">
                <input type="text" name="search" class="zone-search" placeholder="Search zones..."
                       value="{{ request('search') }}" aria-label="Search zones">
            </form>

            <div class="filters-wrap" id="zone-filters">
                <div role="button" tabindex="0" class="filter-trigger {{ $anyFilter ? 'is-active' : '' }}" id="filter-trigger-btn" aria-expanded="false">
                    <i class="bi bi-funnel-fill"></i>Filters
                    @if ($anyFilter)
                        <a href="{{ route('zones.index') }}" class="filter-clear" title="Clear filters" onclick="event.stopPropagation();">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>

                <div class="filters-popover" id="filters-popover" role="dialog" aria-label="Filters">
                    <div class="select" id="team-filter-row">
                        <span>All Teams</span>
                        <span style="display:flex;align-items:center;gap:8px;color:#71859a;">
                            <span id="team-filter-value" style="color:#eef5fb;">{{ $teamLabel }}</span>
                            <span class="select-arrow">⌄</span>
                        </span>
                        <div class="menu" id="team-filter-dropdown">
                            <a href="{{ route('zones.index', array_merge($chipBase, ['team_id' => null])) }}" class="menu-item {{ request('team_id') === null ? 'is-selected' : '' }}">
                                <span>All Teams</span><i class="bi bi-check-lg filter-check"></i>
                            </a>
                            @foreach ($teams as $team)
                                <a href="{{ route('zones.index', array_merge($chipBase, ['team_id' => $team->id])) }}" class="menu-item {{ (string)$team->id === request('team_id') ? 'is-selected' : '' }}">
                                    <span>{{ $team->name }}</span><i class="bi bi-check-lg filter-check"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="select" id="status-filter-row">
                        <span>Zone Status</span>
                        <span style="display:flex;align-items:center;gap:8px;color:#71859a;">
                            <span id="status-filter-value" style="color:#eef5fb;">{{ $statusLabel }}</span>
                            <span class="select-arrow">⌄</span>
                        </span>
                        <div class="menu" id="status-filter-dropdown">
                            @foreach ($statusOptions as $label => $href)
                                <a href="{{ $href }}" class="menu-item {{ $statusLabel === $label ? 'is-selected' : '' }}">
                                    <span>{{ $label }}</span><i class="bi bi-check-lg filter-check"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="zone-count">{{ $zones->count() }} zone{{ $zones->count() !== 1 ? 's' : '' }}</div>

        <div class="zone-list" id="zoneList">
            @forelse ($zones as $zone)
                @php $color = $teamColors[$zone->team_id] ?? '#2f8cff'; @endphp
                <div class="zone-card {{ $zone->is_active ? '' : 'is-inactive' }}" data-zone-id="{{ $zone->id }}">
                    <div class="zone-card-head">
                        <div>
                            <div class="zone-name">
                                <i class="bi bi-geo-alt-fill" style="{{ !$zone->is_active ? 'opacity:0.4;' : '' }}"></i>
                                <span style="overflow:hidden;text-overflow:ellipsis;">{{ $zone->name }}</span>
                            </div>
                            <div class="zone-desc">
                                {{ $zone->address ?: ($zone->description ?: '') }}
                            </div>
                        </div>
                        <span class="zone-state {{ $zone->is_active ? '' : 'inactive' }}">{{ $zone->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>

                    <div class="zone-card-bottom">
                        @if ($zone->team)
                            <span class="zone-team-badge" style="background:{{ $color }}33;color:{{ $color }};">
                                <i class="bi bi-people me-1"></i>{{ $zone->team->name }}
                            </span>
                        @else
                            <span class="zone-team-badge unassigned">
                                <i class="bi bi-exclamation-circle me-1"></i>Unassigned
                            </span>
                        @endif
                        <span class="zone-radius"><i class="bi bi-rulers me-1"></i>{{ $zone->radius_m }}m</span>

                        <div class="zone-card-actions">
                            <a href="{{ route('zones.edit', $zone) }}" class="zone-action-btn" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('zones.toggle-active', $zone) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="zone-action-btn" style="color:{{ $zone->is_active ? '#e8b93a' : '#3be17d' }};" title="{{ $zone->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-{{ $zone->is_active ? 'pause-fill' : 'play-fill' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('zones.destroy', $zone) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete zone {{ $zone->name }}?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="zone-action-btn delete" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="zone-list-empty">
                    <i class="bi bi-globe2" style="font-size:2rem;display:block;margin-bottom:0.5rem;opacity:0.4;"></i>
                    <div class="fw-semibold" style="color:#eef5fb;">
                        @if ($anyFilter)
                            No zones match your filters.
                        @else
                            No zones created yet.
                        @endif
                    </div>
                    @if ($anyFilter)
                        <div class="mt-2">
                            <a href="{{ route('zones.index') }}" class="zone-create-btn">
                                <i class="bi bi-x-lg"></i>Clear filters
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </aside>
</div>
@endsection

@push('scripts')
@vite('resources/js/zone-picker.js')
@if ($zones->isNotEmpty())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.__zonePicker?.initZoneViewer) return;
        const zones = @json($mapData);
        const viewer = window.__zonePicker?.initZoneViewer('zone-index-map', {
            zones: zones,
            detailActions: {
                editUrl: (id) => @json(route('zones.edit', ['zone' => '__ID__'])).replace('__ID__', id),
                toggleUrl: (id) => @json(route('zones.toggle-active', ['zone' => '__ID__'])).replace('__ID__', id),
                deleteUrl: (id) => @json(route('zones.destroy', ['zone' => '__ID__'])).replace('__ID__', id),
                canToggle: true,
            },
            onZoneClick: function(zone) {
                document.querySelectorAll('.zone-card').forEach(el => el.classList.remove('is-highlighted'));
                const item = document.querySelector(`.zone-card[data-zone-id="${zone.id}"]`);
                if (item) {
                    item.classList.add('is-highlighted');
                    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        });

        // Click card -> fly to zone on map
        document.querySelectorAll('.zone-card').forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (e.target.closest('form') || e.target.closest('a')) return;
                const zoneId = parseInt(this.dataset.zoneId);
                const zone = zones.find(z => z.id === zoneId);
                if (zone && viewer) {
                    document.querySelectorAll('.zone-card').forEach(el => el.classList.remove('is-highlighted'));
                    this.classList.add('is-highlighted');
                    viewer.handleZone(zone);
                }
            });
        });
    });
</script>
@endif

<script>
    // Filter popover toggle (mirrors demo behavior)
    document.addEventListener('DOMContentLoaded', function () {
        const popover = document.getElementById('filters-popover');
        const trigger = document.getElementById('filter-trigger-btn');
        const teamRow = document.getElementById('team-filter-row');
        const statusRow = document.getElementById('status-filter-row');
        const teamDropdown = document.getElementById('team-filter-dropdown');
        const statusDropdown = document.getElementById('status-filter-dropdown');
        if (!popover || !trigger) return;

        function closeDropdowns() {
            teamDropdown?.classList.remove('open');
            statusDropdown?.classList.remove('open');
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

        teamRow?.addEventListener('click', function (e) {
            e.stopPropagation();
            statusDropdown?.classList.remove('open');
            teamDropdown.classList.toggle('open');
        });

        statusRow?.addEventListener('click', function (e) {
            e.stopPropagation();
            teamDropdown?.classList.remove('open');
            statusDropdown.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#zone-filters')) closeAll();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll();
        });
    });
</script>
@endpush