@extends('layouts.app')

@section('title', 'Record Clamp')

@push('styles')
<style>
    .pym-dash {
        --pym-navy: #0d2b49;
        --pym-blue: #176ff2;
        --pym-ink: #182235;
        --pym-muted: #78869a;
        --pym-line: #dfe5eb;
    }
    .pym-dash .pym-toolbar { display: flex; align-items: center; gap: 16px; width: 100%; flex-wrap: wrap; }

    /* Filters dropdown — same as the clamping index */
    .pym-dash .pym-filter-wrap { position: relative; flex: 0 0 128px; }
    .pym-dash .pym-filter-button {
        height: 38px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
        background: #fff; border: 1px solid #d7dee7; color: #111827; font-weight: 650; font-size: 16px;
        border-radius: 4px; cursor: pointer;
    }
    .pym-dash .pym-filter-button svg { width: 15px; height: 15px; }
    .pym-dash .pym-filter-button[aria-expanded="true"] .pym-caret { transform: rotate(180deg); }
    .pym-dash .pym-caret { transition: transform .18s ease; }

    .pym-dash .pym-filter-panel {
        display: none; position: absolute; top: calc(100% + 8px); left: 0; z-index: 30;
        width: 340px; max-width: min(340px, 88vw); padding: 22px 18px 28px;
        background: #fff; border: 1px solid #dfe5eb; border-radius: 8px;
        box-shadow: 0 12px 32px rgba(13, 43, 72, .16), 0 2px 6px rgba(13, 43, 72, .08);
    }
    .pym-dash .pym-filter-panel.open { display: grid; gap: 22px; }
    .pym-dash .pym-field { border: 0; padding: 0; margin: 0; min-width: 0; }
    .pym-dash .pym-field__label { font-size: 16px; font-weight: 700; color: #000; padding: 0; margin-bottom: 10px; display: block; }

    .pym-dash .pym-select { position: relative; }
    .pym-dash .pym-select select {
        appearance: none; -webkit-appearance: none; width: 100%; height: 46px; padding: 0 44px 0 16px;
        background: #fff; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: var(--pym-ink);
    }
    .pym-dash .pym-select select:focus { border-color: #3b82f6; outline: 2px solid rgba(59,130,246,.25); outline-offset: 0; }
    .pym-dash .pym-select svg {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        width: 16px; height: 16px; color: var(--pym-ink); pointer-events: none;
    }

    .pym-dash .pym-panel-actions { display: flex; gap: 14px; margin-top: 4px; }
    .pym-dash .pym-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        height: 40px; padding: 0 14px; border-radius: 4px; border: 1px solid transparent;
        font-size: 11px; font-weight: 700; text-decoration: none; white-space: nowrap; cursor: pointer;
    }
    .pym-dash .pym-btn svg { width: 14px; height: 14px; }
    .pym-dash .pym-btn--primary { background: var(--pym-blue); color: #fff; }
    .pym-dash .pym-btn--primary:hover { background: #075edb; color: #fff; }
    .pym-dash .pym-btn--neutral { background: #6b7280; color: #fff; }
    .pym-dash .pym-btn--neutral:hover { background: #565d6a; color: #fff; }

    /* Search — same as the clamping index */
    .pym-dash .pym-search-box { display: flex; height: 38px; min-width: 100px; flex: 1; }
    .pym-dash .pym-search-submit {
        display: grid; place-items: center; width: 34px; flex: 0 0 34px; background: #fff;
        color: var(--pym-blue); border: 1px solid var(--pym-blue); border-radius: 4px 0 0 4px; cursor: pointer;
    }
    .pym-dash .pym-search-submit svg { width: 19px; height: 19px; stroke: currentColor; }
    .pym-dash .pym-search-box input {
        min-width: 0; width: 100%; border: 1px solid #d7dee7; border-left: 0; padding: 0 22px;
        outline: none; background: #fff; color: #263247; font-size: 16px; border-radius: 0 4px 4px 0;
    }
    .pym-dash .pym-search-box input:focus { border-color: #7aa9f8; box-shadow: inset 0 0 0 1px #7aa9f8; }

    /* Table — same as the clamping index */
    .pym-dash .pym-table-wrap { overflow-x: auto; margin-top: 18px; }
    .pym-dash .pym-table { width: 100%; border-collapse: collapse; font-size: 14px; color: var(--pym-ink); background: #fff; }
    .pym-dash .pym-table th,
    .pym-dash .pym-table td { padding: 10px 12px; border-bottom: 1px solid var(--pym-line); text-align: left; vertical-align: middle; }
    .pym-dash .pym-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--pym-muted); white-space: nowrap; }
    .pym-dash .pym-table tbody tr:hover { background: #f4f7fa; }
    .pym-dash .pym-table tbody tr.row-selected { background: #eef5ff; box-shadow: inset 3px 0 0 var(--pym-blue); }

    .pym-dash .pym-pill {
        display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
        border-radius: 7px; min-width: 40px; text-align: center; white-space: nowrap;
    }

    .pym-dash .pym-pill-btn {
        display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 14px;
        min-width: 100px;
        border: 1px solid var(--pym-line); border-radius: 15px; font-size: 12px; color: var(--pym-ink);
        text-decoration: none; background: #fff; cursor: pointer;
    }
    .pym-dash .pym-pill-btn:hover { background: #f4f7fa; }
    .pym-dash .pym-pill-btn.is-selected { background: var(--pym-blue); border-color: var(--pym-blue); color: #fff; }

    .pym-dash .pym-empty { padding: 30px; text-align: center; color: var(--pym-muted); }

    .pym-dash .pym-table-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; font-size: 13px; color: var(--pym-muted); }

    @media (max-width: 767.98px) {
        .pym-dash .pym-table { min-width: 0; }
    }
</style>
@endpush

@section('content')
<div class="pym-dash">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0"><i class="bi bi-lock me-1"></i>Record Clamp</h1>
            <p class="text-muted small mb-0">Select an eligible citation, review its details, then record the clamp.</p>
        </div>
        <a href="{{ route('clamping.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Clamping
        </a>
    </div>

    {{-- Toolbar — same design as the clamping index --}}
    <form id="eligibleForm" method="GET" action="{{ route('clamping.create') }}" class="pym-toolbar" aria-label="Eligible citations search and filters">
        <input type="hidden" name="citation_id" value="{{ $citation->id ?? '' }}">

        <div class="pym-filter-wrap">
            <button type="button" class="pym-filter-button" id="eligibleFilterBtn" aria-haspopup="dialog" aria-expanded="false" aria-controls="eligibleFilterPanel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filters
                <svg class="pym-caret" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 12.5 8 3l6 9.5H2Z"/></svg>
            </button>

            <div class="pym-filter-panel" id="eligibleFilterPanel" role="dialog" aria-label="Eligible citations filters">
                <fieldset class="pym-field">
                    <label class="pym-field__label" for="eligibleStatus">Status</label>
                    <div class="pym-select">
                        <select id="eligibleStatus" name="eligible_status">
                            <option value="">All Statuses</option>
                            <option value="issued" @selected(request('eligible_status') === 'issued')>Issued</option>
                            <option value="overdue" @selected(request('eligible_status') === 'overdue')>Overdue</option>
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </fieldset>

                <div class="pym-panel-actions">
                    <button class="pym-btn pym-btn--primary" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                        Apply Filter
                    </button>
                    <a class="pym-btn pym-btn--neutral" href="{{ route('clamping.create') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        Clear Filter
                    </a>
                </div>
            </div>
        </div>

        <label class="pym-search-box" aria-label="Search eligible citations">
            <button type="submit" class="pym-search-submit" aria-label="Search eligible citations">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="eligible_search" id="eligibleSearchInput" placeholder="Citation #, plate, or driver..." value="{{ request('eligible_search') }}" autocomplete="off">
        </label>
    </form>

    <section class="pym-table-wrap" aria-label="Citations eligible for clamping">
        <table class="pym-table table-cards">
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
                    <tr data-citation-row data-id="{{ $eligible->id }}" @if (($citation->id ?? null) === $eligible->id) class="row-selected" @endif>
                        <th scope="row" data-label="Citation #">{{ $eligible->citation_number }}</th>
                        <td data-label="Plate">{{ $eligible->vehicle_plate }}</td>
                        <td data-label="Violation">{{ $eligible->violationLabel() }}</td>
                        <td data-label="Issued">{{ $eligible->issued_at->format('M d, Y') }}</td>
                        <td data-label="Due">{{ $eligible->due_date->format('M d, Y') }}</td>
                        <td data-label="Status"><span class="pym-pill {{ $eligible->status->badgeClass() }}">{{ $eligible->status->label() }}</span></td>
                        <td>
                            <button type="button" class="pym-pill-btn js-select-citation" data-id="{{ $eligible->id }}">
                                @if (($citation->id ?? null) === $eligible->id) Selected @else Select @endif
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="pym-empty">No citations are currently eligible for clamping. Citations become eligible here once they have a clampable, unpaid violation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <footer class="pym-table-footer">
        <span>{{ $eligibleCitations->count() }} eligible citation{{ $eligibleCitations->count() === 1 ? '' : 's' }} found</span>
    </footer>

    {{-- Review panel: full citation details + record form (appears on select) --}}
    <div class="card stat-card mt-4" id="reviewPanel" @if (! $citation) style="display:none;" @endif>
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-file-earmark-text me-1"></i>Selected Citation — Review</strong>
            <span class="pym-pill" id="reviewStatus">—</span>
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

            <form method="POST" action="{{ route('clamping.store') }}">@csrf
                <input type="hidden" name="citation_id" id="recordCitationId" value="{{ $citation->id ?? '' }}">
                <input type="hidden" name="vehicle_plate" id="recordPlate" value="{{ $citation->vehicle_plate ?? '' }}">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Clamp location">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info mb-0 py-2">
                            <small><i class="bi bi-camera me-1"></i>Evidence photos are taken from the selected citation's own records.</small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger mt-3"><i class="bi bi-lock me-1"></i>Record Clamp</button>
                <a href="{{ route('clamping.index') }}" class="btn btn-link mt-3">Cancel</a>
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
    ]])->all();
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('eligibleForm');
    if (!form) return;

    // Filter panel toggle — same behaviour as the clamping index
    const filterBtn = document.getElementById('eligibleFilterBtn');
    const panel = document.getElementById('eligibleFilterPanel');

    const closePanel = () => {
        panel.classList.remove('open');
        filterBtn.setAttribute('aria-expanded', 'false');
    };
    filterBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = panel.classList.toggle('open');
        filterBtn.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.pym-filter-wrap')) closePanel();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closePanel();
    });

    // Search debounce
    const searchInput = document.getElementById('eligibleSearchInput');
    let debounce;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => form.submit(), 300);
    });

    const reviewData = @json($reviewData);

    const reviewPanel = document.getElementById('reviewPanel');
    const slots = ['Number', 'Plate', 'Violation', 'Penalty', 'Issued', 'Due', 'Vehicle', 'Driver', 'Officer', 'Location'];

    function renderReview(data) {
        slots.forEach(function (name) {
            const el = document.getElementById('review' + name);
            if (el) el.textContent = data[name.toLowerCase()] ?? '—';
        });
        const statusEl = document.getElementById('reviewStatus');
        statusEl.textContent = data.statusLabel;
        statusEl.className = 'pym-pill ' + data.statusClass;

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
