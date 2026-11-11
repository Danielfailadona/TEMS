@extends('layouts.app')

@section('title', 'Impound Vehicle')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Impound Vehicle</h1>
        <div class="text-muted small">
            Select an eligible citation below, then record the impound. The decision is held at the Administrator side.
            <button type="button" class="btn btn-sm btn-link p-0 align-baseline" data-bs-toggle="collapse" data-bs-target="#impEligibleList">Show eligible violations</button>
        </div>
        <div class="collapse mt-2" id="impEligibleList">
            <div class="card card-body p-2">
                <ul class="mb-0 small ps-3">
                    <li>Unregistered or Delinquent Vehicles: Driving with expired, invalid, or missing registration (₱10,000 fine plus impoundment until properly registered and inspected).</li>
                    <li>Unauthorized Modifications: Changing color, body, engine/chassis without prior LTO approval and registration update.</li>
                    <li>Tampered or Improvised Plates: Using unauthorized, fake, or improperly attached plates, or illegal transfer of plates/stickers.</li>
                    <li>Suspended or Revoked CR/OR: Operating while CR/OR is suspended or revoked.</li>
                    <li>Defective or Missing Parts: Operating with missing/unsafe required parts that pose public safety hazard.</li>
                    <li>Colorum Operations: Operating PUVs without valid CPC or franchise.</li>
                    <li>Fake Driver's Licenses or Documents: Presenting fraudulent licenses, fake OR/CR, or tampered franchising documents.</li>
                    <li>Major Driving & Crime Violations: Using vehicle in commission of crime or severe offenses upon court conviction.</li>
                </ul>
            </div>
        </div>
    </div>
    <a href="{{ route('impounding.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Impounding</a>
</div>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-truck-front me-1"></i>Citations Eligible for Impounding</h2>
        <p class="text-muted small mb-0">Impoundable violations, unpaid and not yet impounded. Select a citation to pre-fill the form and its fees.</p>
    </div>
    <form method="GET" action="{{ route('impounding.create') }}" class="d-flex flex-wrap gap-2" aria-label="Search eligible citations">
        <input type="hidden" name="citation_id" value="{{ $citation->id ?? '' }}">
        <select name="eligible_status" class="form-select form-select-sm" style="max-width:150px;" aria-label="Filter by status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="issued" @selected(request('eligible_status') === 'issued')>Issued</option>
            <option value="overdue" @selected(request('eligible_status') === 'overdue')>Overdue</option>
        </select>
        <input type="search" name="eligible_search" class="form-control form-control-sm" style="max-width:220px;" placeholder="Citation #, plate, driver..." value="{{ request('eligible_search') }}" aria-label="Search eligible citations">
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
</div>

