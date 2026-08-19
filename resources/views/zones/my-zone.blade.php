@extends('layouts.app')

@section('title', 'My Zone')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
<style>
    #my-zone-map { width:100%; min-height:500px; border-radius:0.75rem; overflow:hidden; position:relative; }
    .zone-detail-card {
        border:1px solid var(--itevcms-border); border-radius:0.75rem;
        background:var(--itevcms-card); overflow:hidden;
    }
    .zone-detail-card .card-body { padding:1.25rem; }
    .zone-stat {
        text-align:center; padding:0.75rem;
        background:#f8fafc; border-radius:0.5rem;
    }
    .zone-stat .value { font-size:1.25rem; font-weight:800; }
    .zone-stat .label { font-size:0.65rem; color:var(--itevcms-text-muted); text-transform:uppercase; font-weight:600; }
    .team-member {
        display:flex; align-items:center; gap:0.75rem;
        padding:0.5rem 0; border-bottom:1px solid #f1f5f9;
    }
    .team-member:last-child { border-bottom:none; }
    .map-detail-overlay {
        position:absolute; bottom:12px; left:12px; z-index:10;
        background:rgba(15,23,42,0.92); backdrop-filter:blur(12px);
        border:1px solid rgba(255,255,255,0.12); border-radius:0.75rem;
        padding:0.85rem 1rem; color:#e2e8f0; font-family:system-ui,sans-serif;
        max-width:300px; pointer-events:auto; box-shadow:0 8px 32px rgba(0,0,0,0.35);
        transition:opacity 0.2s, transform 0.2s;
    }
    .map-detail-overlay.is-hidden { opacity:0; transform:translateY(8px); pointer-events:none; }
    .map-detail-overlay .mdo-title { font-weight:700; font-size:0.9rem; margin-bottom:0.4rem; display:flex; align-items:center; gap:0.4rem; }
    .map-detail-overlay .mdo-row { display:flex; justify-content:space-between; padding:0.15rem 0; font-size:0.75rem; }
    .map-detail-overlay .mdo-row .mdo-lbl { color:rgba(148,163,184,0.9); }
    .map-detail-overlay .mdo-row .mdo-val { font-weight:600; text-align:right; }
    .map-detail-overlay .mdo-badge { display:inline-block; font-size:0.62rem; font-weight:700; border-radius:999px; padding:0.1rem 0.45rem; }
    .map-detail-overlay .mdo-badge.active { background:rgba(34,197,94,0.18); color:#4ade80; }
    .map-detail-overlay .mdo-close { position:absolute; top:6px; right:8px; background:none; border:none; color:rgba(203,213,225,0.6); cursor:pointer; font-size:0.85rem; padding:2px 4px; }
    .map-detail-overlay .mdo-close:hover { color:#fff; }
</style>
@endpush

@section('content')
<p class="text-muted mb-4 small">Your assigned patrol zone and team information.</p>

@if ($zones->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-geo-alt" style="font-size:2.5rem;display:block;margin-bottom:0.75rem;opacity:0.3;"></i>
        <span class="fw-semibold">No assigned zone</span>
        <div class="small mt-1">You have not been assigned to any active zone yet. Contact your administrator.</div>
    </div>
@else
    @foreach ($zones as $zone)
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div id="my-zone-map" data-lat="{{ $zone->center_latitude }}" data-lng="{{ $zone->center_longitude }}" data-radius="{{ $zone->radius_m }}" data-name="{{ $zone->name }}" data-team="{{ $zone->team?->name ?? '' }}" data-desc="{{ $zone->description ?? '' }}"></div>
                <div class="map-detail-overlay is-hidden" id="my-zone-detail"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="zone-detail-card mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2" style="color:#2563eb;"></i>{{ $zone->name }}</h5>
                    @if ($zone->address)
                        <p class="small text-muted mb-2"><i class="bi bi-pin-map me-1"></i>{{ $zone->address }}</p>
                    @endif
                    @if ($zone->description)
                        <p class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i>{{ $zone->description }}</p>
                    @endif
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="zone-stat">
                                <div class="value">{{ number_format($zone->radius_m) }}</div>
                                <div class="label">Radius (m)</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="zone-stat">
                                <div class="value">{{ $zone->center_latitude }}, {{ $zone->center_longitude }}</div>
                                <div class="label">Coordinates</div>
                            </div>
                        </div>
                    </div>
                    @if ($zone->team)
                    <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#f0f7ff;">
                        <i class="bi bi-people-fill" style="color:#2563eb;"></i>
                        <div>
                            <div class="fw-semibold small">{{ $zone->team->name }}</div>
                            @if ($zone->team->leader)
                                <div class="text-muted" style="font-size:0.65rem;">Leader: {{ $zone->team->leader->name }}</div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @if ($zone->team)
            <div class="zone-detail-card">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-people me-2"></i>Team Members</h6>
                    @php $members = $zone->team->members()->orderBy('name')->get(); @endphp
                    @forelse ($members as $member)
                        <div class="team-member">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white fw-bold" style="width:36px;height:36px;font-size:0.75rem;background:#2563eb;">
                                {{ collect(explode(' ', $member->name))->map(fn($p) => $p[0])->take(2)->join('') }}
                            </span>
                            <div>
                                <div class="fw-semibold small">{{ $member->name }}</div>
                                <div class="text-muted" style="font-size:0.65rem;">{{ $member->role->label() }}</div>
                            </div>
                            @if ($member->id === $user->id)
                                <span class="badge bg-success ms-auto">You</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No team members.</p>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </div>
    @endforeach
@endif
@endsection

@push('scripts')
<script src="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapContainer = document.getElementById('my-zone-map');
    if (!mapContainer) return;

    const lat = parseFloat(mapContainer.dataset.lat);
    const lng = parseFloat(mapContainer.dataset.lng);
    const radius = parseFloat(mapContainer.dataset.radius);
    const name = mapContainer.dataset.name;
    const team = mapContainer.dataset.team || '—';
    const desc = mapContainer.dataset.desc || '';
    const detailEl = document.getElementById('my-zone-detail');
    const areaM2 = Math.round(Math.PI * radius * radius);

    function showZoneDetail() {
        if (!detailEl) return;
        detailEl.innerHTML = `
            <button class="mdo-close" onclick="document.getElementById('my-zone-detail').classList.add('is-hidden')">&times;</button>
            <div class="mdo-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="#38bdf8" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" fill="#fff" font-size="9" font-weight="bold">Z</text></svg>
                ${name}
            </div>
            <div class="mdo-row"><span class="mdo-lbl">Status</span><span class="mdo-badge active">Active</span></div>
            <div class="mdo-row"><span class="mdo-lbl">Team</span><span class="mdo-val">${team}</span></div>
            <div class="mdo-row"><span class="mdo-lbl">Radius</span><span class="mdo-val">${radius.toLocaleString()} m</span></div>
            <div class="mdo-row"><span class="mdo-lbl">Area</span><span class="mdo-val">${areaM2.toLocaleString()} m²</span></div>
            <div class="mdo-row"><span class="mdo-lbl">Coordinates</span><span class="mdo-val">${lat.toFixed(7)}, ${lng.toFixed(7)}</span></div>
            ${desc ? `<div style="margin-top:0.3rem;font-size:0.7rem;color:rgba(203,213,225,0.7);">${desc}</div>` : ''}
        `;
        detailEl.classList.remove('is-hidden');
    }
    showZoneDetail();

    const map = new maplibregl.Map({
        container: 'my-zone-map',
        style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
        center: [lng, lat],
        zoom: 14,
    });

    map.addControl(new maplibregl.NavigationControl(), 'top-right');

    map.on('load', function () {
        const radiusDeg = radius / 111320;
        const coords = [];
        const points = 64;
        for (let i = 0; i <= points; i++) {
            const angle = (i / points) * 2 * Math.PI;
            const dx = radiusDeg * Math.cos(angle) / Math.cos(lat * Math.PI / 180);
            const dy = radiusDeg * Math.sin(angle);
            coords.push([lng + dx, lat + dy]);
        }

        map.addSource('zone', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: [{
                    type: 'Feature',
                    properties: { name: name },
                    geometry: { type: 'Polygon', coordinates: [coords] },
                }],
            },
        });

        map.addLayer({
            id: 'zone-fill',
            type: 'fill',
            source: 'zone',
            paint: { 'fill-color': 'rgba(37, 99, 235, 0.1)', 'fill-outline-color': 'rgba(37, 99, 235, 0.3)' },
        });

        map.addLayer({
            id: 'zone-outline',
            type: 'line',
            source: 'zone',
            paint: { 'line-color': '#2563eb', 'line-width': 2, 'line-opacity': 0.7, 'line-dasharray': [3, 2] },
        });

        new maplibregl.Marker({ color: '#2563eb' })
            .setLngLat([lng, lat])
            .addTo(map);

        showZoneDetail();
    });
});
</script>
@endpush