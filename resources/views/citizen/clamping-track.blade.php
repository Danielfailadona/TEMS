<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Report Parking — {{ config('itevcms.app_name') }}</title>
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
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.1);
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
        .map-detail-overlay .mdo-badge { display:inline-block; font-size:0.62rem; font-weight:700; border-radius:999px; padding:0.1rem 0.45rem; }
        .map-detail-overlay .mdo-badge.active { background:rgba(251,191,36,0.18); color:#fbbf24; }
        .map-detail-overlay .mdo-close { position:absolute; top:6px; right:8px; background:none; border:none; color:rgba(203,213,225,0.6); cursor:pointer; font-size:0.85rem; padding:2px 4px; }
        .map-detail-overlay .mdo-close:hover { color:#fff; }
        .info-chip {
            display:inline-flex; align-items:center; gap:0.35rem;
            font-size:0.75rem; padding:0.25rem 0.65rem;
            background:rgba(255,255,255,0.15); border-radius:999px;
            border:1px solid rgba(255,255,255,0.25);
        }
        .evidence-thumb {
            cursor:pointer; transition:transform 0.2s ease, box-shadow 0.2s ease;
            border-radius:0.6rem; overflow:hidden;
            width:100%; height:260px; object-fit:cover;
        }
        .evidence-thumb:hover { transform:scale(1.02); box-shadow:0 8px 25px rgba(0,0,0,0.15); }
        .section-group { margin-bottom:0.5rem; }
        .section-group:last-child { margin-bottom:0; }
        .section-group-label {
            font-size:0.65rem; font-weight:700; text-transform:uppercase;
            letter-spacing:0.05em; color:#94a3b8; margin-bottom:0.25rem;
        }
        .note-entry {
            padding:0.75rem; border-radius:0.6rem; background:#f8fafc;
            border-left:3px solid #2563eb; margin-bottom:0.5rem;
        }
        .note-entry:last-child { margin-bottom:0; }
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

<div class="container py-4 py-lg-5">
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show animate-on-load" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="text-center mb-5 animate-on-load">
                <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-search text-info" style="font-size: 2rem;"></i>
                </div>
                <h1 class="h2 fw-bold">Track Report Parking</h1>
                <p class="text-muted">Enter your report number to check the status of your report.</p>
            </div>

            <div class="card stat-card mb-4 animate-on-load">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('citizen.report-parking.track') }}" class="row g-3">
                        <div class="col-12 col-md-9">
                            <label class="form-label visually-hidden">Reference Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                <input type="text" name="reference" class="form-control form-control-lg" placeholder="Enter report number (e.g., RPT-20250115-A1B2)" value="{{ request('reference') }}" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                                <i class="bi bi-search me-1"></i> Track
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if (request('reference'))
                @if ($requestInfo)
                    <div class="card stat-card mb-4 animate-on-load">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <strong>Request Details</strong>
                            <span class="badge {{ $requestInfo->getStatusBadgeClass() }} fs-6">{{ $requestInfo->getStatusLabel() }}</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="text-muted small d-block">Report Number</div>
                                    <div class="fw-bold fs-5">{{ $requestInfo->report_number }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small d-block">Submitted</div>
                                    <div class="fw-bold">{{ $requestInfo->created_at->format('M d, Y h:i A') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small d-block">Vehicle Plate</div>
                                    <div class="fw-bold">{{ $requestInfo->vehicle_plate }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small d-block">Vehicle</div>
                                    <div>{{ $requestInfo->vehicle_description ?? '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small d-block">Location</div>
                                    <div>{{ $requestInfo->location_address }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small d-block">Coordinates</div>
                                    <div class="font-monospace small">{{ $requestInfo->latitude }}, {{ $requestInfo->longitude }}</div>
                                </div>
                                @if (in_array($requestInfo->status, ['accepted', 'rejected', 'resolved']))
                                    <div class="col-md-6">
                                        <div class="text-muted small d-block">Processed By</div>
                                        <div>{{ $requestInfo->processedBy?->name ?? 'System' }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted small d-block">Processed At</div>
                                        <div>{{ $requestInfo->processed_at?->format('M d, Y h:i A') ?? '—' }}</div>
                                    </div>
                                @endif
                                @if ($requestInfo->status === 'rejected')
                                    <div class="col-12">
                                        <div class="text-muted small d-block">Rejection Reason</div>
                                        <div class="alert alert-warning">{{ $requestInfo->rejection_reason }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card stat-card mb-4 animate-on-load">
                        <div class="card-header bg-white"><strong>Status Timeline</strong></div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex align-items-center gap-2">
                                    <span class="badge bg-primary rounded-circle p-1" style="width:10px;height:10px;"></span>
                                    <div>
                                        <small class="fw-semibold d-block">Submitted</small>
                                        <small class="text-muted">{{ $requestInfo->created_at->format('M d, Y h:i A') }}</small>
                                    </div>
                                </div>
                                @if ($requestInfo->status !== 'pending')
                                    <div class="list-group-item d-flex align-items-center gap-2">
                                        <span class="badge bg-info rounded-circle p-1" style="width:10px;height:10px;"></span>
                                        <div>
                                            <small class="fw-semibold d-block">Under Review</small>
                                            <small class="text-muted">{{ $requestInfo->processed_at?->format('M d, Y h:i A') ?? '—' }}</small>
                                        </div>
                                    </div>
                                @endif
                                @if (in_array($requestInfo->status, ['accepted', 'rejected', 'resolved']))
                                    <div class="list-group-item d-flex align-items-center gap-2">
                                        <span class="badge {{ $requestInfo->status === 'accepted' ? 'bg-success' : 'bg-danger' }} rounded-circle p-1" style="width:10px;height:10px;"></span>
                                        <div>
                                            <small class="fw-semibold d-block">{{ $requestInfo->status === 'accepted' ? 'Accepted' : 'Rejected' }}</small>
                                            <small class="text-muted">{{ $requestInfo->processed_at?->format('M d, Y h:i A') ?? '—' }}</small>
                                        </div>
                                    </div>
                                @endif
                                @if ($requestInfo->status === 'resolved')
                                    <div class="list-group-item d-flex align-items-center gap-2">
                                        <span class="badge bg-success rounded-circle p-1" style="width:10px;height:10px;"></span>
                                        <div>
                                            <small class="fw-semibold d-block">Clamping Resolved</small>
                                            <small class="text-muted">{{ $requestInfo->updated_at?->format('M d, Y h:i A') ?? '—' }}</small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($requestInfo->evidence_photo)
                        <div class="card stat-card mb-4 animate-on-load">
                            <div class="card-header bg-white"><strong>Submitted Evidence</strong></div>
                            <div class="card-body text-center">
                                @if (\App\Services\SupabaseStorage::has($requestInfo->evidence_photo))
                                    <img src="{{ \App\Services\SupabaseStorage::publicUrl($requestInfo->evidence_photo) }}" alt="Evidence" class="rounded border" style="max-height:300px;">
                                    <small class="text-muted d-block mt-2">Submitted photo evidence</small>
                                @else
                                    <div class="text-muted small fst-italic">Evidence file unavailable</div>
                                @endif
                            </div>
                        </div>
                    @endif
                @else
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        No citizen report found with report number: <strong>{{ request('reference') }}</strong>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<script src="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const bs = window.bootstrap;
    if (!bs) return;

    const navCollapse = document.getElementById('navbarNav');
    if (navCollapse && !bs.Collapse.getInstance(navCollapse)) {
        new bs.Collapse(navCollapse, { toggle: false });
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bs.Tooltip(el);
    });
});
</script>
@push('scripts')
<script src="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js"></script>
@endpush
</body>
</html>