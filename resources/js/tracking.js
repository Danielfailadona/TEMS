import maplibregl from 'maplibre-gl';

function initTrackingMap(options = {}) {
    const {
        containerId = 'tracking-map',
        enforcersEndpoint = '/tracking/locations',
        styleUrl = 'https://tiles.openfreemap.org/styles/liberty',
        center = [121.0402, 14.5432],
        zoom = 12,
        refreshInterval = 15000,
    } = options;

    const map = new maplibregl.Map({
        container: containerId,
        style: styleUrl,
        center,
        zoom,
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
        refreshTimer: null,
    };

    function createEnforcerMarker(enforcer, isSelected = false) {
        const el = document.createElement('div');
        el.className = 'enforcer-marker';
        const statusColor = enforcer.status === 'active' ? '#22c55e' : '#9ca3af';
        el.innerHTML = `<svg width="32" height="32" viewBox="0 0 24 24" fill="${statusColor}" stroke="white" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" fill="white" font-size="10" font-weight="bold">${enforcer.initials || 'E'}</text></svg>`;
        el.style.width = '32px';
        el.style.height = '32px';
        el.style.cursor = 'pointer';
        if (isSelected) {
            el.style.filter = 'drop-shadow(0 0 8px rgba(37,99,235,0.8))';
        }

        const popup = new maplibregl.Popup({ offset: 25, closeButton: false }).setHTML(`
            <div style="font-weight:600">${enforcer.name}</div>
            <div style="font-size:0.85rem;color:#666">${enforcer.status === 'active' ? '\u{1F7E2} Active' : '\u{26AA} Offline'}</div>
            <div style="font-size:0.85rem;color:#666">${enforcer.team || 'No team'}</div>
        `);

        const marker = new maplibregl.Marker({ element: el })
            .setLngLat([enforcer.lng, enforcer.lat])
            .setPopup(popup)
            .addTo(map);

        el.addEventListener('click', () => selectEnforcer(enforcer.id));

        return marker;
    }

    function renderEnforcers() {
        Object.values(state.markers).forEach(m => m.remove());
        state.markers = {};

        state.enforcers.forEach(enforcer => {
            const isSelected = enforcer.id === state.selectedEnforcerId;
            state.markers[enforcer.id] = createEnforcerMarker(enforcer, isSelected);
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
            map.removeLayer('zones-fill');
            map.removeLayer('zones-outline');
            map.removeSource('zones');
        }

        if (state.zones.length === 0) return;

        const features = state.zones.map(zone => {
            const lng = parseFloat(zone.center_lng);
            const lat = parseFloat(zone.center_lat);
            const radius = parseFloat(zone.radius_m);
            if (isNaN(lng) || isNaN(lat) || isNaN(radius)) return null;
            return {
                type: 'Feature',
                properties: { name: zone.name, team: zone.team || '' },
                geometry: {
                    type: 'Polygon',
                    coordinates: [buildCirclePolygon(lng, lat, radius)],
                },
            };
        }).filter(Boolean);

        map.addSource('zones', {
            type: 'geojson',
            data: { type: 'FeatureCollection', features },
        });

        map.addLayer({
            id: 'zones-fill',
            type: 'fill',
            source: 'zones',
            paint: {
                'fill-color': 'rgba(37, 99, 235, 0.08)',
                'fill-outline-color': 'rgba(37, 99, 235, 0.25)',
            },
        });

        map.addLayer({
            id: 'zones-outline',
            type: 'line',
            source: 'zones',
            paint: {
                'line-color': 'rgba(37, 99, 235, 0.4)',
                'line-width': 1,
                'line-opacity': 0.6,
                'line-dasharray': [3, 2],
            },
        });
    }

    function selectEnforcer(id) {
        state.selectedEnforcerId = id;
        const enforcer = state.enforcers.find(e => e.id === id);
        if (!enforcer) return;

        renderEnforcers();

        map.flyTo({ center: [enforcer.lng, enforcer.lat], zoom: 15, duration: 800 });

        const panel = document.getElementById('enforcer-detail');
        if (panel) {
            panel.innerHTML = `
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge ${enforcer.status === 'active' ? 'bg-success' : 'bg-secondary'} rounded-circle p-2" style="width:12px;height:12px;"></span>
                    <strong class="fs-5">${enforcer.name}</strong>
                </div>
                <hr class="my-2">
                <div class="small">
                    <div class="mb-1"><span class="text-muted">Status:</span> ${enforcer.status === 'active' ? 'Active' : 'Offline'}</div>
                    <div class="mb-1"><span class="text-muted">Team:</span> ${enforcer.team || '\u2014'}</div>
                    <div class="mb-1"><span class="text-muted">Zone:</span> ${enforcer.zone_name || '\u2014'} (${enforcer.distance_km || '?'} km)</div>
                    <div class="mb-1"><span class="text-muted">Inside Zone:</span> ${enforcer.inside_zone ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'}</div>
                    <div class="mb-1"><span class="text-muted">Latitude:</span> <code>${enforcer.lat}</code></div>
                    <div class="mb-1"><span class="text-muted">Longitude:</span> <code>${enforcer.lng}</code></div>
                    <div class="mb-1"><span class="text-muted">Accuracy:</span> \u00B1${enforcer.accuracy_m || '?'}m</div>
                    <div class="mb-1"><span class="text-muted">Last Seen:</span> ${enforcer.last_seen_label || '\u2014'}</div>
                </div>
            `;
        }
    }

    function updateEnforcerList() {
        const list = document.getElementById('enforcer-list');
        if (!list) return;

        list.innerHTML = state.enforcers.map(e => `
            <div class="d-flex align-items-center justify-content-between p-2 border-bottom ${e.id === state.selectedEnforcerId ? 'bg-light' : ''}" style="cursor:pointer" onclick="window.selectEnforcer(${e.id})">
                <div class="d-flex align-items-center gap-2">
                    <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:${e.status === 'active' ? '#22c55e' : '#9ca3af'}"></span>
                    <span class="fw-semibold small">${e.name}</span>
                </div>
                <small class="text-muted">${e.zone_name || '\u2014'}</small>
            </div>
        `).join('');
    }

    async function fetchData() {
        try {
            const response = await fetch(enforcersEndpoint);
            const data = await response.json();

            state.enforcers = data.enforcers || [];
            state.zones = data.zones || [];

            renderEnforcers();
            renderZones();
            updateEnforcerList();

            if (state.selectedEnforcerId) {
                const stillExists = state.enforcers.find(e => e.id === state.selectedEnforcerId);
                if (!stillExists) {
                    selectEnforcer(state.enforcers[0]?.id || null);
                } else {
                    selectEnforcer(state.selectedEnforcerId);
                }
            } else if (state.enforcers.length > 0) {
                selectEnforcer(state.enforcers[0].id);
            }

            const countEl = document.getElementById('enforcer-count');
            if (countEl) countEl.textContent = state.enforcers.filter(e => e.status === 'active').length;
        } catch (err) {
            console.error('Tracking fetch failed:', err);
        }
    }

    function toggle3D() {
        state.is3D = !state.is3D;
        if (state.is3D) {
            map.easeTo({ pitch: 60, bearing: -30, duration: 1000 });
        } else {
            map.easeTo({ pitch: 0, bearing: 0, duration: 1000 });
        }
        const btn = document.getElementById('toggle-3d');
        if (btn) btn.textContent = state.is3D ? '2D View' : '3D View';
    }

    map.on('load', () => {
        fetchData();
        state.refreshTimer = setInterval(fetchData, refreshInterval);

        const btn = document.getElementById('toggle-3d');
        if (btn) btn.addEventListener('click', toggle3D);
    });

    window.selectEnforcer = selectEnforcer;

    return map;
}

window.__tracking = { initTrackingMap };
