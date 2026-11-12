@extends('layouts.app')

@section('title', 'Impound Vehicle')

@section('content')
<div class="imp-dash">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0"><i class="bi bi-truck-front me-1"></i>Impound Vehicle</h1>
            <p class="text-muted small mb-0">Select an eligible citation, review its details and fees, then record the impound.</p>
        </div>
        <a href="{{ route('impounding.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Impounding
        </a>
    </div>

    {{-- Toolbar — same design as the impounding index --}}
    <form id="eligibleForm" method="GET" action="{{ route('impounding.create') }}" class="imp-toolbar" aria-label="Eligible citations search and filters">
        <input type="hidden" name="citation_id" value="{{ $citation->id ?? '' }}">

        <div class="imp-filter-wrap">
            <button type="button" id="impFilterBtn" class="imp-filter-btn" aria-expanded="false" aria-controls="impFilterMenu">
                Filters <span class="imp-filter-caret"></span>
            </button>
            <div id="impFilterMenu" class="imp-filter-menu" role="menu" aria-label="Eligible citations filters">
                <button type="button" class="imp-filter-option{{ request('eligible_status') === '' || request('eligible_status') === null ? ' selected' : '' }}" data-status="" role="menuitem">All Statuses</button>
                <button type="button" class="imp-filter-option{{ request('eligible_status') === 'issued' ? ' selected' : '' }}" data-status="issued" role="menuitem">Issued</button>
                <button type="button" class="imp-filter-option{{ request('eligible_status') === 'overdue' ? ' selected' : '' }}" data-status="overdue" role="menuitem">Overdue</button>
            </div>
            <input type="hidden" name="eligible_status" id="eligibleStatus" value="{{ request('eligible_status') }}">
        </div>

        <label class="imp-search" aria-label="Search eligible citations">
            <button type="submit" class="imp-search-icon-box" aria-label="Apply search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="eligible_search" id="impEligibleSearchInput" placeholder="Citation #, plate, or driver..." value="{{ request('eligible_search') }}" autocomplete="off">
        </label>
    </form>

    <section class="std-table-wrap" aria-label="Citations eligible for impounding">
        <table class="std-table table-cards" aria-label="Citations eligible for impounding">
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
                    <tr data-citation-row data-id="{{ $eligible->id }}" @if (($citation->id ?? null) === $eligible->id) class="row-selected" @endif>
                        <th scope="row" data-label="Citation #">{{ $eligible->citation_number }}</th>
                        <td data-label="Plate">{{ $eligible->vehicle_plate }}</td>
                        <td data-label="Violation">{{ $eligible->violationLabel() }}</td>
                        <td data-label="Towing">₱{{ number_format($rowFees['towing_fee'] ?? 0, 2) }}</td>
                        <td data-label="Storage/Day">₱{{ number_format($rowFees['storage_fee_per_day'] ?? 0, 2) }}</td>
                        <td data-label="Admin Fee">₱{{ number_format($rowFees['admin_fee'] ?? 0, 2) }}</td>
                        <td data-label="Status"><span class="std-pill {{ $eligible->status->badgeClass() }}">{{ $eligible->status->label() }}</span></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger js-select-citation" data-id="{{ $eligible->id }}">
                                @if (($citation->id ?? null) === $eligible->id) Selected @else Select @endif
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="std-empty">No citations are currently eligible for impounding. Citations become eligible here once they have an impoundable, unpaid violation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <footer class="std-table-footer">
        <span>{{ $eligibleCitations->count() }} eligible citation{{ $eligibleCitations->count() === 1 ? '' : 's' }} found</span>
    </footer>

    {{-- Review panel: full citation details + fees + record form (appears on select) --}}
    <div class="card stat-card mt-4" id="reviewPanel" @if (! $citation) style="display:none;" @endif>
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-file-earmark-text me-1"></i>Selected Citation — Review</strong>
            <span class="std-pill" id="reviewStatus">—</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4"><span class="text-muted small d-block">Citation #</span><strong id="reviewNumber">—</strong></div>
                <div class="col-md-4"><span class="text-muted small d-block">Vehicle Plate</span><strong id="reviewPlate">—</strong></div>
                <div class="col-md-4"><span class="text-muted small d-block">Violation</span><span id="reviewViolation">—</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Penalty</span><span id="reviewPenalty">—</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Issued</span><span id="reviewIssued">—</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Due</span><span id="reviewDue">—</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Vehicle</span><span id="reviewVehicle">—</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Driver</span><span id="reviewDriver">—</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Issuing Officer</span><span id="reviewOfficer">—</span></div>
                <div class="col-12"><span class="text-muted small d-block">Location</span><span id="reviewLocation">—</span></div>
            </div>

            <h6 class="fw-bold mb-2"><i class="bi bi-gear me-1"></i>Fees (Based on Violation Type)</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card text-center border-0" style="background:#f8fafc;">
                        <div class="card-body py-2">
                            <div class="fs-5 fw-800 text-danger" id="feeTowing">—</div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Towing Fee</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center border-0" style="background:#f8fafc;">
                        <div class="card-body py-2">
                            <div class="fs-5 fw-800 text-warning" id="feeStorage">—</div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Storage/Day</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center border-0" style="background:#f8fafc;">
                        <div class="card-body py-2">
                            <div class="fs-5 fw-800 text-info" id="feeAdmin">—</div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Admin Fee</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-info py-2">
                <small><i class="bi bi-info-circle me-1"></i>
                    <strong>Grace Period:</strong> {{ config('itevcms.impounding.grace_hours', 24) }} hours before storage fees begin.
                    Fees based on violation: <strong id="feeViolationCode">default</strong>.
                    Evidence photos are taken from the selected citation's own records.
                </small>
            </div>

            <form method="POST" action="{{ route('impounding.store') }}">@csrf
                <input type="hidden" name="citation_id" id="recordCitationId" value="{{ $citation->id ?? '' }}">
                <input type="hidden" name="vehicle_plate" id="recordPlate" value="{{ $citation->vehicle_plate ?? '' }}">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Impound location" value="{{ old('location') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger mt-3"><i class="bi bi-truck-front me-1"></i>Impound Vehicle</button>
                <a href="{{ route('impounding.index') }}" class="btn btn-link mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $reviewData = $eligibleCitations->mapWithKeys(fn ($c) => [$c->id => [
        'number' => $c->citation_number,
        'plate' => $c->vehicle_plate,
        'statusLabel' => $c->status->label(),
        'statusClass' => $c->status->badgeClass(),
        'violation' => $c->violationLabel(),
        'penalty' => '₱' . number_format((float) $c->penalty_amount, 2),
        'issued' => $c->issued_at?->format('M d, Y') ?? '—',
        'due' => $c->due_date?->format('M d, Y') ?? '—',
        'vehicle' => trim(collect([$c->vehicle_make, $c->vehicle_model, $c->vehicle_type, $c->vehicle_color])->filter()->implode(' ')) ?: '—',
        'driver' => $c->driver_name ?: '—',
        'officer' => $c->enforcer?->name ?? '—',
        'location' => $c->location ?: '—',
        'code' => $c->violationType?->code ?? 'default',
    ]])->all();
