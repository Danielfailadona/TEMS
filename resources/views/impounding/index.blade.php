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

    .imp-dash .imp-primary-btn {
        height: 38px; border: 0; background: var(--imp-blue); color: #fff; border-radius: 16px;
        padding: 0 16px; font-weight: 650; white-space: nowrap; display: inline-flex; align-items: center;
        gap: 6px; text-decoration: none; font-size: 14px; align-self: center; margin-left: auto;
    }
    .imp-dash .imp-primary-btn:hover { background: #075edb; color: #fff; }

    .imp-dash .imp-plate { font-weight: 700; color: #17202d; }
    .imp-dash .imp-muted-dash { color: #77808f; }

    .imp-dash .imp-actions { display: flex; align-items: center; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .imp-dash .std-table th:nth-child(1), .imp-dash .std-table td:nth-child(1) { width: 12%; }
    .imp-dash .std-table th:nth-child(2), .imp-dash .std-table td:nth-child(2) { width: 15%; }
    .imp-dash .std-table th:nth-child(3), .imp-dash .std-table td:nth-child(3) { width: 12%; }
    .imp-dash .std-table th:nth-child(4), .imp-dash .std-table td:nth-child(4) { width: 13%; }
    .imp-dash .std-table th:nth-child(5), .imp-dash .std-table td:nth-child(5) { width: 14%; }
    .imp-dash .std-table th:nth-child(6), .imp-dash .std-table td:nth-child(6) { width: 10%; }
    .imp-dash .std-table th:nth-child(7), .imp-dash .std-table td:nth-child(7) { width: 24%; text-align: center; }
    .imp-dash .imp-payment-btn {
        height: 28px; padding: 0 9px; border: 1px solid #1976ff; border-radius: 16px;
        background: #fff; color: #0f6df0; font-size: 13px; font-weight: 700; white-space: nowrap;
    }
    .imp-dash .imp-eye-btn:hover, .imp-dash .imp-payment-btn:hover { background: var(--imp-blue-soft); }
    .imp-dash .imp-queue-btn {
        height: 28px; padding: 0 14px; border-radius: 16px; border: none;
        background: #d97706; color: #fff; font-size: 13px; font-weight: 700; white-space: nowrap;
    }
    .imp-dash .imp-release-btn {
        height: 28px; padding: 0 14px; border-radius: 16px; border: none;
        background: #15803d; color: #fff; font-size: 13px; font-weight: 700; white-space: nowrap;
    }
    .imp-dash .imp-queue-btn:hover { background: #b45309; }
    .imp-dash .imp-release-btn:hover { background: #14532d; }

    @media (max-width: 900px) {
        .imp-dash .imp-toolbar { gap: 8px; }
        .imp-dash .imp-search { flex: 1; }
    }
    @media (max-width: 600px) {
        .imp-dash .imp-toolbar { align-items: stretch; flex-direction: column; }
        .imp-dash .imp-filter-btn { width: 100%; }
        .imp-dash .imp-search { width: 100%; }
        .imp-dash .imp-filter-menu { width: 100%; }
        .imp-dash .imp-primary-btn { margin-left: 0; }
    }
</style>
@endpush

@section('content')
@php
$activeStatus = (string) request('status');
if (! \App\Enums\ImpoundingStatus::tryFrom($activeStatus)) {
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
                <button type="button" class="imp-filter-option{{ $activeStatus === '' ? ' selected' : '' }}" data-status="" role="menuitem">Active</button>
                @foreach (\App\Enums\ImpoundingStatus::cases() as $statusOption)
                    <button type="button" class="imp-filter-option{{ $statusOption->value === $activeStatus ? ' selected' : '' }}" data-status="{{ $statusOption->value }}" role="menuitem">{{ $statusOption->label() }}</button>
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

        @can('create', App\Models\ImpoundingRecord::class)
            <a href="{{ route('impounding.create') }}" class="imp-primary-btn">
                <i class="bi bi-truck-front"></i>Record Impound
            </a>
        @endcan
    </form>

    <section class="std-table-wrap" aria-label="Impounding records">
        <table class="std-table table-cards" aria-label="Impounding records">
            <thead>
                <tr>
                    <th scope="col">Plate #</th>
                    <th scope="col">Notice #</th>
                    <th scope="col">Violation</th>
                    <th scope="col">Officer</th>
                    <th scope="col">Impounded At</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td class="imp-plate" data-label="Plate #">{{ $record->vehicle_plate }}</td>
                        <td data-label="Notice #">{{ $record->notice_number }}</td>
                        <td class="{{ $record->citation?->violationType ? '' : 'imp-muted-dash' }}" data-label="Violation">
                            @if ($record->citation)
                                <a href="{{ route('citations.show', $record->citation) }}" title="View citation {{ $record->citation->citation_number }}">{{ $record->citation->violationType?->code ?? '—' }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="Officer">{{ $record->officer->name }}</td>
                        <td data-label="Impounded At">{{ $record->impounded_at?->format('M d, Y') ?? '—' }}</td>
                        <td data-label="Status"><span class="std-pill {{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span></td>
                        <td>
                            <div class="imp-actions">
                                <a href="{{ route('impounding.show', $record) }}" class="std-view-btn" title="View details" aria-label="View {{ $record->vehicle_plate }}">
                                    <svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.3"/></svg>
                                </a>
                                @can('markPaid', $record)
                                    <button type="button" class="imp-payment-btn" data-bs-toggle="modal" data-bs-target="#payModal-{{ $record->id }}">Record Payment</button>
                                @endcan
                                @can('markWaitingRelease', $record)
                                    <form action="{{ route('impounding.mark-waiting-release', $record) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="imp-queue-btn">Queue Release</button>
                                    </form>
                                @endcan
                                @can('processRelease', $record)
                                    <button type="button" class="imp-release-btn" data-bs-toggle="modal" data-bs-target="#releaseModal-{{ $record->id }}">Process Release</button>
                                @endcan
                                <x-delete-button :record="$record" route-name="impounding.destroy" :label="$record->notice_number" class="btn btn-sm btn-outline-danger" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="std-empty">No impounded vehicles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <footer class="std-table-footer">
        <span>Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ $records->total() }} results</span>
        @if ($records->hasPages())
            {{ $records->withQueryString()->links() }}
        @endif
    </footer>
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
                            <p class="mb-3">Amount due: <strong>₱{{ number_format($record->getTotalFees(), 2) }}</strong></p>
                            @if ($record->citation)
                                <p class="mb-3 small text-muted">Citation fine of ₱{{ number_format($record->citation->penalty_amount, 2) }} ({{ $record->citation->citation_number }}) is collected separately and is required before release.</p>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    @foreach (App\Enums\PaymentMethod::cases() as $method)
                                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                    @endforeach
                                </select>
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

    @can('processRelease', $record)
        <div class="modal fade" id="releaseModal-{{ $record->id }}" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('impounding.process-release', $record) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Process Release &mdash; {{ $record->vehicle_plate }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Confirm release of vehicle <strong>{{ $record->vehicle_plate }}</strong>?</p>
                            <div class="mb-3">
                                <label class="form-label">Release Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Release Vehicle</button>
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