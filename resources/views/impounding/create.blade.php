@extends('layouts.app')

@section('title', 'Impound Vehicle')

@push('styles')
<style>
    /* Toolbar + filter styles — same as the impounding index */
    .imp-dash .imp-toolbar { display: flex; align-items: center; gap: 14px; margin-bottom: 23px; flex-wrap: wrap; }
    .imp-dash .imp-filter-wrap { position: relative; }
    .imp-dash .imp-filter-btn {
        width: 114px; height: 36px; background: #fff; border: 1px solid #d7dce3;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .02); font-size: 15px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer;
    }
    .imp-dash .imp-filter-caret {
        width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent;
        border-top: 7px solid #080808; transition: transform .15s ease;
    }
    .imp-dash .imp-filter-btn[aria-expanded="true"] .imp-filter-caret { transform: rotate(180deg); }
    .imp-dash .imp-filter-menu {
        position: absolute; left: 0; top: 41px; width: 229px; background: #fff;
        border: 1px solid #d6dadf; box-shadow: 0 8px 24px rgba(16, 24, 40, .12);
        z-index: 30; padding: 0; display: none;
    }
    .imp-dash .imp-filter-menu.open { display: block; }
    .imp-dash .imp-filter-option {
        height: 32px; display: flex; align-items: center; padding: 0 10px; font-size: 15px;
        color: #171717; background: #fff; border: 0; width: 100%; text-align: left; cursor: pointer;
    }
    .imp-dash .imp-filter-option:hover, .imp-dash .imp-filter-option.selected { background: #f3f6fa; }

    .imp-dash .imp-search {
        height: 36px; flex: 1; min-width: 160px; display: flex; background: #fff; border: 1px solid #d7dce3;
    }
    .imp-dash .imp-search-icon-box {
        width: 35px; border: 0; border-right: 1px solid #d7dce3; background: #fff;
        display: grid; place-items: center; color: #1677ff; cursor: pointer; flex: none;
    }
    .imp-dash .imp-search-icon-box svg { width: 15px; height: 15px; }
    .imp-dash .imp-search input {
        border: 0; outline: 0; padding: 0 22px; width: 100%; font-size: 16px; color: #555f70; background: #fff;
    }
    .imp-dash .imp-search input:focus { box-shadow: inset 0 0 0 1px #7aa9f8; }
    .imp-dash .imp-search input::placeholder { color: #7b8493; }

    .imp-dash tbody tr.row-selected { background: #eef5ff; box-shadow: inset 3px 0 0 #1677ff; }

    @media (max-width: 600px) {
        .imp-dash .imp-toolbar { align-items: stretch; flex-direction: column; }
        .imp-dash .imp-filter-btn { width: 100%; }
        .imp-dash .imp-search { width: 100%; }
        .imp-dash .imp-filter-menu { width: 100%; }
    }
</style>
@endpush

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
                        <td data-label="Status"><span class="std-pill {{ $eligible->status->value === 'overdue' ? 'bg-warning-soft' : 'bg-gray-soft' }}">{{ $eligible->status->label() }}</span></td>
                        <td>
                            @php
                                $selectUrl = route('impounding.create', array_filter([
                                    'citation_id' => $eligible->id,
                                    'eligible_search' => request('eligible_search'),
                                    'eligible_status' => request('eligible_status'),
                                ]));
                                $isSelected = ($citation->id ?? null) === $eligible->id;
                            @endphp
                            <a href="{{ $selectUrl }}" class="btn btn-sm {{ $isSelected ? 'btn-success' : 'btn-primary' }}">
                                @if ($isSelected) <i class="bi bi-check-lg me-1"></i>Selected @else Select @endif
                            </a>
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

    {{-- Review panel: full citation details + fees + record form (server-rendered for the selected citation) --}}
    @if ($citation)
        <div class="card stat-card mt-4" id="reviewPanel">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-file-earmark-text me-1"></i>Selected Citation — Review</strong>
                <span class="std-pill {{ $citation->status->value === 'overdue' ? 'bg-warning-soft' : 'bg-gray-soft' }}">{{ $citation->status->label() }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><span class="text-muted small d-block">Citation #</span><strong>{{ $citation->citation_number }}</strong></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Vehicle Plate</span><strong>{{ $citation->vehicle_plate }}</strong></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Violation</span><span>{{ $citation->violationLabel() }}</span></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Penalty</span><span>₱{{ number_format((float) $citation->penalty_amount, 2) }}</span></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Issued</span><span>{{ $citation->issued_at?->format('M d, Y') ?? '—' }}</span></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Due</span><span>{{ $citation->due_date?->format('M d, Y') ?? '—' }}</span></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Vehicle</span><span>{{ trim(collect([$citation->vehicle_make, $citation->vehicle_model, $citation->vehicle_type, $citation->vehicle_color])->filter()->implode(' ')) ?: '—' }}</span></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Driver</span><span>{{ $citation->driver_name ?: '—' }}</span></div>
                    <div class="col-md-4"><span class="text-muted small d-block">Issuing Officer</span><span>{{ $citation->enforcer?->name ?? '—' }}</span></div>
                    <div class="col-12"><span class="text-muted small d-block">Location</span><span>{{ $citation->location ?: '—' }}</span></div>
                </div>

                <h6 class="fw-bold mb-2"><i class="bi bi-gear me-1"></i>Fees (Based on Violation Type)</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card text-center border-0" style="background:#f8fafc;">
                            <div class="card-body py-2">
                                <div class="fs-5 fw-800 text-danger">{{ $fees['towing_fee'] ? '₱' . number_format($fees['towing_fee'], 2) : '—' }}</div>
                                <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Towing Fee</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center border-0" style="background:#f8fafc;">
                            <div class="card-body py-2">
                                <div class="fs-5 fw-800 text-warning">{{ $fees['storage_fee_per_day'] ? '₱' . number_format($fees['storage_fee_per_day'], 2) : '—' }}</div>
                                <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Storage/Day</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center border-0" style="background:#f8fafc;">
                            <div class="card-body py-2">
                                <div class="fs-5 fw-800 text-info">{{ $fees['admin_fee'] ? '₱' . number_format($fees['admin_fee'], 2) : '—' }}</div>
                                <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;">Admin Fee</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info py-2">
                    <small><i class="bi bi-info-circle me-1"></i>
                        <strong>Grace Period:</strong> {{ config('itevcms.impounding.grace_hours', 24) }} hours before storage fees begin.
                        Fees based on violation: <strong>{{ $citation->violationType?->code ?? 'default' }}</strong>.
                        Evidence photos are taken from the selected citation's own records.
                    </small>
                </div>

                <h6 class="fw-bold mb-2"><i class="bi bi-clipboard-check me-1"></i>Next Step — Record the Impound</h6>
                <form method="POST" action="{{ route('impounding.store') }}">@csrf
                    <input type="hidden" name="citation_id" value="{{ $citation->id }}">
                    <input type="hidden" name="vehicle_plate" value="{{ $citation->vehicle_plate }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="Impound location" value="{{ old('location', $citation->location) }}">
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
    @endif
</div>
@endsection

@push('scripts')
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
});
</script>
@endpush
