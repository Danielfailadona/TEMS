<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report Illegal Parking — {{ config('itevcms.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
    <style>
        body {
            background: #f8fbff;
            min-height: 100vh;
        }
        .animate-on-load {
            opacity: 0;
            transform: translateY(12px);
            transition: opacity 0.55s ease, transform 0.55s ease;
        }
        .animate-on-load.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .stat-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1.1rem;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
            background: rgba(255, 255, 255, 0.92);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.1);
        }
        .form-control, .form-select {
            border-radius: 0.8rem;
            border: 1px solid rgba(15, 23, 42, 0.12);
            padding: 0.7rem 0.85rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: rgba(37, 99, 235, 0.45);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.16);
        }
        .btn {
            border-radius: 0.8rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        /* Map and form specific styles from clamping-request */
        #location-map {
            width: 100%;
            aspect-ratio: 4 / 3;
            min-height: 240px;
            border-radius: 0.5rem;
            position: relative;
        }
        .map-detail-overlay {
            position:absolute; bottom:12px; left:12px; z-index:10;
            background:rgba(15,23,42,0.92); backdrop-filter:blur(12px);
            border:1px solid rgba(255,255,255,0.12); border-radius:0.75rem;
            padding:0.85rem 1rem; color:#e2e8f0; font-family:system-ui,sans-serif;
            max-width:300px; pointer-events:auto; box-shadow:0 8px 32px rgba(0,0,0,0.35);
            transition:opacity 0.2s, transform 0.2s;
        }
        .map-detail-overlay.is-hidden { opacity:0; transform:translateY(8px); pointer-events:none; }
        .map-detail-overlay .mdo-title { font-weight:700; font-size:0.9rem; margin-bottom:0.4rem; }
        .map-detail-overlay .mdo-row { display:flex; justify-content:space-between; padding:0.15rem 0; font-size:0.75rem; }
        .map-detail-overlay .mdo-row .mdo-lbl { color:rgba(148,163,184,0.9); }
        .map-detail-overlay .mdo-row .mdo-val { font-weight:600; text-align:right; }
        .map-detail-overlay .mdo-badge { display:inline-block; font-size:0.62rem; font-weight:700; border-radius:999px; padding:0.1rem 0.45rem; background:rgba(34,197,94,0.18); color:#4ade80; }
        .map-detail-overlay .mdo-close { position:absolute; top:6px; right:8px; background:none; border:none; color:rgba(203,213,225,0.6); cursor:pointer; font-size:0.85rem; padding:2px 4px; }
        .map-detail-overlay .mdo-close:hover { color:#fff; }
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
        /* Responsive map container */
        @media (max-width: 991.98px) {
            #location-map {
                min-height: 300px;
                height: 50vh;
                max-height: 400px;
            }
        }
        @media (min-width: 992px) {
            #location-map {
                min-height: 500px;
                height: 500px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" style="z-index: 100;">
    <div class="container-fluid px-4 px-lg-5">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('welcome') }}">
            <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="32" class="me-2">
            <span style="background: linear-gradient(135deg, #0f2b4a, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">TEMs</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('welcome') }}#features">Features</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('citizen.citation.lookup') }}">Ticket Lookup</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('citizen.clamping.landing') }}">Report Parking</a></li>
                <li class="nav-item"><a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign In</a></li>
                <li class="nav-item"><a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign Up</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4 py-lg-5">
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show animate-on-load" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12">
            <div class="mb-3 animate-on-load">
                <h2 class="mb-0 h4">Report Illegally Parked Vehicle</h2>
                <p class="text-muted mb-0 small">Help us enforce parking regulations in your area</p>
            </div>

            <ul class="nav nav-pills nav-fill bg-white bg-opacity-75 rounded-pill p-1 shadow-sm mb-4" id="clampingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill" data-bs-toggle="tab" data-bs-target="#clamping-report" type="button" role="tab" aria-controls="clamping-report" aria-selected="true">
                        <i class="bi bi-car-front-fill me-2"></i>Report
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill" data-bs-toggle="tab" data-bs-target="#clamping-track" type="button" role="tab" aria-controls="clamping-track" aria-selected="false">
                        <i class="bi bi-search me-2"></i>Track
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="clamping-report" role="tabpanel" aria-labelledby="clamping-report-tab">

    <form method="POST" action="{{ route('citizen.clamping.store') }}" enctype="multipart/form-data">
        @csrf

        <p class="text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i>Fields marked with <span class="text-danger">*</span> are required.
        </p>

        <div class="row g-4 mb-4">
            <!-- Left column: Your Information + Location -->
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

                        <!-- Location section inside same card -->
                        <hr class="my-4">
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
                        <div class="map-detail-overlay is-hidden" id="citizen-map-detail"></div>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i>Click the map to fine-tune location, or use GPS above.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Right column: Vehicle Information + Evidence stacked -->
            <div class="col-lg-5">
                <!-- Vehicle Information -->
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

                <!-- Evidence -->
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

                <div class="tab-pane fade" id="clamping-track" role="tabpanel" aria-labelledby="clamping-track-tab">
                    @include('citizen.partials.clamping-track-panel', ['requestInfo' => $requestInfo ?? null])
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js"></script>
<script>
const MAP_STYLE = 'https://tiles.openfreemap.org/styles/liberty';
const DEFAULT_CENTER = [121.0402, 14.5432];
const DEFAULT_ZOOM = 12;

let map = null;
let locationMarker = null;

function initMap() {
    const container = document.getElementById('location-map');
    if (container && container.offsetHeight === 0) {
        container.style.height = '400px';
    }

    map = new maplibregl.Map({
        container: 'location-map',
        style: MAP_STYLE,
        center: DEFAULT_CENTER,
        zoom: DEFAULT_ZOOM,
        attributionControl: false,
    });

    map.addControl(new maplibregl.NavigationControl(), 'top-right');
    map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');

    map.on('error', function (e) {
        console.warn('MapLibre error:', e && e.error ? e.error.message : e);
    });

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

    const detailEl = document.getElementById('citizen-map-detail');
    if (detailEl) {
        detailEl.innerHTML = `
            <button class="mdo-close" onclick="document.getElementById('citizen-map-detail').classList.add('is-hidden')">&times;</button>
            <div class="mdo-title">Selected Location</div>
            <div class="mdo-row"><span class="mdo-lbl">Status</span><span class="mdo-badge">Pin placed</span></div>
            <div class="mdo-row"><span class="mdo-lbl">Latitude</span><span class="mdo-val">${lat.toFixed(6)}</span></div>
            <div class="mdo-row"><span class="mdo-lbl">Longitude</span><span class="mdo-val">${lng.toFixed(6)}</span></div>
        `;
        detailEl.classList.remove('is-hidden');
    }

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

    const params = new URLSearchParams(window.location.search);
    if (params.get('tab') === 'track') {
        const trackTab = document.querySelector('#clampingTabs .nav-link[data-bs-target="#clamping-track"]');
        if (trackTab) new bootstrap.Tab(trackTab).show();
    }

    document.querySelectorAll('#clampingTabs .nav-link').forEach(function (tabEl) {
        tabEl.addEventListener('shown.bs.tab', function (e) {
            if (e.target.getAttribute('data-bs-target') === '#clamping-report' && map) {
                setTimeout(function () { map.resize(); }, 60);
            }
        });
    });

    window.addEventListener('load', function () {
        if (map) map.resize();
    });
});
</script>
</body>
</html>