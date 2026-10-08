@extends('layouts.app')

@section('title', 'Impound Vehicle')

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
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 animate-on-load">
    <p class="text-muted mb-0 small">Impound vehicles for eligible violations (unregistered, expired plate, etc.).</p>
    <a href="{{ route('impounding.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Impounding</a>
</div>

<div class="d-flex gap-3 mb-4 flex-wrap animate-on-load">
    <div class="stat-card-sm">
        <div class="stat-value" style="color:#dc2626;">{{ $citation ? 1 : 0 }}</div>
        <div class="stat-label">Citation Linked</div>
    </div>
    <div class="stat-card-sm">
        <div class="stat-value" style="color:#2563eb;">{{ $citation?->violationType?->name ?? '—' }}</div>
        <div class="stat-label">Violation Type</div>
    </div>
</div>

<form method="POST" action="{{ route('impounding.store') }}" enctype="multipart/form-data" id="impounding-form">
    @csrf
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card stat-card mb-4 animate-on-load">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Vehicle Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Vehicle Plate <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_plate" class="form-control text-uppercase @error('vehicle_plate') is-invalid @enderror" placeholder="e.g. ABC-1234" value="{{ old('vehicle_plate') ?? $citation->vehicle_plate }}" required>
                        @error('vehicle_plate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Citation</label>
                        @if ($citation)
                            <div class="form-control-plaintext fw-semibold">{{ $citation->citation_number }} - {{ $citation->violationType->name }}</div>
                            <input type="hidden" name="citation_id" value="{{ $citation->id }}">
                        @else
                            <div class="form-control-plaintext text-muted">No citation linked. Violation eligibility will be checked on impound.</div>
                        @endif
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Impound location" value="{{ old('location') }}">
                    </div>
                </div>
            </div>

            <div class="card stat-card mb-4 animate-on-load">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Fees (Based on Violation Type)</h5>
                </div>
                <div class="card-body">
                    @if ($citation)
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="card stat-card-sm text-center">
                                    <div class="stat-value text-danger">{{ $fees['towing_fee'] ? '₱' . number_format($fees['towing_fee'], 2) : '—' }}</div>
                                    <div class="stat-label">Towing Fee</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stat-card-sm text-center">
                                    <div class="stat-value text-warning">{{ $fees['storage_fee_per_day'] ? '₱' . number_format($fees['storage_fee_per_day'], 2) : '—' }}</div>
                                    <div class="stat-label">Storage/Day</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stat-card-sm text-center">
                                    <div class="stat-value text-info">{{ $fees['admin_fee'] ? '₱' . number_format($fees['admin_fee'], 2) : '—' }}</div>
                                    <div class="stat-label">Admin Fee</div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Grace Period:</strong> {{ config('itevcms.impounding.grace_hours', 24) }} hours before storage fees begin.
                            <br>Fees based on violation: <strong>{{ $citation->violationType->code }}</strong>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            No citation linked. Default fees will apply.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card stat-card mb-4 animate-on-load">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-camera me-2"></i>Evidence</h5>
                </div>
                <div class="card-body">
                    <label class="form-label fw-semibold small">Photo of Vehicle <span class="text-danger">*</span></label>
                    <input type="file" name="evidence" class="form-control @error('evidence') is-invalid @enderror" accept="image/*" required id="photoInput" onchange="previewPhoto(event)">
                    <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>Max 5MB. Clear photo showing license plate and violation.</small>
                    @error('evidence')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                    <div id="photoPreview" class="mt-3"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card stat-card animate-on-load">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location Map</h5>
                </div>
                <div class="card-body p-0">
                    <div id="impounding-map" class="zone-map"></div>
                </div>
                <div class="card-footer bg-transparent border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Click map to set impound location.</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('zones.index') }}" class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-eye me-1"></i>View Zones</a>
                        <a href="{{ route('zones.create') }}" class="btn btn-sm btn-outline-success flex-fill"><i class="bi bi-plus me-1"></i>Manage Zones</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('impounding.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-danger px-4"><i class="bi bi-truck-front me-1"></i>Impound Vehicle</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
@vite('resources/js/zone-picker.js')
<script src="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js"></script>
<script>
const MAP_STYLE = 'https://tiles.openfreemap.org/styles/liberty';
const DEFAULT_CENTER = [121.0402, 14.5432];
const DEFAULT_ZOOM = 12;

let map = null;
let locationMarker = null;

function initMap() {
    map = new maplibregl.Map({
        container: 'impounding-map',
        style: MAP_STYLE,
        center: DEFAULT_CENTER,
        zoom: DEFAULT_ZOOM,
        attributionControl: false,
    });

    map.addControl(new maplibregl.NavigationControl(), 'top-right');
    map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');

    map.on('click', function (e) {
        placePin(e.lngLat.lat, e.lngLat.lng);
    });
}

function placePin(lat, lng) {
    document.querySelector('input[name="latitude"]')?.value = lat.toFixed(6);
    document.querySelector('input[name="longitude"]')?.value = lng.toFixed(6);

    if (locationMarker) locationMarker.remove();

    const el = document.createElement('div');
    el.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="#dc2626" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" fill="white" font-size="10" font-weight="bold">P</text></svg>';
    el.style.width = '32px';
    el.style.height = '32px';

    locationMarker = new maplibregl.Marker({ element: el })
        .setLngLat([lng, lat])
        .addTo(map);

    map.flyTo({ center: [lng, lat], zoom: 16, duration: 600 });
}

function previewPhoto(event) {
    const preview = document.getElementById('photoPreview');
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = `
                <div class="position-relative d-inline-block w-100">
                    <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 300px; width: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <small class="text-muted d-block mt-2"><i class="bi bi-check-circle text-success me-1"></i>Photo selected</small>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initMap();
});
</script>
@endpush