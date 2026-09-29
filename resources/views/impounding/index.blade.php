@extends('layouts.app')

@section('title', 'Impounding')

@push('styles')
<style>
    .imp-dash {
        --imp-blue: #1677ff;
        --imp-blue-soft: #eaf3ff;
        --imp-line: #e6e9ee;
    }
    .imp-dash .imp-toolbar { display: flex; align-items: center; gap: 14px; margin-bottom: 23px; flex-wrap: wrap; }

    .imp-dash .imp-filter-wrap { position: relative; }
    .imp-dash .imp-filter-btn {
        width: 114px; height: 36px; background: #fff; border: 1px solid #d7dce3;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .02); font-size: 15px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; gap: 10px;
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
        height: 25px; display: flex; align-items: center; padding: 0 10px; font-size: 15px;
        color: #171717; background: #fff; border: 0; width: 100%; text-align: left;
    }
    .imp-dash .imp-filter-option:hover, .imp-dash .imp-filter-option.selected { background: #f3f6fa; }

    .imp-dash .imp-search {
        height: 36px; flex: 0 1 696px; min-width: 160px; display: flex; background: #fff; border: 1px solid #d7dce3;
    }
    .imp-dash .imp-search-icon-box {
        width: 35px; border: 0; border-right: 1px solid #d7dce3; background: #fff;
        display: grid; place-items: center; color: var(--imp-blue); cursor: pointer; flex: none;
    }
    .imp-dash .imp-search-icon-box svg { width: 15px; height: 15px; }
    .imp-dash .imp-search input {
        border: 0; outline: 0; padding: 0 22px; width: 100%; font-size: 16px; color: #555f70; background: #fff;
    }
    .imp-dash .imp-search input:focus { box-shadow: inset 0 0 0 1px #7aa9f8; }
    .imp-dash .imp-search input::placeholder { color: #7b8493; }

    .imp-dash .imp-table-card {
        background: #fff; border-radius: 10px; overflow: hidden;
        box-shadow: 0 0 0 1px rgba(16, 24, 40, .01); padding: 28px 27px 0;
    }
    .imp-dash .imp-table-wrap { overflow-x: auto; }
    .imp-dash table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .imp-dash thead th {
        height: 51px; text-align: left; border-bottom: 1px solid #edf0f3;
        font-size: 14px; font-weight: 700; color: #151922; vertical-align: middle; padding: 0;
    }
    .imp-dash tbody tr { height: 78px; border-bottom: 1px solid #edf0f3; }
    .imp-dash tbody tr:last-child { border-bottom: 0; }
    .imp-dash tbody td { font-size: 14px; color: #283140; padding: 0; vertical-align: middle; }
    .imp-dash th:nth-child(1), .imp-dash td:nth-child(1) { width: 13.1%; }
    .imp-dash th:nth-child(2), .imp-dash td:nth-child(2) { width: 16.5%; }
    .imp-dash th:nth-child(3), .imp-dash td:nth-child(3) { width: 15.3%; }
    .imp-dash th:nth-child(4), .imp-dash td:nth-child(4) { width: 14.0%; }
    .imp-dash th:nth-child(5), .imp-dash td:nth-child(5) { width: 13.2%; }
    .imp-dash th:nth-child(6), .imp-dash td:nth-child(6) { width: 14.0%; }
    .imp-dash th:nth-child(7), .imp-dash td:nth-child(7) { width: 14.0%; text-align: center; }
    .imp-dash .imp-plate { font-weight: 700; color: #17202d; }
    .imp-dash .imp-muted-dash { color: #77808f; }
    .imp-dash .imp-status {
        display: inline-flex; align-items: center; justify-content: center; height: 23px; padding: 0 15px;
        border-radius: 20px; font-size: 13px; font-weight: 700; white-space: nowrap;
    }
    .imp-dash .imp-empty { padding: 35px; text-align: center; color: #7b8798; }

    .imp-dash .imp-actions { display: flex; align-items: center; gap: 10px; justify-content: center; }
    .imp-dash .imp-eye-btn {
        width: 48px; height: 34px; border: 1px solid #1976ff; border-radius: 18px;
        background: #fff; color: #1976ff; display: grid; place-items: center; text-decoration: none;
    }
    .imp-dash .imp-eye-btn svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.6; }
    .imp-dash .imp-payment-btn {
        height: 28px; padding: 0 9px; border: 1px solid #1976ff; border-radius: 16px;
        background: #fff; color: #0f6df0; font-size: 13px; font-weight: 700; white-space: nowrap;
    }
    .imp-dash .imp-eye-btn:hover, .imp-dash .imp-payment-btn:hover { background: var(--imp-blue-soft); }

    .imp-dash .imp-footer { display: flex; justify-content: flex-end; align-items: center; margin-top: 34px; gap: 14px; flex-wrap: wrap; }
    .imp-dash .imp-result-count { font-size: 11px; color: #6f7d91; }
    .imp-dash .imp-pagination .pagination { display: flex; align-items: center; height: 30px; gap: 0; margin-bottom: 0; }
    .imp-dash .imp-pagination .page-link {
        width: 38px; height: 30px; padding: 0; border: 1px solid #dce3eb; margin-left: -1px; background: #fff;
        color: #176ff2; font-size: 13px; display: grid; place-items: center; text-decoration: none;
        border-radius: 0;
    }
    .imp-dash .imp-pagination .page-item:first-child .page-link { border-radius: 5px 0 0 5px; color: #60728a; }
    .imp-dash .imp-pagination .page-item:last-child .page-link { border-radius: 0 5px 5px 0; color: #60728a; }
    .imp-dash .imp-pagination .page-item.active .page-link { background: #176ff2; color: #fff; border-color: #176ff2; }
    .imp-dash .imp-pagination .page-item.disabled .page-link { background: #fff; color: #c3cdd9; cursor: default; pointer-events: none; }
    .imp-dash .imp-pagination .small.text-muted { display: none !important; }
    .imp-dash .imp-pagination .d-none.flex-sm-fill { display: flex !important; }
    .imp-dash .imp-pagination .d-sm-none { display: none !important; }

    @media (max-width: 900px) {
        .imp-dash .imp-toolbar { gap: 8px; }
        .imp-dash .imp-search { flex: 1; }
        .imp-dash .imp-table-card { padding-left: 16px; padding-right: 16px; }
        .imp-dash table { min-width: 930px; }
        .imp-dash .imp-footer { justify-content: space-between; }
    }
    @media (max-width: 600px) {
        .imp-dash .imp-toolbar { align-items: stretch; flex-direction: column; }
        .imp-dash .imp-filter-btn { width: 100%; }
        .imp-dash .imp-search { width: 100%; }
        .imp-dash .imp-filter-menu { width: 100%; }
        .imp-dash .imp-footer { flex-direction: column; align-items: flex-end; }
    }
</style>
@endpush

@section('content')
@php
$options = [
    '' => 'Active',
    'awaiting_payment' => 'Awaiting Payment',
    'paid' => 'Paid',
    'waiting_release' => 'Waiting Release',
    'released' => 'Released',
];
$activeStatus = (string) request('status');
if (! array_key_exists($activeStatus, $options)) {
    $activeStatus = '';
}
@endphp

<div class="imp-dash">
    <form method="GET" class="imp-toolbar" id="impForm" aria-label="Impounding filters and search">
        <div class="imp-filter-wrap">
            <button type="button" id="impFilterBtn" class="imp-filter-btn" aria-expanded="false" aria-controls="impFilterMenu">
                Filters <span class="imp-filter-caret"></span>
            </button>
            <div id="impFilterMenu" class="imp-filter-menu" role="menu" aria-label="Impounding filters">
                @foreach ($options as $value => $label)
                    <button type="button" class="imp-filter-option{{ $value === $activeStatus ? ' selected' : '' }}" data-status="{{ $value }}" role="menuitem">{{ $label }}</button>
                @endforeach
            </div>
            <input type="hidden" name="status" id="impStatus" value="{{ $activeStatus }}">
        </div>

        <label class="imp-search" aria-label="Search impounding records">
            <button type="submit" class="imp-search-icon-box" aria-label="Apply search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="search" id="impSearchInput" placeholder="Search..." value="{{ request('search') }}" autocomplete="off">
        </label>
    </form>

    <div class="imp-table-card">
        <div class="imp-table-wrap">
            <table aria-label="Impounding records">
                <thead>
                    <tr>
                        <th scope="col">Plate #</th>
                        <th scope="col">Notice #</th>
                        <th scope="col">Violation</th>
                        <th scope="col">Officer</th>
                        <th scope="col">Clamped At</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td class="imp-plate">{{ $record->vehicle_plate }}</td>
                            <td>{{ $record->notice_number }}</td>
                            <td class="{{ $record->citation?->violationType ? '' : 'imp-muted-dash' }}">{{ $record->citation?->violationType?->name ?? '—' }}</td>
                            <td>{{ $record->officer->name }}</td>
                            <td>{{ $record->clamped_at->format('M d, Y') }}</td>
                            <td><span class="imp-status {{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span></td>
                            <td>
                                <div class="imp-actions">
                                    <a href="{{ route('impounding.show', $record) }}" class="imp-eye-btn" title="View details" aria-label="View {{ $record->vehicle_plate }}">
                                        <svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.3"/></svg>
                                    </a>
                                    @can('markPaid', $record)
                                        <button type="button" class="imp-payment-btn" data-bs-toggle="modal" data-bs-target="#payModal-{{ $record->id }}">Record Payment</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="imp-empty">No impounded vehicles found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="imp-footer">
            <span class="imp-result-count">Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ $records->total() }} results</span>
            @if ($records->hasPages())
                <div class="imp-pagination">{{ $records->links() }}</div>
            @endif
        </footer>
    </div>
</div>

@foreach ($records as $record)
    @can('markPaid', $record)
        <div class="modal fade" id="payModal-{{ $record->id }}" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('impounding.mark-paid', $record) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Record Payment — {{ $record->vehicle_plate }}</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">Amount due: <strong>₱{{ number_format($record->citation?->penalty_amount ?? 0, 2) }}</strong></p>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    @foreach (App\Enums\PaymentMethod::cases() as $method)
                                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Confirm Payment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('impForm');
    const filterBtn = document.getElementById('impFilterBtn');
    const filterMenu = document.getElementById('impFilterMenu');
    const statusInput = document.getElementById('impStatus');
    const searchInput = document.getElementById('impSearchInput');
    if (!form || !filterBtn || !filterMenu || !statusInput) return;

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