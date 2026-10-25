<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report Submitted — {{ config('itevcms.app_name') }}</title>
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
        .action-card {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .action-card .card-body {
            transition: background 0.2s ease;
        }
        .action-card:hover .card-body {
            background: rgba(37, 99, 235, 0.03);
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(15, 23, 42, 0.05);
        }
        .feature-item:last-child {
            border-bottom: none;
        }
        .feature-icon {
            width: 2.2rem;
            height: 2.2rem;
            background: rgba(37, 99, 235, 0.1);
            border-radius: 0.6rem;
            display: grid;
            place-items: center;
            color: #2563eb;
            flex-shrink: 0;
            font-size: 1.1rem;
        }
        .map-detail-overlay {
            position: absolute;
            bottom: 12px;
            left: 12px;
            z-index: 10;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
            color: #e2e8f0;
            font-family: system-ui, sans-serif;
            max-width: 300px;
            pointer-events: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
            transition: opacity 0.2s, transform 0.2s;
        }
        .map-detail-overlay.is-hidden {
            opacity: 0;
            transform: translateY(8px);
            pointer-events: none;
        }
        .map-detail-overlay .mdo-title {
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
        }
        .map-detail-overlay .mdo-row {
            display: flex;
            justify-content: space-between;
            padding: 0.15rem 0;
            font-size: 0.75rem;
        }
        .map-detail-overlay .mdo-row .mdo-lbl {
            color: rgba(148, 163, 184, 0.9);
        }
        .map-detail-overlay .mdo-row .mdo-val {
            font-weight: 600;
            text-align: right;
        }
        .map-detail-overlay .mdo-badge {
            display: inline-block;
            font-size: 0.62rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 0.1rem 0.45rem;
        }
        .map-detail-overlay .mdo-badge.active {
            background: rgba(251, 191, 36, 0.18);
            color: #fbbf24;
        }
        .map-detail-overlay .mdo-close {
            position: absolute;
            top: 6px;
            right: 8px;
            background: none;
            border: none;
            color: rgba(203, 213, 225, 0.6);
            cursor: pointer;
            font-size: 0.85rem;
            padding: 2px 4px;
        }
        .map-detail-overlay .mdo-close:hover {
            color: #fff;
        }
        .info-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            padding: 0.25rem 0.65rem;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .evidence-thumb {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 0.6rem;
            overflow: hidden;
            width: 100%;
            height: 260px;
            object-fit: cover;
        }
        .evidence-thumb:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .section-group {
            margin-bottom: 0.5rem;
        }
        .section-group:last-child {
            margin-bottom: 0;
        }
        .section-group-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }
        .note-entry {
            padding: 0.75rem;
            border-radius: 0.6rem;
            background: #f8fafc;
            border-left: 3px solid #2563eb;
            margin-bottom: 0.5rem;
        }
        .note-entry:last-child {
            margin-bottom: 0;
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
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('citizen.report-parking.landing') }}">Report Parking</a></li>
                <li class="nav-item"><a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign In</a></li>
                <li class="nav-item"><a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign Up</a></li>
            </ul>
        </div>
    </div>
</nav>

@include('components.alerts')

    @if ($reference)
        <div class="modal fade" id="referenceModal" tabindex="-1" aria-labelledby="referenceModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="referenceModalLabel">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>Report Submitted Successfully
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="mb-3">Report Submitted Successfully</h5>
                        <p class="text-muted mb-3">Your parking violation report has been submitted. Save your reference ID:</p>
                        <div class="input-group mb-4">
                            <input type="text" class="form-control text-center fw-bold text-primary fs-5" id="referenceInput" value="{{ $reference }}" readonly aria-label="Reference ID">
                            <button class="btn btn-outline-primary" type="button" id="copyReferenceBtn" data-clipboard-target="#referenceInput">
                                <i class="bi bi-clipboard me-1"></i>Copy
                            </button>
                        </div>
                        <p class="text-muted small mb-0">Save this reference ID to track your report status.</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-primary" onclick="handleOkClick()">OK</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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

    <form method="POST" action="{{ route('citizen.report-parking.store') }}" enctype="multipart/form-data">
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
                                <label class="form-label fw-semibold small">Color</label>
                                <input type="text" name="vehicle_color" class="form-control @error('vehicle_color') is-invalid @enderror" placeholder="e.g., White" value="{{ old('vehicle_color') }}">
                                @error('vehicle_color')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Make</label>
                                <input type="text" name="vehicle_make" class="form-control @error('vehicle_make') is-invalid @enderror" placeholder="e.g., Toyota" value="{{ old('vehicle_make') }}">
                                @error('vehicle_make')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Model</label>
                                <input type="text" name="vehicle_model" class="form-control @error('vehicle_model') is-invalid @enderror" placeholder="e.g., Corolla" value="{{ old('vehicle_model') }}">
                                @error('vehicle_model')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
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

                <!-- Submit Button -->
                <div class="d-flex flex-column flex-md-row gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold py-2">
                        <i class="bi bi-send me-2"></i>Submit Report
                    </button>
                    <a href="{{ route('citizen.citation.lookup') }}" class="btn btn-outline-secondary py-2">Cancel</a>
                </div>

        </form>
                </div>

                <div class="tab-pane fade" id="clamping-track" role="tabpanel" aria-labelledby="clamping-track-tab">
                    @include('citizen.partials.clamping-track-panel', ['requestInfo' => $requestInfo ?? null])
                </div>
            </div>
        </div>
    </div>