<section class="std-table-wrap mb-4" aria-label="Citations eligible for impounding">
    <table class="std-table table-cards">
        <thead>
            <tr>
                <th scope="col">Citation #</th>
                <th scope="col">Plate</th>
                <th scope="col">Violation</th>
                <th scope="col">Towing</th>
                <th scope="col">Storage/Day</th>
                <th scope="col">Admin Fee</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="visually-hidden">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($eligibleCitations as $eligible)
                @php
                    $rowFees = config('itevcms.impounding.violation_fees')[$eligible->violationType?->code ?? 'default'] ?? config('itevcms.impounding.violation_fees.default');
                @endphp
                <tr data-citation-row data-id="{{ $eligible->id }}" data-plate="{{ $eligible->vehicle_plate }}"
                    data-number="{{ $eligible->citation_number }}" data-label-text="{{ $eligible->violationLabel() }}"
                    data-code="{{ $eligible->violationType?->code ?? 'default' }}"
                    @if (($citation->id ?? null) === $eligible->id) class="table-active" @endif>
                    <th scope="row" data-label="Citation #">{{ $eligible->citation_number }}</th>
                    <td data-label="Plate">{{ $eligible->vehicle_plate }}</td>
                    <td data-label="Violation">{{ $eligible->violationLabel() }}</td>
                    <td data-label="Towing">₱{{ number_format($rowFees['towing_fee'] ?? 0, 2) }}</td>
                    <td data-label="Storage/Day">₱{{ number_format($rowFees['storage_fee_per_day'] ?? 0, 2) }}</td>
                    <td data-label="Admin Fee">₱{{ number_format($rowFees['admin_fee'] ?? 0, 2) }}</td>
                    <td data-label="Status"><span class="std-pill {{ $eligible->status->badgeClass() }}">{{ $eligible->status->label() }}</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger js-select-citation">
                            <i class="bi bi-check2 me-1"></i>Select
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="std-empty">No citations are currently eligible for impounding. Citations become eligible here once they have an impoundable, unpaid violation.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<form method="POST" action="{{ route('impounding.store') }}" enctype="multipart/form-data" id="impounding-form" @if (! $citation) style="display:none;" @endif>
    @csrf
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card stat-card mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Vehicle Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Vehicle Plate <span class="text-danger">*</span></label>
                        <input type="hidden" name="citation_id" value="{{ $citation->id ?? '' }}">
                        <input type="text" name="vehicle_plate" class="form-control text-uppercase @error('vehicle_plate') is-invalid @enderror" placeholder="e.g. ABC-1234" value="{{ old('vehicle_plate', $citation->vehicle_plate ?? '') }}" readonly required>
                        @error('vehicle_plate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="small text-muted mt-1" id="selectedCitation" @if (! $citation) style="display:none;" @endif>
                            @if ($citation)
                                Citation: {{ $citation->citation_number }} — {{ $citation->violationLabel() }} ({{ ucfirst($citation->status->value) }})
                            @endif
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Impound location" value="{{ old('location') }}">
                    </div>
                </div>
            </div>

            <div class="card stat-card mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Fees (Based on Violation Type)</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card stat-card-sm text-center">
                                <div class="stat-value text-danger" id="feeTowing">{{ $fees['towing_fee'] ? '₱' . number_format($fees['towing_fee'], 2) : '—' }}</div>
                                <div class="stat-label">Towing Fee</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card-sm text-center">
                                <div class="stat-value text-warning" id="feeStorage">{{ $fees['storage_fee_per_day'] ? '₱' . number_format($fees['storage_fee_per_day'], 2) : '—' }}</div>
                                <div class="stat-label">Storage/Day</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card-sm text-center">
                                <div class="stat-value text-info" id="feeAdmin">{{ $fees['admin_fee'] ? '₱' . number_format($fees['admin_fee'], 2) : '—' }}</div>
                                <div class="stat-label">Admin Fee</div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Grace Period:</strong> {{ config('itevcms.impounding.grace_hours', 24) }} hours before storage fees begin.
                        <br>Fees based on violation: <strong id="feeViolationCode">{{ $citation?->violationType?->code ?? 'default' }}</strong>
                    </div>
                </div>
            </div>

            <div class="card stat-card mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-camera me-2"></i>Evidence</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-camera me-1"></i>Evidence photos are taken from the selected citation's own records.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card stat-card">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location Map</h5>
                </div>
                <div class="card-body p-0">
                    <div id="impounding-map" class="zone-map"></div>
                </div>
                <div class="card-footer bg-transparent border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Reference map. Manage zones for precise impound areas.</span>
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
const DEFAULT_CENTER = [123.79554621067624, 10.245912335483869];
const DEFAULT_ZOOM = 12;

let map = null;

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
}

document.addEventListener('DOMContentLoaded', function () {
    initMap();

    const feeSchedule = @json($feeSchedule);
    const peso = (value) => value ? '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';

    function showSelected(text) {
        const indicator = document.getElementById('selectedCitation');
        if (!indicator) return;
        indicator.textContent = text;
        indicator.style.display = 'block';
    }

    function applyFees(code) {
        const fees = feeSchedule[code] || feeSchedule['default'] || { towing_fee: 0, storage_fee_per_day: 0, admin_fee: 0 };
        const towing = document.getElementById('feeTowing');
        const storage = document.getElementById('feeStorage');
        const admin = document.getElementById('feeAdmin');
        const codeEl = document.getElementById('feeViolationCode');
        if (towing) towing.textContent = peso(fees.towing_fee);
        if (storage) storage.textContent = peso(fees.storage_fee_per_day);
        if (admin) admin.textContent = peso(fees.admin_fee);
        if (codeEl) codeEl.textContent = code;
    }

    // Pre-fill from a deep link (?citation_id=) — the form is already visible.
    const selectedRow = document.querySelector('[data-citation-row].table-active');
    if (selectedRow) {
        showSelected('Citation: ' + selectedRow.dataset.number + ' — ' + (selectedRow.dataset.labelText || ''));
    }

    document.querySelectorAll('.js-select-citation').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const row = btn.closest('[data-citation-row]');
            const plateInput = document.querySelector('input[name="vehicle_plate"]');
            const citationInput = document.querySelector('input[name="citation_id"]');
            if (!row || !plateInput || !citationInput) return;

            citationInput.value = row.dataset.id;
            plateInput.value = row.dataset.plate;
            showSelected('Citation: ' + row.dataset.number + ' — ' + (row.dataset.labelText || ''));
            applyFees(row.dataset.code);

            document.querySelectorAll('[data-citation-row]').forEach(function (r) {
                r.classList.remove('table-active');
            });
            row.classList.add('table-active');

            // The record form only appears once a citation is selected.
            const form = document.getElementById('impounding-form');
            if (form) form.style.display = '';

            plateInput.focus();
            form?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});
</script>
@endpush
