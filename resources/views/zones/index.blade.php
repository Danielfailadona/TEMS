@extends('layouts.app')

@section('title', 'Zone Management')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
<style>
    .zone-map-container { width:100%; height:100%; min-height:500px; border-radius:0.75rem; overflow:hidden; }
    .stat-card-sm {
        flex:1; padding:1rem 1.25rem; border-radius:0.75rem;
        background:var(--itevcms-card); border:1px solid var(--itevcms-border);
        box-shadow:0 1px 3px rgba(0,0,0,0.04);
        transition:transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stat-card-sm:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,0.06); }
    .stat-card-sm .stat-value { font-size:1.5rem; font-weight:800; line-height:1.2; }
    .stat-card-sm .stat-label { font-size:0.7rem; color:var(--itevcms-text-muted); text-transform:uppercase; letter-spacing:0.04em; font-weight:600; }
    .zone-list-wrapper {
        max-height:500px; overflow-y:auto; border-radius:0.75rem;
        border:1px solid var(--itevcms-border);
    }
    .zone-list-wrapper::-webkit-scrollbar { width:4px; }
    .zone-list-wrapper::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:4px; }
    .zone-list-item {
        padding:0.75rem 1rem; border-bottom:1px solid #f1f5f9;
        cursor:pointer; transition:background 0.12s ease;
        background:var(--itevcms-card);
    }
    .zone-list-item:last-child { border-bottom:none; }
    .zone-list-item:hover { background:#f8fafc; }
    .zone-list-item.is-highlighted { background:#eff6ff; border-left:3px solid #2563eb; padding-left:calc(1rem - 3px); }
    .zone-marker-dot {
        width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:5px;
        border:2px solid white; box-shadow:0 0 0 1px rgba(0,0,0,0.1);
    }
    .filter-bar {
        display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;
    }
    .filter-bar .form-select, .filter-bar .form-control {
        font-size:0.8rem; padding:0.4rem 0.7rem; border-radius:0.5rem; min-width:130px;
    }
    .filter-bar .form-control { min-width:180px; }
    .zone-count-badge {
        font-size:0.65rem; font-weight:600; color:var(--itevcms-text-muted);
        padding:0.2rem 0.5rem; background:#f1f5f9; border-radius:999px;
    }
    .empty-map-state {
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        min-height:500px; color:var(--itevcms-text-muted);
    }
    .empty-map-state i { font-size:3rem; margin-bottom:1rem; opacity:0.4; }
    .min-width-0 { min-width:0; }
    .stat-card-sm.text-decoration-none { color: inherit; }
    .stat-card-sm.text-decoration-none:hover { color: inherit; }
    .stat-card-active { border-color:#2563eb; box-shadow:0 0 0 1px #2563eb inset; }
    .zone-list-item .zone-actions { opacity:0.65; transition:opacity 0.12s ease; }
    .zone-list-item:hover .zone-actions { opacity:1; }
    .zone-action-btn {
        display:inline-flex; align-items:center; justify-content:center;
        width:26px; height:26px; padding:0;
        font-size:0.75rem; border-radius:0.4rem;
        border:1px solid var(--itevcms-border); background:transparent;
        color:var(--itevcms-text-muted);
        transition:background 0.12s ease, color 0.12s ease;
    }
    .zone-action-btn:hover { background:#f1f5f9; }
</style>
@endpush

@section('content')
@php
    $chipBase = array_filter(request()->only(['search', 'team_id']), fn ($v) => $v !== null && $v !== '');
    $curStatus = request('status');
    $curAssignment = request('assignment');
@endphp

{{-- Header (single title lives in the topbar) --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0 small">Define patrol zones and assign them to response teams.</p>
    <a href="{{ route('zones.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Create Zone
    </a>
</div>

{{-- Stat Cards (clickable filters) --}}
<div class="d-flex gap-3 mb-4 flex-wrap">
    <a href="{{ route('zones.index', $chipBase) }}" class="stat-card-sm text-decoration-none {{ $curStatus === null && $curAssignment === null ? 'stat-card-active' : '' }}" title="View all zones">
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">Total Zones</div>
    </a>
    <a href="{{ route('zones.index', array_merge($chipBase, ['assignment' => 'assigned'])) }}" class="stat-card-sm text-decoration-none {{ $curAssignment === 'assigned' ? 'stat-card-active' : '' }}" title="View assigned zones">
        <div class="stat-value" style="color:#059669;">{{ $stats['assigned'] }}</div>
        <div class="stat-label">Assigned</div>
    </a>
    <a href="{{ route('zones.index', array_merge($chipBase, ['assignment' => 'unassigned'])) }}" class="stat-card-sm text-decoration-none {{ $curAssignment === 'unassigned' ? 'stat-card-active' : '' }}" title="View unassigned zones">
        <div class="stat-value" style="color:#dc2626;">{{ $stats['unassigned'] }}</div>
        <div class="stat-label">Unassigned</div>
    </a>
    <a href="{{ route('zones.index', $chipBase) }}" class="stat-card-sm text-decoration-none" title="View all zones">
        <div class="stat-value" style="color:#2563eb;">{{ $stats['teams'] }}</div>
        <div class="stat-label">Teams</div>
    </a>
</div>

{{-- Quick filter chips --}}
<div class="btn-group flex-wrap gap-1 mb-3" role="group" id="zone-chips">
    <a href="{{ route('zones.index', $chipBase) }}" class="btn btn-sm {{ $curStatus === null && $curAssignment === null ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
    <a href="{{ route('zones.index', array_merge($chipBase, ['status' => 'active'])) }}" class="btn btn-sm {{ $curStatus === 'active' && $curAssignment === null ? 'btn-primary' : 'btn-outline-primary' }}">Active</a>
    <a href="{{ route('zones.index', array_merge($chipBase, ['status' => 'inactive'])) }}" class="btn btn-sm {{ $curStatus === 'inactive' && $curAssignment === null ? 'btn-primary' : 'btn-outline-primary' }}">Inactive</a>
    <a href="{{ route('zones.index', array_merge($chipBase, ['assignment' => 'assigned'])) }}" class="btn btn-sm {{ $curAssignment === 'assigned' ? 'btn-primary' : 'btn-outline-primary' }}">Assigned</a>
    <a href="{{ route('zones.index', array_merge($chipBase, ['assignment' => 'unassigned'])) }}" class="btn btn-sm {{ $curAssignment === 'unassigned' ? 'btn-primary' : 'btn-outline-primary' }}">Unassigned</a>
</div>

{{-- Filters --}}
<div class="filter-bar mb-4">
    <form method="GET" action="{{ route('zones.index') }}" class="d-flex gap-2 align-items-center flex-wrap">
        <div class="input-group input-group-sm" style="max-width:220px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search zones..."
                   value="{{ request('search') }}">
        </div>

        <select name="team_id" class="form-select" onchange="this.form.submit()">
            <option value="">All Teams</option>
            @foreach ($teams as $team)
                <option value="{{ $team->id }}" @selected((string)$team->id === request('team_id'))>{{ $team->name }}</option>
            @endforeach
        </select>

        @if (request()->anyFilled(['search', 'status', 'team_id', 'assignment']))
            <a href="{{ route('zones.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </form>

    <span class="zone-count-badge ms-auto">{{ $zones->count() }} zone{{ $zones->count() !== 1 ? 's' : '' }}</span>
</div>

{{-- Map + List --}}
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm overflow-hidden">
            @if ($zones->isEmpty())
                <div class="empty-map-state p-5">
                    <i class="bi bi-map"></i>
                    <span class="fw-semibold">No zones found</span>
                    <small class="text-muted">
                        @if (request()->anyFilled(['search', 'status', 'team_id', 'assignment']))
                            No zones match the current filters.
                        @else
                            Create your first zone to see it here.
                        @endif
                    </small>
                    @if (request()->anyFilled(['search', 'status', 'team_id', 'assignment']))
                        <a href="{{ route('zones.index') }}" class="btn btn-sm btn-outline-secondary mt-3">
                            <i class="bi bi-x-lg me-1"></i>Clear filters
                        </a>
                    @endif
                </div>
            @else
                <div id="zone-index-map" class="zone-map-container"></div>
            @endif
        </div>
    </div>

    <div class="col-lg-5">
        @if ($zones->isEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-globe2" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                    <span class="fw-semibold">
                        @if (request()->anyFilled(['search', 'status', 'team_id', 'assignment']))
                            No zones match your filters.
                        @else
                            No zones created yet.
                        @endif
                    </span>
                    @if (request()->anyFilled(['search', 'status', 'team_id', 'assignment']))
                        <div class="mt-2">
                            <a href="{{ route('zones.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Clear filters
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="zone-list-wrapper shadow-sm" id="zoneList">
                @foreach ($zones as $zone)
                    @php $color = $teamColors[$zone->team_id] ?? '#9ca3af'; @endphp
                    <div class="zone-list-item" data-zone-id="{{ $zone->id }}">
                        <div class="d-flex align-items-start gap-2">
                            <span class="zone-marker-dot" style="background:{{ $color }};{{ !$zone->is_active ? 'opacity:0.35;' : '' }}"></span>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-semibold small" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <i class="bi bi-geo-alt me-1" style="color:{{ $color }};"></i>
                                    {{ $zone->name }}
                                </div>
                                @if ($zone->address)
                                    <div class="text-muted" style="font-size:0.7rem;line-height:1.3;">{{ $zone->address }}</div>
                                @elseif ($zone->description)
                                    <div class="text-muted text-truncate" style="font-size:0.7rem;line-height:1.3;">{{ $zone->description }}</div>
                                @endif
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                @if ($zone->is_active)
                                    <span class="badge bg-success-subtle text-success" style="font-size:0.6rem;font-weight:600;">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.6rem;font-weight:600;">Inactive</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 mt-1">
                            @if ($zone->team)
                                <span class="badge" style="background:{{ $color }};font-size:0.65rem;font-weight:500;">
                                    <i class="bi bi-people me-1"></i>{{ $zone->team->name }}
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger" style="font-size:0.65rem;font-weight:500;">
                                    <i class="bi bi-exclamation-circle me-1"></i>Unassigned
                                </span>
                            @endif
                            <span class="text-muted" style="font-size:0.65rem;">
                                <i class="bi bi-rulers me-1"></i>{{ $zone->radius_m }}m
                            </span>
                        </div>

                        <div class="d-flex gap-1 mt-2 zone-actions">
                            <a href="{{ route('zones.edit', $zone) }}" class="zone-action-btn" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('zones.toggle-active', $zone) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="zone-action-btn" style="color:{{ $zone->is_active ? '#d97706' : '#059669' }};" title="{{ $zone->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-{{ $zone->is_active ? 'pause-fill' : 'play-fill' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('zones.destroy', $zone) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete zone {{ $zone->name }}?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="zone-action-btn" title="Delete">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
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
            onZoneClick: function(zone) {
                document.querySelectorAll('.zone-list-item').forEach(el => el.classList.remove('is-highlighted'));
                const item = document.querySelector(`.zone-list-item[data-zone-id="${zone.id}"]`);
                if (item) {
                    item.classList.add('is-highlighted');
                    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        });

        // Click list item -> fly to zone on map
        document.querySelectorAll('.zone-list-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (e.target.closest('form') || e.target.closest('a')) return;
                const zoneId = parseInt(this.dataset.zoneId);
                const zone = zones.find(z => z.id === zoneId);
                if (zone && viewer) {
                    document.querySelectorAll('.zone-list-item').forEach(el => el.classList.remove('is-highlighted'));
                    this.classList.add('is-highlighted');
                    // Trigger marker click programmatically
                    const markerEl = viewer.markers.find(m => m.zone.id === zoneId);
                    if (markerEl) markerEl.el.click();
                }
            });
        });
    });
</script>
@endif
@endpush