</div>

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
    if (locationMarker) {
        locationMarker.remove();
    }
    locationMarker = new maplibregl.Marker({ color: '#dc2626', draggable: true })
        .setLngLat([lng, lat])
        .addTo(map);

    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);

    locationMarker.on('dragend', function () {
        const pos = locationMarker.getLngLat();
        document.getElementById('latitude').value = pos.lat.toFixed(6);
        document.getElementById('longitude').value = pos.lng.toFixed(6);
    });
}

function getGPSCoordinates() {
    const btn = document.getElementById('gpsButton');
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Getting location...';
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
            placePin(lat, lng);
            if (map) {
                map.flyTo({ center: [lng, lat], zoom: 16, duration: 500 });
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Location Set';
            setTimeout(() => {
                btn.innerHTML = original;
            }, 2500);
        },
        (error) => {
            alert('Unable to get GPS coordinates: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = original;
        }
    );
}

function previewPhoto(event) {
    const input = event.target;
    const preview = document.getElementById('photoPreview');
    preview.innerHTML = '';

    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'img-fluid rounded border mt-2';
            img.style.maxHeight = '200px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('referenceModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
        modal.show();

        // Copy button functionality
        const copyBtn = document.getElementById('copyReferenceBtn');
        const input = document.getElementById('referenceInput');
        if (copyBtn && input) {
            copyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText(input.value).then(() => {
                    const original = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
                    copyBtn.classList.replace('btn-outline-primary', 'btn-success');
                    setTimeout(() => {
                        copyBtn.innerHTML = original;
                        copyBtn.classList.replace('btn-success', 'btn-outline-primary');
                    }, 2000);
                }).catch(() => {
                    // Fallback for older browsers
                    input.select();
                    document.execCommand('copy');
                    const original = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
                    copyBtn.classList.replace('btn-outline-primary', 'btn-success');
                    setTimeout(() => {
                        copyBtn.innerHTML = original;
                        copyBtn.classList.replace('btn-success', 'btn-outline-primary');
                    }, 2000);
                });
            });
        }
    }

    const container = document.getElementById('location-map');
    if (container && container.offsetHeight === 0) {
        container.style.height = '400px';
    }

    if (document.getElementById('gpsButton')) {
        initMap();
    }

    const trackTab = document.querySelector('[data-bs-target="#clamping-track"]');
    if (trackTab) {
        trackTab.addEventListener('shown.bs.tab', function () {
            setTimeout(() => {
                if (map) map.resize();
            }, 300);
        });
    }

    const bs = window.bootstrap;
    if (!bs) return;

    const navCollapse = document.getElementById('navbarNav');
    if (navCollapse && !bs.Collapse.getInstance(navCollapse)) {
        new bs.Collapse(navCollapse, { toggle: false });
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bs.Tooltip(el);
    });
    
    // Handle OK button click - dismiss modal and redirect to clean URL
    window.handleOkClick = function() {
        const modalEl = document.getElementById('referenceModal');
        const modal = bootstrap.Modal.getInstance(document.getElementById('referenceModal'));
        if (modal) {
            modal.hide();
        }
        // Redirect to clean URL without reference parameter
        window.location.href = window.location.origin + '/citizen/report-parking';
    };
});
</script>
</body>
</html>