@extends('layouts.guest')

@section('title', 'Report Illegal Parking')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.css">
<style>
    .login-shell {
        width: min(1100px, 95vw) !important;
    }
    .login-card {
        max-width: 100% !important;
    }
    #location-map {
        width: 100%;
        aspect-ratio: 4 / 3;
        min-height: 240px;
        border-radius: 0.5rem;
    }
    .section-icon {
        width: 2rem;
        height: 2rem;
        background: linear-gradient(135deg, #2563eb, #0f2b4a);
        border-radius: 0.6rem;
        display: grid;
        place-items: center;
        color: #fff;
        font-size: 1rem;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="container py-3">
    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap animate-on-load">
        <a href="{{ route('welcome') }}" class="d-inline-flex align-items-center justify-content-center text-decoration-none"
           style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,0.9);box-shadow:0 2px 8px rgba(0,0,0,0.12);color:#1e293b;transition:all 0.2s;"
           onmouseover="this.style.background='#2563eb';this.style.color='#fff'" onmouseout="this.style.background='rgba(255,255,255,0.9)';this.style.color='#1e293b'">
            <i class="bi bi-house-door-fill" style="font-size:1.2rem;"></i>
        </a>
        <div>
            <h2 class="mb-0 h4">Report Illegally Parked Vehicle</h2>
            <p class="text-muted mb-0 small">Help us enforce parking regulations in your area</p>
        </div>
    </div>

    <form method="POST" action="{{ route('citizen.clamping.store') }}" enctype="multipart/form-data">
        @csrf

        <p class="text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i>Fields marked with <span class="text-danger">*</span> are required.
        </p>

        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card stat-card h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="section-icon"><i class="bi bi-person-fill"></i></span>
                            <h5 class="mb-0 fw-bold text-primary">Your Information</h5>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="requester_name" class="form-control @error('requester_name') is-invalid @enderror" value="{{ old('requester_name') }}" placeholder="Juan Dela Cruz" required>
                            @error('requester_name')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="requester_phone" class="form-control @error('requester_phone') is-invalid @enderror" value="{{ old('requester_phone') }}" placeholder="+639123456789" required>
                                @error('requester_phone')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="requester_email" class="form-control @error('requester_email') is-invalid @enderror" value="{{ old('requester_email') }}" placeholder="you@example.com" required>
                                @error('requester_email')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card stat-card h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="section-icon"><i class="bi bi-geo-alt-fill"></i></span>
                            <h5 class="mb-0 fw-bold text-primary">Location</h5>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Address / Place Name <span class="text-danger">*</span></label>
                            <input type="text" name="location_address" class="form-control @error('location_address') is-invalid @enderror" placeholder="e.g., 123 Main St, Barangay Marikina" value="{{ old('location_address') }}" required>
                            @error('location_address')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Latitude</label>
                                <input type="number" step="0.000001" name="latitude" id="latitude" class="form-control form-control-sm @error('latitude') is-invalid @enderror" value="{{ old('latitude') }}" placeholder="Auto-filled" readonly>
                                @error('latitude')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Longitude</label>
                                <input type="number" step="0.000001" name="longitude" id="longitude" class="form-control form-control-sm @error('longitude') is-invalid @enderror" value="{{ old('longitude') }}" placeholder="Auto-filled" readonly>
                                @error('longitude')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100 fw-semibold mb-3" id="gpsButton" onclick="getGPSCoordinates()">
                            <i class="bi bi-crosshair me-2"></i>Get Current Location
                        </button>

                        <div id="location-map"></div>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i>Click the map to fine-tune location, or use GPS above.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card stat-card mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="section-icon"><i class="bi bi-car-front-fill"></i></span>
                    <h5 class="mb-0 fw-bold text-primary">Vehicle Information</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">License Plate <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_plate" class="form-control text-uppercase @error('vehicle_plate') is-invalid @enderror" placeholder="e.g., ABC 1234" value="{{ old('vehicle_plate') }}" required>
                        @error('vehicle_plate')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Vehicle Description</label>
                        <input type="text" name="vehicle_description" class="form-control" placeholder="e.g., White Toyota Corolla" value="{{ old('vehicle_description') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Additional Notes</label>
                        <textarea name="additional_notes" class="form-control" rows="2" placeholder="Any additional details...">{{ old('additional_notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card stat-card mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="section-icon"><i class="bi bi-camera-fill"></i></span>
                    <h5 class="mb-0 fw-bold text-primary">Evidence</h5>
                </div>
                <label class="form-label fw-semibold small">Photo of Vehicle <span class="text-danger">*</span></label>
                <input type="file" name="evidence_photo" class="form-control @error('evidence_photo') is-invalid @enderror" accept="image/*" required id="photoInput" onchange="previewPhoto(event)">
                <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>Max 5MB. Clear photo showing license plate and parking violation.</small>
                @error('evidence_photo')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                <div id="photoPreview" class="mt-3"></div>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold py-2">
                <i class="bi bi-send me-2"></i>Submit Request
            </button>
            <a href="{{ route('citizen.citation.lookup') }}" class="btn btn-outline-secondary py-2">Cancel</a>
        </div>

        <p class="text-muted small mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            By submitting this request, you confirm that the information is accurate and the vehicle is illegally parked on your property.
        </p>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.js"></script>
<script>
const MAP_STYLE = 'https://tiles.openfreemap.org/styles/liberty';
const DEFAULT_CENTER = [121.0402, 14.5432];
const DEFAULT_ZOOM = 12;

let map = null;
let locationMarker = null;

function initMap() {
    map = new maplibregl.Map({
        container: 'location-map',
        style: MAP_STYLE,
        center: DEFAULT_CENTER,
        zoom: DEFAULT_ZOOM,
        attributionControl: false,
    });

    map.addControl(new maplibregl.NavigationControl(), 'top-right');
    map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');

    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);

    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
        placePin(lat, lng);
        map.flyTo({ center: [lng, lat], zoom: 16, duration: 500 });
    }

    map.on('click', function (e) {
        placePin(e.lngLat.lat, e.lngLat.lng);
    });
}

function placePin(lat, lng) {
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);

    if (locationMarker) locationMarker.remove();

    const el = document.createElement('div');
    el.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="#dc2626" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" fill="white" font-size="10" font-weight="bold">P</text></svg>';
    el.style.width = '32px';
    el.style.height = '32px';

    locationMarker = new maplibregl.Marker({ element: el })
        .setLngLat([lng, lat])
        .addTo(map);

    const gpsBtn = document.getElementById('gpsButton');
    gpsBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Location Set';
    gpsBtn.classList.remove('btn-primary');
    gpsBtn.classList.add('btn-success');
}

function getGPSCoordinates() {
    const button = document.getElementById('gpsButton');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Getting location...';

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function (position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                placePin(lat, lng);
                map.flyTo({ center: [lng, lat], zoom: 16, duration: 600 });
                button.disabled = false;
            },
            function (error) {
                alert('Unable to get GPS coordinates: ' + error.message);
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-crosshair me-2"></i>Get Current Location';
                button.classList.remove('btn-success');
                button.classList.add('btn-primary');
            }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
        button.disabled = false;
    }
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