@endphp
<script>
    const feeSchedule = @json($feeSchedule);
    const reviewData = @json($reviewData);
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toolbar — same behaviour as the impounding index
    const form = document.getElementById('eligibleForm');
    const filterBtn = document.getElementById('impFilterBtn');
    const filterMenu = document.getElementById('impFilterMenu');
    const statusInput = document.getElementById('eligibleStatus');
    const searchInput = document.getElementById('impEligibleSearchInput');

    if (form && filterBtn && filterMenu && statusInput) {
        filterBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = filterMenu.classList.toggle('open');
            filterBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.imp-filter-wrap')) {
                filterMenu.classList.remove('open');
                filterBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                filterMenu.classList.remove('open');
                filterBtn.setAttribute('aria-expanded', 'false');
            }
        });

        filterMenu.querySelectorAll('.imp-filter-option').forEach((opt) => {
            opt.addEventListener('click', () => {
                statusInput.value = opt.dataset.status;
                form.submit();
            });
        });
    }

    if (searchInput) {
        let debounce;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => form.submit(), 300);
        });
    }

    const peso = (value) => value ? '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
    const reviewPanel = document.getElementById('reviewPanel');
    const slots = ['Number', 'Plate', 'Violation', 'Penalty', 'Issued', 'Due', 'Vehicle', 'Driver', 'Officer', 'Location'];

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

    function renderReview(data) {
        slots.forEach(function (name) {
            const el = document.getElementById('review' + name);
            if (el) el.textContent = data[name.toLowerCase()] ?? '—';
        });
        const statusEl = document.getElementById('reviewStatus');
        statusEl.textContent = data.statusLabel;
        statusEl.className = 'std-pill ' + data.statusClass;
        applyFees(data.code || 'default');

        document.getElementById('recordCitationId').value = data.id ?? '';
        document.getElementById('recordPlate').value = data.plate ?? '';
    }

    // Pre-fill the review from a deep link (?citation_id=)
    const selectedRow = document.querySelector('[data-citation-row].row-selected');
    if (selectedRow) {
        const data = reviewData[selectedRow.dataset.id];
        if (data) renderReview({ id: selectedRow.dataset.id, ...data });
        selectedRow.querySelector('.js-select-citation')?.classList.add('is-selected');
    }

    document.querySelectorAll('.js-select-citation').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const row = btn.closest('[data-citation-row]');
            const data = reviewData[btn.dataset.id];
            if (!row || !data) return;

            renderReview({ id: btn.dataset.id, ...data });

            document.querySelectorAll('[data-citation-row]').forEach(function (r) {
                r.classList.remove('row-selected');
                r.querySelector('.js-select-citation')?.classList.remove('is-selected');
                r.querySelector('.js-select-citation')?.textContent = 'Select';
            });
            row.classList.add('row-selected');
            btn.classList.add('is-selected');
            btn.textContent = 'Selected';

            // The review panel only appears once a citation is selected.
            reviewPanel.style.display = '';
            reviewPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});
</script>
@endpush
