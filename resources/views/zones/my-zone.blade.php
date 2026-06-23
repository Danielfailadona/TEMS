@extends('layouts.app')

@section('title', 'My Zone')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
<style>
    #my-zone-map { width:100%; min-height:500px; border-radius:0.75rem; overflow:hidden; }
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
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">My Zone</h1>
        <p class="text-muted mb-0 small">Your assigned patrol zone and team information.</p>
    </div>
</div>

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
                <div id="my-zone-map" data-lat="{{ $zone->center_latitude }}" data-lng="{{ $zone->center_longitude }}" data-radius="{{ $zone->radius_m }}" data-name="{{ $zone->name }}"></div>
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
            .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML('<strong>' + name + '</strong>'))
            .addTo(map);
    });
});
</script>
@endpush