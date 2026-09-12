@extends('layouts.app')

@section('title', $citation->citation_number)

@push('styles')
@if ($citation->latitude && $citation->longitude)
<style>
    #citation-map { width:100%; height:360px; border-radius:0.75rem; position:relative; }
    .map-detail-overlay {
        position:absolute; bottom:12px; left:12px; z-index:10;
        background:rgba(15,23,42,0.92); backdrop-filter:blur(12px);
        border:1px solid rgba(255,255,255,0.12); border-radius:0.75rem;
        padding:0.85rem 1rem; color:#e2e8f0; font-family:system-ui,sans-serif;
        max-width:300px; pointer-events:auto;
    }
    .map-detail-overlay .mdo-title { font-weight:700; font-size:0.9rem; margin-bottom:0.4rem; display:flex; align-items:center; gap:0.4rem; }
    .map-detail-overlay .mdo-row { display:flex; justify-content:space-between; padding:0.15rem 0; font-size:0.75rem; }
    .map-detail-overlay .mdo-row .mdo-lbl { color:rgba(148,163,184,0.9); }
    .map-detail-overlay .mdo-row .mdo-val { font-weight:600; text-align:right; }
    .map-detail-overlay .mdo-close { position:absolute; top:6px; right:8px; background:none; border:none; color:rgba(203,213,225,0.6); cursor:pointer; font-size:0.85rem; padding:2px 4px; }
    .map-detail-overlay .mdo-close:hover { color:#fff; }
</style>
@endif
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $citation->citation_number }}</h1>
        <p class="text-muted mb-0">{{ $citation->violationType->name }}</p>
    </div>
    <span class="badge {{ $citation->status->badgeClass() }} fs-6">{{ $citation->status->label() }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card stat-card mb-4">
            <div class="card-header bg-white"><strong>Citation Details</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong class="text-muted small d-block">Vehicle</strong>{{ $citation->vehicle_plate }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Driver</strong>{{ $citation->driver_name ?? '—' }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Penalty</strong>₱{{ number_format($citation->penalty_amount, 2) }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Due Date</strong>{{ $citation->due_date->format('M d, Y') }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Issued By</strong>{{ $citation->enforcer->name }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Issued At</strong>{{ $citation->issued_at->format('M d, Y h:i A') }}</div>
                    <div class="col-12"><strong class="text-muted small d-block">Location</strong>{{ $citation->location ?? '—' }} @if (!$citation->latitude || !$citation->longitude)<span class="text-muted small d-block mt-1">Coordinates not captured</span>@endif</div>
                    @if ($citation->notes)
                        <div class="col-12"><strong class="text-muted small d-block">Notes</strong>{{ $citation->notes }}</div>
                    @endif
                </div>
            </div>
        </div>
        @if ($citation->latitude && $citation->longitude)
            <div class="card stat-card mb-4">
                <div class="card-header bg-white"><strong><i class="bi bi-geo-alt me-2"></i>Location Map</strong></div>
                <div class="card-body position-relative">
                    <div id="citation-map"></div>
                    <div class="map-detail-overlay" id="citation-map-detail"></div>
                </div>
            </div>
        @endif
        @if ($citation->evidence->isNotEmpty())
            <div class="card stat-card">
                <div class="card-header bg-white"><strong>Evidence</strong></div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @foreach ($citation->evidence as $item)
                        <a href="{{ asset('storage/'.$item->file_path) }}" target="_blank">
                            <img src="{{ asset('storage/'.$item->file_path) }}" alt="Evidence" class="rounded border" style="height:120px">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card stat-card mb-3 text-center">
            <div class="card-body py-3">
                <div class="bg-white p-2 d-inline-block rounded border">
                    {!! $citation->getQRCodeSvg(180) !!}
                </div>
                <div class="small text-muted mt-2">Scan to view citation ticket</div>
            </div>
        </div>

        @if ($citation->violationType->is_impoundable && $citation->clampingRecords->isEmpty() && auth()->user()->isRole(App\Enums\Role::SuperAdmin, App\Enums\Role::Administrator, App\Enums\Role::Enforcer))
            <div class="card stat-card mb-3 border-warning">
                <div class="card-body text-center">
                    <form method="POST" action="{{ route('citations.refer-impounding', $citation) }}">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100 fw-semibold">
                            <i class="bi bi-truck-front me-2"></i>Refer for Impounding
                        </button>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            This violation is eligible for impounding.
                        </small>
                    </form>
                </div>
            </div>
        @endif

        @if ($citation->payment)
            <div class="card stat-card mb-3">
                <div class="card-header bg-white"><strong>Payment</strong></div>
                <div class="card-body">
                    <p class="mb-1">Receipt: <a href="{{ route('payments.show', $citation->payment) }}">{{ $citation->payment->receipt_number }}</a></p>
                    @if ($citation->payment->paid_at)
                        <p class="mb-0 text-muted small">Paid {{ $citation->payment->paid_at->format('M d, Y') }}</p>
                    @else
                        <p class="mb-0 text-muted small"><span class="text-warning">Payment pending</span> — {{ $citation->payment->online_payment_method ? 'online checkout in progress' : 'not yet recorded' }}</p>
                    @endif
                </div>
            </div>
        @elseif ($citation->isPayable())
            @can('create', App\Models\Payment::class)
                <div class="card stat-card">
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('payments.create', ['citation_id' => $citation->id]) }}" class="btn btn-success w-100">Record Payment</a>
                        <form method="POST" action="{{ route('citations.checkout', $citation) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-credit-card me-2"></i>Pay Online
                            </button>
                        </form>
                    </div>
                </div>
            @elseif (auth()->user()->isRole(App\Enums\Role::VehicleOwner))
                <div class="card stat-card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('citations.checkout', $citation) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-credit-card me-2"></i>Pay Online via GCash/Maya
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if ($citation->latitude && $citation->longitude)
<script src="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js"></script>
<script>
    (function () {
        const lat = {{ $citation->latitude }};
        const lng = {{ $citation->longitude }};
        const citationNumber = {!! json_encode($citation->citation_number) !!};
        const location = {!! json_encode($citation->location) !!};
        const vehiclePlate = {!! json_encode($citation->vehicle_plate ?? 'N/A') !!};

        const map = new maplibregl.Map({
            container: 'citation-map',
            style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
            center: [lng, lat],
            zoom: 15,
        });

        map.addControl(new maplibregl.NavigationControl(), 'top-right');

        map.on('load', function () {
            map.addSource('pin', {
                type: 'geojson',
                data: {
                    type: 'FeatureCollection',
                    features: [{
                        type: 'Feature',
                        geometry: { type: 'Point', coordinates: [lng, lat] },
                        properties: {},
                    }],
                },
            });

            map.addLayer({
                id: 'pin-layer',
                type: 'circle',
                source: 'pin',
                paint: {
                    'circle-radius': 10,
                    'circle-color': '#2563eb',
                    'circle-stroke-width': 3,
                    'circle-stroke-color': '#fff',
                },
            });

            const detailEl = document.getElementById('citation-map-detail');
            if (detailEl) {
                detailEl.innerHTML = `
                    <button class="mdo-close" onclick="document.getElementById('citation-map-detail').style.display='none'">&times;</button>
                    <div class="mdo-title">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#38bdf8" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>
                        Citation Location
                    </div>
                    <div class="mdo-row"><span class="mdo-lbl">Citation</span><span class="mdo-val">${citationNumber}</span></div>
                    <div class="mdo-row"><span class="mdo-lbl">Vehicle</span><span class="mdo-val">${vehiclePlate}</span></div>
                    <div class="mdo-row"><span class="mdo-lbl">Location</span><span class="mdo-val">${location || '—'}</span></div>
                    <div class="mdo-row"><span class="mdo-lbl">Coordinates</span><span class="mdo-val">${lat.toFixed(7)}, ${lng.toFixed(7)}</span></div>
                `;
            }
        });
    })();
</script>
@endif
@endpush
