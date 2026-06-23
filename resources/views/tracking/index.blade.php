@extends('layouts.app')

@section('title', 'GPS Tracking')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
<style>
    html, body { height:100%; }
    .app-shell { height:100vh; display:flex; flex-direction:column; overflow:hidden; }
    .tracking-page {
        position:relative; width:100%; height:100%; display:flex; flex-direction:column;
    }
    .page-bg:has(.tracking-page) { padding:0 !important; }

    #tracking-map {
        position:absolute; inset:0; z-index:0;
    }

    .tracking-overlay-top {
        position:relative; z-index:1; padding:1rem 1.5rem;
        background:linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0) 100%);
        pointer-events:none;
    }
    .tracking-overlay-top > * { pointer-events:auto; }

    .tracking-overlay-bottom {
        position:absolute; bottom:1.5rem; left:1.5rem; z-index:1;
        pointer-events:none;
    }
    .tracking-overlay-bottom > * { pointer-events:auto; }

    .enforcer-sidebar {
        position:absolute; top:1rem; right:1rem; z-index:1;
        width:320px; max-height:calc(100vh - 120px);
        background:rgba(255,255,255,0.95); backdrop-filter:blur(12px);
        border-radius:0.75rem; box-shadow:0 8px 32px rgba(0,0,0,0.12);
        display:flex; flex-direction:column;
        pointer-events:none;
    }
    .enforcer-sidebar > * { pointer-events:auto; }

    .enforcer-sidebar-header {
        padding:0.75rem 1rem; border-bottom:1px solid #f1f5f9;
        display:flex; justify-content:space-between; align-items:center;
        flex-shrink:0;
    }
    .enforcer-sidebar-header h6 { margin:0; font-weight:700; font-size:0.85rem; }

    .enforcer-sidebar-list {
        overflow-y:auto; flex:1; padding:0.25rem 0;
    }
    .enforcer-sidebar-list::-webkit-scrollbar { width:4px; }
    .enforcer-sidebar-list::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:4px; }

    .enforcer-sidebar-item {
        display:flex; align-items:center; gap:0.75rem;
        padding:0.6rem 1rem; cursor:pointer; transition:background 0.1s;
        border-left:3px solid transparent;
    }
    .enforcer-sidebar-item:hover { background:#f8fafc; }
    .enforcer-sidebar-item.is-selected {
        background:#eff6ff; border-left-color:#2563eb;
    }

    .tracking-stats-bar {
        display:flex; gap:1.5rem; align-items:center;
        padding:0.5rem 1rem; background:rgba(255,255,255,0.9);
        backdrop-filter:blur(8px); border-radius:0.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.08);
    }
    .tracking-stats-bar .stat { font-size:0.8rem; }
    .tracking-stats-bar .stat-value { font-weight:700; }
    .tracking-stats-bar .stat-label { color:var(--itevcms-text-muted); font-size:0.7rem; }
</style>
@endpush

