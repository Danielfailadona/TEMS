@extends('layouts.app')

@section('title', 'GPS Tracking')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
<style>
    html, body { height: 100%; }
    .app-shell { height: 100vh; overflow: hidden; }
    .topbar { display: none !important; }
    .page-bg:has(.tracking-page) { padding: 0 !important; }

    .tracking-page {
        position: relative;
        width: 100%;
        height: 100vh;
        display: flex;
        flex-direction: row;
        overflow: hidden;
        background: #0a1f35;
    }

    .tracking-map-wrap { position: relative; flex: 1 1 auto; min-width: 0; }
    #tracking-map { position: absolute; inset: 0; z-index: 0; }

    .tracking-loading {
        position: absolute; inset: 0; z-index: 5;
        display: flex; align-items: center; justify-content: center;
        background: rgba(10, 31, 53, 0.5);
        opacity: 0; visibility: hidden;
        transition: opacity 0.2s, visibility 0.2s;
    }
    .tracking-loading.is-visible { opacity: 1; visibility: visible; }

    /* ---------- Top-left overlay ---------- */
    .tracking-overlay-top {
        position: absolute; top: 0; left: 0;
        z-index: 2;
        padding: 1rem 1.25rem;
        pointer-events: none;
    }
    .tracking-overlay-top > * { pointer-events: auto; }

    .tracking-info-card {
        background: rgba(10, 31, 53, 0.92);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        backdrop-filter: blur(8px);
        max-width: 340px;
    }
    .tracking-title { color: #fff; font-weight: 800; font-size: 1.2rem; line-height: 1.1; }
    .tracking-subtitle { color: rgba(203, 213, 225, 0.8); font-size: 0.75rem; }

    .tracking-stats-mini {
        display: flex; gap: 1.25rem; margin-top: 0.5rem;
    }
    .tracking-stats-mini .stat { font-size: 0.72rem; color: rgba(203, 213, 225, 0.85); }
    .tracking-stats-mini .stat-value { font-weight: 700; color: #fff; font-size: 0.9rem; }

    /* ---------- Bottom bar: filter chips + controls ---------- */
    .tracking-overlay-bottom {
        position: absolute; bottom: 1.25rem; left: 1.25rem;
        z-index: 2;
        display: flex; align-items: center; gap: 0.6rem;
        flex-wrap: wrap;
    }
    .chip-row {
        display: flex; align-items: center; gap: 0.4rem;
        flex-wrap: wrap;
    }
    .chip {
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.14);
        color: #cbd5e1;
        border-radius: 999px;
        padding: 0.3rem 0.8rem;
        font-size: 0.72rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .chip:hover { border-color: rgba(56, 189, 248, 0.5); color: #fff; }
    .chip.active { background: #2563eb; border-color: #2563eb; color: #fff; }
    .chip .count { opacity: 0.7; font-weight: 500; margin-left: 0.25rem; }

    .bottom-divider {
        width: 1px; height: 26px; background: rgba(255, 255, 255, 0.2);
    }
    .bottom-actions {
        display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;
    }
    .ctrl-btn {
        display: inline-flex; align-items: center; gap: 0.4rem;
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.14);
        color: #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.35rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
        white-space: nowrap;
    }
    .ctrl-btn:hover { background: rgba(37, 99, 235, 0.35); border-color: rgba(56, 189, 248, 0.5); }
    .ctrl-btn.icon-only { padding: 0.35rem 0.55rem; }
    .ctrl-badge {
        background: rgba(34, 197, 94, 0.18);
        border: 1px solid rgba(34, 197, 94, 0.4);
        color: #4ade80;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex; align-items: center; gap: 0.35rem;
        white-space: nowrap;
    }

    /* ---------- Right dark sidebar ---------- */
    .enforcer-sidebar {
        flex: 0 0 340px;
        width: 340px;
        max-width: 100%;
        background: linear-gradient(160deg, #0f2b4a 0%, #0a1f35 100%);
        color: #cbd5e1;
        display: flex;
        flex-direction: column;
        border-left: 1px solid rgba(255, 255, 255, 0.08);
        transition: transform 0.3s ease, opacity 0.3s ease;
        z-index: 1045;
    }

    .enforcer-sidebar-header {
        padding: 1rem 1.1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    .enforcer-sidebar-header h6 { margin: 0; font-weight: 700; color: #fff; font-size: 0.9rem; }
    .enforcer-sidebar-header .header-count {
        background: rgba(56, 189, 248, 0.15);
        color: #7dd3fc;
        font-size: 0.68rem;
        font-weight: 700;
        border-radius: 999px;
        padding: 0.15rem 0.55rem;
        margin-left: 0.5rem;
    }
    .side-close-btn {
        background: none; border: none; color: rgba(203, 213, 225, 0.7);
        font-size: 1.1rem; cursor: pointer; line-height: 1; padding: 0.25rem;
    }
    .side-close-btn:hover { color: #fff; }

    .enforcer-sidebar-list {
        overflow-y: auto; flex: 1; padding: 0.4rem 0;
    }
    .enforcer-sidebar-list::-webkit-scrollbar { width: 4px; }
    .enforcer-sidebar-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

    .enforcer-sidebar-item {
        display: flex; align-items: center; gap: 0.7rem;
        padding: 0.55rem 1rem; cursor: pointer;
        border-left: 3px solid transparent;
        transition: background 0.12s;
    }
    .enforcer-sidebar-item:hover { background: rgba(255, 255, 255, 0.05); }
    .enforcer-sidebar-item.is-selected { background: rgba(56, 189, 248, 0.12); border-left-color: #38bdf8; }
    .status-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .status-dot.active { background: #22c55e; box-shadow: 0 0 8px rgba(34, 197, 94, 0.6); }
    .status-dot.offline { background: #64748b; }
    .item-name { font-weight: 600; font-size: 0.8rem; color: #e2e8f0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; }
    .item-meta { font-size: 0.65rem; color: rgba(148, 163, 184, 0.9); text-overflow: ellipsis; overflow: hidden; white-space: nowrap; }
    .item-dist { font-size: 0.62rem; color: rgba(148, 163, 184, 0.8); flex-shrink: 0; }
    .item-zone-tag {
        font-size: 0.6rem; color: #7dd3fc; background: rgba(56, 189, 248, 0.12);
        border-radius: 999px; padding: 0.1rem 0.5rem; flex-shrink: 0;
    }

    .enforcer-sidebar-detail {
        flex-shrink: 0;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        padding: 0.9rem 1.1rem;
        background: rgba(0, 0, 0, 0.18);
    }
    .detail-empty { font-size: 0.75rem; color: rgba(148, 163, 184, 0.8); display: flex; align-items: center; gap: 0.5rem; }
    .detail-row { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; padding: 0.18rem 0; font-size: 0.75rem; }
    .detail-row .label { color: rgba(148, 163, 184, 0.9); }
    .detail-row .value { color: #e2e8f0; font-weight: 600; text-align: right; }
    .detail-row .badge-inzone { background: rgba(34, 197, 94, 0.18); color: #4ade80; font-size: 0.62rem; font-weight: 700; border-radius: 999px; padding: 0.12rem 0.5rem; }
    .detail-row .badge-offzone { background: rgba(251, 191, 36, 0.15); color: #fbbf24; font-size: 0.62rem; font-weight: 700; border-radius: 999px; padding: 0.12rem 0.5rem; }

    /* ---------- Mobile: panel becomes overlay ---------- */
    @media (max-width: 1023.98px) {
        .enforcer-sidebar {
            position: fixed;
            top: 0; right: 0; bottom: 0;
            width: 320px; max-width: 88vw;
            transform: translateX(105%);
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.5);
        }
        .enforcer-sidebar.is-open { transform: translateX(0); opacity: 1; visibility: visible; }
        .sidebar-backdrop {
            position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5);
            z-index: 1044; opacity: 0; visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
        }
        .sidebar-backdrop.is-visible { opacity: 1; visibility: visible; }
        .tracking-overlay-bottom { left: 1rem; right: 1rem; }
    }

    /* ---------- Desktop: panel always visible ---------- */
    @media (min-width: 1024px) {
        .enforcer-sidebar { transform: none !important; opacity: 1; visibility: visible; }
        .sidebar-backdrop { display: none !important; }
        .side-close-btn { display: none; }
        .tracking-page.enforcer-sidebar-collapsed .enforcer-sidebar { display: none !important; }
    }

    .maplibregl-ctrl-group { background: rgba(15, 23, 42, 0.85) !important; }
    .maplibregl-ctrl-group button { background-color: transparent !important; }
    .maplibregl-ctrl-group button .maplibregl-ctrl-icon { filter: invert(1) opacity(0.8); }
</style>
@endpush

@section('content')
<div class="tracking-page">
    <div class="tracking-map-wrap">
        <div id="tracking-map"></div>
        <div class="tracking-loading" id="trackingLoading" aria-label="Loading map">
            <div class="spinner-border text-light" role="status"></div>
        </div>

        {{-- Top-left overlay --}}
        <div class="tracking-overlay-top">
            <div class="tracking-info-card">
                <div class="tracking-title"><i class="bi bi-signpost-2-fill me-1"></i>GPS Tracking</div>
                <div class="tracking-subtitle">Real-time enforcer location monitoring</div>
                <div class="tracking-stats-mini">
                    <div class="stat"><span class="stat-value" id="stat-total">0</span> enforcers</div>
                    <div class="stat"><span class="stat-value" id="stat-active">0</span> active</div>
                    <div class="stat"><span class="stat-value" id="stat-zones">0</span> zones</div>
                </div>
            </div>
        </div>

        {{-- Bottom bar: filter chips + controls --}}
        <div class="tracking-overlay-bottom" id="filter-chips">
            <div class="chip-row">
                <button class="chip active" data-filter="all">All <span class="count" id="chip-all-count">0</span></button>
                <button class="chip" data-filter="active">Active <span class="count" id="chip-active-count">0</span></button>
                <button class="chip" data-filter="inzone">In Zone <span class="count" id="chip-inzone-count">0</span></button>
            </div>

            <span class="bottom-divider"></span>

            <div class="bottom-actions">
                <span class="ctrl-badge"><i class="bi bi-circle-fill" style="font-size:0.5rem;"></i><span id="active-count">0</span> Active</span>
                <button class="ctrl-btn" id="toggle-3d" title="Toggle 3D view">
                    <i class="bi bi-box"></i>3D
                </button>
                <button class="ctrl-btn icon-only" id="toggle-theme" title="Toggle dark/light theme" aria-label="Toggle dark/light theme">
                    <i class="bi bi-moon-stars"></i>
                </button>
                <button class="ctrl-btn icon-only" id="sidebarToggle" title="Hide enforcers panel" aria-label="Show or hide enforcers panel">
                    <i class="bi bi-people-slash"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Right dark sidebar --}}
    <aside class="enforcer-sidebar" id="enforcerSidebar">
        <div class="enforcer-sidebar-header">
            <div class="d-flex align-items-center">
                <i class="bi bi-people-fill me-2" style="color:#38bdf8;"></i>
                <h6>Enforcers<span class="header-count" id="header-count">0</span></h6>
            </div>
            <button class="side-close-btn" id="toggleSidebar" title="Hide panel" aria-label="Hide enforcers panel">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="enforcer-sidebar-list" id="enforcer-list">
            <div class="text-center py-4" style="color:rgba(148,163,184,0.8);font-size:0.78rem;">
                <div class="spinner-border spinner-border-sm mb-2" role="status" style="color:#38bdf8;"></div>
                <div>Loading enforcers...</div>
            </div>
        </div>
        <div class="enforcer-sidebar-detail" id="enforcer-detail">
            <div class="detail-empty"><i class="bi bi-info-circle"></i> Select an enforcer on the map or list to view details.</div>
        </div>
    </aside>

    {{-- Backdrop for mobile --}}
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
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

    const state = {
        enforcers: [],
        markers: {},
        zones: [],
        selectedEnforcerId: null,
        is3D: false,
        theme: 'light',
        refreshTimer: null,
        filter: 'all',
        sidebarVisible: window.innerWidth >= 1024,
        zonePopup: null,
    };
    let zoneHandlersBound = false;
    let firstLoad = true;

    const LoadingEl = document.getElementById('trackingLoading');

    function showLoading() { LoadingEl?.classList.add('is-visible'); }
    function hideLoading() { LoadingEl?.classList.remove('is-visible'); }
    showLoading();

    function applyCamera3D() {
        if (state.is3D) map.easeTo({ pitch: 60, bearing: -30, duration: 800 });
        else map.easeTo({ pitch: 0, bearing: 0, duration: 800 });
    }

    function applyDark3D() {
        if (state.theme !== 'dark') return;
        if (map.getLayer('3d-buildings-dark')) return;
        map.addLayer({
            id: '3d-buildings-dark',
            type: 'fill-extrusion',
            source: 'openmaptiles',
            'source-layer': 'building',
            minzoom: 14,
            paint: {
                'fill-extrusion-color': '#232323',
                'fill-extrusion-height': ['interpolate', ['linear'], ['zoom'], 14, 0, 15.5, ['get', 'render_height']],
                'fill-extrusion-base': ['interpolate', ['linear'], ['zoom'], 14, 0, 15.5, ['get', 'render_min_height']],
                'fill-extrusion-opacity': 0.6,
            },
        });
    }

    const Sidebar = document.getElementById('enforcerSidebar');
    const Backdrop = document.getElementById('sidebarBackdrop');
    const ToggleBtn = document.getElementById('sidebarToggle');
    const ToggleClose = document.getElementById('toggleSidebar');

    function openSidebar() {
        Sidebar.classList.add('is-open');
        Backdrop.classList.add('is-visible');
        state.sidebarVisible = true;
    }

    function closeSidebar() {
        Sidebar.classList.remove('is-open');
        Backdrop.classList.remove('is-visible');
        state.sidebarVisible = false;
    }

    function toggleSidebar() {
        if (window.innerWidth >= 1024) {
            const collapsed = document.querySelector('.tracking-page').classList.toggle('enforcer-sidebar-collapsed');
            const icon = ToggleBtn?.querySelector('i');
            if (icon) icon.className = collapsed ? 'bi bi-people-fill' : 'bi bi-people-slash';
            if (ToggleBtn) ToggleBtn.title = collapsed ? 'Show enforcers panel' : 'Hide enforcers panel';
            return;
        }
        state.sidebarVisible ? closeSidebar() : openSidebar();
    }

    ToggleBtn?.addEventListener('click', toggleSidebar);
    ToggleClose?.addEventListener('click', closeSidebar);
    Backdrop?.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', e => e.key === 'Escape' && closeSidebar());
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) closeSidebar();
    });

    function matchesFilter(e) {
        if (state.filter === 'active') return e.status === 'active';
        if (state.filter === 'inzone') return e.inside_zone === true;
        return true;
    }

    function createMarker(el, enforcer) {
        return new maplibregl.Marker({ element: el })
            .setLngLat([enforcer.lng, enforcer.lat])
            .addTo(map);
    }

    function renderEnforcers() {
        Object.values(state.markers).forEach(m => m.remove());
        state.markers = {};
        state.enforcers.filter(matchesFilter).forEach(e => {
            const el = document.createElement('div');
            const color = e.status === 'active' ? '#22c55e' : '#9ca3af';
            el.innerHTML = `<svg width="36" height="36" viewBox="0 0 24 24" fill="${color}" stroke="#0a1f35" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" fill="#fff" font-size="10" font-weight="bold">${e.initials || 'E'}</text></svg>`;
            el.style.cursor = 'pointer';
            el.style.filter = e.id === state.selectedEnforcerId ? 'drop-shadow(0 0 8px rgba(56,189,248,0.9))' : '';
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
        if (existing) {
            try { map.removeLayer('zones-fill'); } catch (e) {}
            try { map.removeLayer('zones-outline'); } catch (e) {}
            map.removeSource('zones');
        }
        const existingCenters = map.getSource('zone-centers');
        if (existingCenters) {
            try { map.removeLayer('zone-labels'); } catch (e) {}
            map.removeSource('zone-centers');
        }
        if (state.zones.length === 0) return;
        const features = state.zones.map(z => {
            if (isNaN(z.center_lng) || isNaN(z.center_lat) || isNaN(z.radius_m)) return null;
            return {
                type: 'Feature',
                properties: { name: z.name, team: z.team || '—', radius: z.radius_m },
                geometry: { type: 'Polygon', coordinates: [buildCirclePolygon(z.center_lng, z.center_lat, z.radius_m)] },
            };
        }).filter(Boolean);
        const centerFeatures = state.zones.map(z => {
            if (isNaN(z.center_lng) || isNaN(z.center_lat)) return null;
            return {
                type: 'Feature',
                properties: { name: z.name, team: z.team || '—', radius: z.radius_m },
                geometry: { type: 'Point', coordinates: [z.center_lng, z.center_lat] },
            };
        }).filter(Boolean);
        map.addSource('zones', { type: 'geojson', data: { type: 'FeatureCollection', features } });
        map.addLayer({ id: 'zones-fill', type: 'fill', source: 'zones', paint: { 'fill-color': 'rgba(37, 99, 235, 0.12)', 'fill-outline-color': 'rgba(37, 99, 235, 0.3)' } });
        map.addLayer({ id: 'zones-outline', type: 'line', source: 'zones', paint: { 'line-color': '#2563eb', 'line-width': 2, 'line-opacity': 0.7, 'line-dasharray': [4, 3] } });
        map.addSource('zone-centers', { type: 'geojson', data: { type: 'FeatureCollection', features: centerFeatures } });
        map.addLayer({ id: 'zone-labels', type: 'symbol', source: 'zone-centers', layout: { 'text-field': ['get', 'name'], 'text-size': 11, 'text-offset': [0, -1], 'text-anchor': 'bottom' }, paint: { 'text-color': '#1e3a8a', 'text-halo-color': 'rgba(255,255,255,0.92)', 'text-halo-width': 1.6 } });
    }

    function showZonePopup(e) {
        const f = e.features && e.features[0];
        if (!f) return;
        if (!state.zonePopup) state.zonePopup = new maplibregl.Popup({ closeButton: true, maxWidth: '260px' });
        state.zonePopup.setLngLat(e.lngLat)
            .setHTML(`
                <div style="font-weight:700;font-size:0.9rem;">${f.properties.name}</div>
                <div style="font-size:0.8rem;color:#64748b;margin-top:2px;">👥 ${f.properties.team} · 📏 ${Math.round(f.properties.radius)}m</div>
            `)
            .addTo(map);
    }

    function selectEnforcer(id) {
        state.selectedEnforcerId = id;
        const e = state.enforcers.find(e => e.id === id);
        if (!e) return;
        renderEnforcers();
        map.flyTo({ center: [e.lng, e.lat], zoom: 15, duration: 800 });

        const detail = document.getElementById('enforcer-detail');
        detail.innerHTML = `
            <div class="detail-row"><span class="label">Enforcer</span><span class="value">${e.name}</span></div>
            <div class="detail-row"><span class="label">Status</span><span class="value" style="color:${e.status === 'active' ? '#4ade80' : '#94a3b8'};">${e.status === 'active' ? 'Active' : 'Offline'}</span></div>
            <div class="detail-row"><span class="label">Zone</span><span class="value">${e.zone_name || '—'}</span></div>
            <div class="detail-row"><span class="label">Team</span><span class="value">${e.team || '—'}</span></div>
            <div class="detail-row"><span class="label">Last seen</span><span class="value">${e.last_seen_label || '—'}</span></div>
            <div class="detail-row"><span class="label">Zone status</span><span class="${e.inside_zone ? 'badge-inzone' : 'badge-offzone'}">${e.inside_zone ? 'Inside zone' : 'Outside zone'}</span></div>
            <div class="detail-row"><span class="label">Distance to zone</span><span class="value">${e.distance_km != null ? e.distance_km + ' km' : '—'}</span></div>
        `;

        document.querySelectorAll('.enforcer-sidebar-item').forEach(el => el.classList.remove('is-selected'));
        const item = document.querySelector(`.enforcer-sidebar-item[data-id="${id}"]`);
        if (item) item.classList.add('is-selected');
    }

    function updateEnforcerList() {
        const list = document.getElementById('enforcer-list');
        const visible = state.enforcers.filter(matchesFilter);
        if (visible.length === 0) {
            list.innerHTML = `<div class="text-center py-4" style="color:rgba(148,163,184,0.8);font-size:0.75rem;"><i class="bi bi-search me-1"></i>No enforcers match this filter.</div>`;
            return;
        }
        list.innerHTML = visible.map(e => `
            <div class="enforcer-sidebar-item ${e.id === state.selectedEnforcerId ? 'is-selected' : ''}" data-id="${e.id}" onclick="window._selectEnforcer(${e.id})">
                <span class="status-dot ${e.status === 'active' ? 'active' : 'offline'}"></span>
                <div class="flex-grow-1 min-width-0">
                    <div class="item-name">${e.name}</div>
                    <div class="item-meta">${e.team || '—'} · ${e.last_seen_label || '—'}</div>
                </div>
                <span class="item-zone-tag">${e.zone_name || 'No zone'}</span>
                <small class="item-dist">${e.distance_km != null ? e.distance_km + 'km' : ''}</small>
            </div>
        `).join('');
    }

    function updateCounts() {
        const total = state.enforcers.length;
        const active = state.enforcers.filter(e => e.status === 'active').length;
        const inzone = state.enforcers.filter(e => e.inside_zone === true).length;
        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-active').textContent = active;
        document.getElementById('stat-zones').textContent = state.zones.length;
        document.getElementById('active-count').textContent = active;
        document.getElementById('header-count').textContent = total;
        document.getElementById('chip-all-count').textContent = total;
        document.getElementById('chip-active-count').textContent = active;
        document.getElementById('chip-inzone-count').textContent = inzone;
    }

    window._selectEnforcer = selectEnforcer;

    document.querySelectorAll('#filter-chips .chip').forEach(chip => {
        chip.addEventListener('click', () => {
            state.filter = chip.dataset.filter;
            document.querySelectorAll('#filter-chips .chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            renderEnforcers();
            updateEnforcerList();
        });
    });

    async function fetchData() {
        try {
            const r = await fetch('{{ route("tracking.locations") }}');
            const d = await r.json();
            state.enforcers = d.enforcers || [];
            state.zones = d.zones || [];
            if (state.selectedEnforcerId) {
                const still = state.enforcers.find(e => e.id === state.selectedEnforcerId);
                if (!still) state.selectedEnforcerId = null;
            }
            renderEnforcers();
            renderZones();
            updateEnforcerList();
            updateCounts();
            if (state.selectedEnforcerId) selectEnforcer(state.selectedEnforcerId);
        } catch (err) { console.error('Tracking fetch failed:', err); }
    }

    document.getElementById('toggle-3d').addEventListener('click', () => {
        state.is3D = !state.is3D;
        applyCamera3D();
        document.getElementById('toggle-3d').innerHTML = state.is3D ? '<i class="bi bi-box"></i>2D' : '<i class="bi bi-box"></i>3D';
    });

    document.getElementById('toggle-theme').addEventListener('click', () => {
        state.theme = state.theme === 'dark' ? 'light' : 'dark';
        showLoading();
        map.setStyle(state.theme === 'dark' ? 'https://tiles.openfreemap.org/styles/dark' : 'https://tiles.openfreemap.org/styles/liberty');
        const btn = document.getElementById('toggle-theme');
        btn.querySelector('i').className = state.theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        btn.title = state.theme === 'dark' ? 'Toggle light theme' : 'Toggle dark theme';
        document.querySelector('.tracking-page').classList.toggle('map-dark', state.theme === 'dark');
    });

    map.on('load', () => {
        if (firstLoad) {
            firstLoad = false;
            fetchData();
            if (!state.refreshTimer) state.refreshTimer = setInterval(fetchData, 15000);
            if (!zoneHandlersBound) {
                map.on('click', 'zones-fill', showZonePopup);
                map.on('click', 'zone-labels', showZonePopup);
                zoneHandlersBound = true;
            }
        } else {
            applyCamera3D();
            applyDark3D();
            renderZones();
        }
        hideLoading();
    });
});
</script>
@endpush
