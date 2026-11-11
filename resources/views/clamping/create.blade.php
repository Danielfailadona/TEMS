@extends('layouts.app')

@section('title', 'Record Clamp')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-lock me-1"></i>Citations Eligible for Clamping</h2>
        <p class="text-muted small mb-0">Clampable violations, unpaid and not yet clamped. Select a citation to pre-fill the form below.</p>
    </div>
    <form method="GET" action="{{ route('clamping.create') }}" class="d-flex flex-wrap gap-2" aria-label="Search eligible citations">
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

<section class="std-table-wrap mb-4" aria-label="Citations eligible for clamping">
    <table class="std-table table-cards">
        <thead>
            <tr>
                <th scope="col">Citation #</th>
                <th scope="col">Plate</th>
                <th scope="col">Violation</th>
                <th scope="col">Issued</th>
                <th scope="col">Due</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="visually-hidden">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($eligibleCitations as $eligible)
                <tr data-citation-row data-id="{{ $eligible->id }}" data-plate="{{ $eligible->vehicle_plate }}"
                    data-number="{{ $eligible->citation_number }}" data-label-text="{{ $eligible->violationLabel() }}"
                    @if (($citation->id ?? null) === $eligible->id) class="table-active" @endif>
                    <th scope="row" data-label="Citation #">{{ $eligible->citation_number }}</th>
                    <td data-label="Plate">{{ $eligible->vehicle_plate }}</td>
                    <td data-label="Violation">{{ $eligible->violationLabel() }}</td>
                    <td data-label="Issued">{{ $eligible->issued_at->format('M d, Y') }}</td>
                    <td data-label="Due">{{ $eligible->due_date->format('M d, Y') }}</td>
                    <td data-label="Status"><span class="std-pill {{ $eligible->status->badgeClass() }}">{{ $eligible->status->label() }}</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger js-select-citation">
                            <i class="bi bi-check2 me-1"></i>Select
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="std-empty">No citations are currently eligible for clamping. Citations become eligible here once they have a clampable, unpaid violation.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="card stat-card" id="clampingForm" @if (! $citation) style="display:none;" @endif><div class="card-body">
    <form method="POST" action="{{ route('clamping.store') }}" enctype="multipart/form-data">@csrf
        <div class="mb-3">
            <div class="card border-primary bg-primary-subtle">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="fw-semibold">Clamping Eligibility Reference</small>
                            <div class="small mt-1">
                                <strong>Core Violations Eligible for Clamping:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    <li>Posted "No Parking" or "Tow-Away" Zones: Any road, street, or Mabuhay lane designated with official no-parking signs.</li>
                                    <li>Sidewalks and Paths: Parked on sidewalks, walking paths, pedestrian crosswalks, or alleys.</li>
                                    <li>Vital Entrances & Driveways: Blocking an authorized driveway or parked within four meters of a hospital, clinic, or fire station entrance.</li>
                                    <li>Intersections & Curves: Parked within six meters of any curved lane or street intersection.</li>
                                    <li>Fire Hydrants: Stopped or parked within four meters of a fire hydrant.</li>
                                    <li>Bicycle Lanes: Straddling or completely obstructing a designated active transport lane.</li>
                                    <li>Double Parking: Parked on the roadside of any legally parked vehicle.</li>
                                    <li>Bridges: Parked at the foot or directly on top of a bridge.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle Plate</label>
                <input type="hidden" name="citation_id" value="{{ $citation->id ?? '' }}">
                <input type="text" name="vehicle_plate" class="form-control" value="{{ old('vehicle_plate', $citation->vehicle_plate ?? '') }}" placeholder="ABC-1234" readonly required>
                <div class="small text-muted mt-1" id="selectedCitation" @if (! $citation) style="display:none;" @endif>
                    @if ($citation)
                        Citation: {{ $citation->citation_number }} — {{ $citation->violationLabel() }} ({{ ucfirst($citation->status->value) }})
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
            <div class="col-12">
                <div class="alert alert-info mb-0 py-2">
                    <small><i class="bi bi-camera me-1"></i>Evidence photos are taken from the selected citation's own records.</small>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-danger mt-3">Record Clamp</button>
        <a href="{{ route('clamping.index') }}" class="btn btn-link">Cancel</a>
    </form>
</div></div>
@endsection

@push('scripts')
<script>
    (function () {
        const indicator = document.getElementById('selectedCitation');
        const formCard = document.getElementById('clampingForm');

        function showSelected(text) {
            if (!indicator) return;
            indicator.textContent = text;
            indicator.style.display = 'block';
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

                document.querySelectorAll('[data-citation-row]').forEach(function (r) {
                    r.classList.remove('table-active');
                });
                row.classList.add('table-active');

                // The record form only appears once a citation is selected.
                if (formCard) formCard.style.display = '';

                plateInput.focus();
                formCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    })();
</script>
@endpush