@section('content')
<div class="tracking-page">
    <div id="tracking-map"></div>

    {{-- Top overlay --}}
    <div class="tracking-overlay-top">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">GPS Tracking</h1>
                <small class="text-muted">Real-time enforcer location monitoring</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button id="toggle-3d" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-box me-1"></i>3D View
                </button>
                <span class="text-muted small">
                    <span id="enforcer-count">0</span> active
                </span>
            </div>
        </div>
    </div>

    {{-- Enforcer sidebar --}}
    <div class="enforcer-sidebar" id="enforcerSidebar">
        <div class="enforcer-sidebar-header">
            <h6><i class="bi bi-people me-1"></i>Enforcers</h6>
            <button class="btn btn-sm btn-link text-decoration-none p-0" id="toggleSidebar" title="Toggle panel">
                <i class="bi bi-layout-sidebar"></i>
            </button>
        </div>
        <div class="enforcer-sidebar-list" id="enforcer-list">
            <div class="text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Loading...
            </div>
        </div>
    </div>

    {{-- Bottom stats bar --}}
    <div class="tracking-overlay-bottom">
        <div class="tracking-stats-bar" id="enforcer-detail">
            <div class="stat">
                <span class="stat-value text-muted">Select an enforcer</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const map = new maplibregl.Map({
        container: 'tracking-map',
        style: 'https://tiles.openfreemap.org/styles/liberty',
        center: [121.0402, 14.5432],
        zoom: 12,
        attributionControl: false,
    });

    map.addControl(new maplibregl.NavigationControl(), 'top-right');
    map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');

    const state = { enforcers: [], markers: {}, zones: [], selectedEnforcerId: null, is3D: false, refreshTimer: null, sidebarVisible: true };

    const ToggleBtn = document.getElementById('toggleSidebar');
    const Sidebar = document.getElementById('enforcerSidebar');
    ToggleBtn.addEventListener('click', () => {
        state.sidebarVisible = !state.sidebarVisible;
        Sidebar.style.display = state.sidebarVisible ? 'flex' : 'none';
    });

    function createMarker(el, enforcer) {
        return new maplibregl.Marker({ element: el })
            .setLngLat([enforcer.lng, enforcer.lat])
            .addTo(map);
    }

    function renderEnforcers() {
        Object.values(state.markers).forEach(m => m.remove());
        state.markers = {};
        state.enforcers.forEach(e => {
            const el = document.createElement('div');
            const color = e.status === 'active' ? '#22c55e' : '#9ca3af';
            el.innerHTML = `<svg width="36" height="36" viewBox="0 0 24 24" fill="${color}" stroke="white" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" fill="white" font-size="10" font-weight="bold">${e.initials || 'E'}</text></svg>`;
            el.style.cursor = 'pointer';
            el.style.filter = e.id === state.selectedEnforcerId ? 'drop-shadow(0 0 8px rgba(37,99,235,0.8))' : '';
            el.addEventListener('click', () => selectEnforcer(e.id));
            state.markers[e.id] = createMarker(el, e);
        });
    }

    function buildCirclePolygon(lng, lat, radiusM, points = 64) {
        const radiusDeg = radiusM / 111320;
        const coords = [];
        for (let i = 0; i <= points; i++) {
            const angle = (i / points) * 2 * Math.PI;
            const dx = radiusDeg * Math.cos(angle) / Math.cos(lat * Math.PI / 180);
            const dy = radiusDeg * Math.sin(angle);
            coords.push([lng + dx, lat + dy]);
        }
        return coords;
    }

    function renderZones() {
        const existing = map.getSource('zones');
        if (existing) { map.removeLayer('zones-fill'); map.removeLayer('zones-outline'); map.removeSource('zones'); }
        if (state.zones.length === 0) return;
        const features = state.zones.map(z => {
            if (isNaN(z.center_lng) || isNaN(z.center_lat) || isNaN(z.radius_m)) return null;
            return { type: 'Feature', properties: { name: z.name }, geometry: { type: 'Polygon', coordinates: [buildCirclePolygon(z.center_lng, z.center_lat, z.radius_m)] } };
        }).filter(Boolean);
        map.addSource('zones', { type: 'geojson', data: { type: 'FeatureCollection', features } });
        map.addLayer({ id: 'zones-fill', type: 'fill', source: 'zones', paint: { 'fill-color': 'rgba(37, 99, 235, 0.08)', 'fill-outline-color': 'rgba(37, 99, 235, 0.25)' } });
        map.addLayer({ id: 'zones-outline', type: 'line', source: 'zones', paint: { 'line-color': 'rgba(37, 99, 235, 0.4)', 'line-width': 1, 'line-opacity': 0.6, 'line-dasharray': [3, 2] } });
    }

    function selectEnforcer(id) {
        state.selectedEnforcerId = id;
        const e = state.enforcers.find(e => e.id === id);
        if (!e) return;
        renderEnforcers();
        map.flyTo({ center: [e.lng, e.lat], zoom: 15, duration: 800 });

        const detail = document.getElementById('enforcer-detail');
        detail.innerHTML = `
            <div class="stat"><span class="stat-value">${e.name}</span> <span class="stat-label">${e.status === 'active' ? 'Active' : 'Offline'}</span></div>
            <div class="stat"><span class="stat-value">${e.zone_name || '—'}</span> <span class="stat-label">Zone</span></div>
            <div class="stat"><span class="stat-value">${e.team || '—'}</span> <span class="stat-label">Team</span></div>
            <div class="stat"><span class="stat-value">${e.last_seen_label || '—'}</span> <span class="stat-label">Last Seen</span></div>
        `;

        document.querySelectorAll('.enforcer-sidebar-item').forEach(el => el.classList.remove('is-selected'));
        const item = document.querySelector(`.enforcer-sidebar-item[data-id="${id}"]`);
        if (item) item.classList.add('is-selected');
    }

    function updateEnforcerList() {
        const list = document.getElementById('enforcer-list');
        list.innerHTML = state.enforcers.map(e => `
            <div class="enforcer-sidebar-item ${e.id === state.selectedEnforcerId ? 'is-selected' : ''}" data-id="${e.id}" onclick="window._selectEnforcer(${e.id})">
                <span class="rounded-circle d-inline-block flex-shrink-0" style="width:10px;height:10px;background:${e.status === 'active' ? '#22c55e' : '#9ca3af'}"></span>
                <div class="flex-grow-1 min-width-0">
                    <div class="fw-semibold small text-truncate">${e.name}</div>
                    <div class="text-muted" style="font-size:0.65rem;">${e.zone_name || '—'}</div>
                </div>
                <small class="text-muted flex-shrink-0" style="font-size:0.6rem;">${e.distance_km ? e.distance_km + 'km' : ''}</small>
            </div>
        `).join('');
    }

    window._selectEnforcer = selectEnforcer;

    async function fetchData() {
        try {
            const r = await fetch('{{ route("tracking.locations") }}');
            const d = await r.json();
            state.enforcers = d.enforcers || [];
            state.zones = d.zones || [];
            renderEnforcers();
            renderZones();
            updateEnforcerList();
            if (state.selectedEnforcerId) {
                const still = state.enforcers.find(e => e.id === state.selectedEnforcerId);
                if (!still) selectEnforcer(state.enforcers[0]?.id || null);
                else selectEnforcer(state.selectedEnforcerId);
            } else if (state.enforcers.length > 0) { selectEnforcer(state.enforcers[0].id); }
            const countEl = document.getElementById('enforcer-count');
            if (countEl) countEl.textContent = state.enforcers.filter(e => e.status === 'active').length;
        } catch (err) { console.error('Tracking fetch failed:', err); }
    }

    document.getElementById('toggle-3d').addEventListener('click', () => {
        state.is3D = !state.is3D;
        if (state.is3D) { map.easeTo({ pitch: 60, bearing: -30, duration: 1000 }); }
        else { map.easeTo({ pitch: 0, bearing: 0, duration: 1000 }); }
        document.getElementById('toggle-3d').innerHTML = state.is3D ? '<i class="bi bi-box me-1"></i>2D View' : '<i class="bi bi-box me-1"></i>3D View';
    });

    map.on('load', () => { fetchData(); setInterval(fetchData, 15000); });
});
</script>
@endpush
