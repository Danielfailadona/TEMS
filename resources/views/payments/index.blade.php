@extends('layouts.app')

@section('title', 'Payments')

@push('styles')
<style>
    .pay-dash {
        --pay-blue: #1977f3;
        --pay-ink: #172033;
        --pay-muted: #718095;
        --pay-line: #e5e9ed;
    }
    .pay-dash .pay-toolbar { display: flex; align-items: center; gap: 16px; width: 100%; flex-wrap: wrap; }
    .pay-dash .pay-search-box { display: flex; height: 37px; flex: 1; min-width: 160px; background: #fff; border: 1px solid #cdd7e1; overflow: hidden; }
    .pay-dash .pay-search-submit {
        width: 33px; flex: 0 0 33px; border: 0; border-right: 1px solid var(--pay-blue);
        background: #fff; color: var(--pay-blue); display: flex; align-items: center; justify-content: center; cursor: pointer;
    }
    .pay-dash .pay-search-submit svg { width: 15px; height: 15px; }
    .pay-dash .pay-search-box input {
        flex: 1; min-width: 0; border: 0; outline: 0; padding: 0 21px; color: #667286; font-size: 16px; background: #fff;
    }
    .pay-dash .pay-search-box input::placeholder { color: #727d90; }
    .pay-dash .pay-search-box input:focus { box-shadow: inset 0 0 0 1px #7aa9f8; }
    .pay-dash .pay-record-btn {
        height: 38px; border: 0; border-radius: 9px; background: var(--pay-blue); color: #fff;
        font-size: 13px; font-weight: 800; padding: 0 16px; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
    }
    .pay-dash .pay-record-btn:hover { background: #0e69df; color: #fff; }
    .pay-dash .pay-table-card { margin-top: 22px; background: #fff; border: 1px solid var(--pay-line); border-radius: 12px; overflow: hidden; }
    .pay-dash .pay-entry-count {
        height: 45px; display: flex; align-items: center; padding: 0 13px;
        color: #6e7c90; font-size: 11px; border-bottom: 1px solid #e2e7ec;
    }
    .pay-dash .pay-entry-count strong { color: var(--pay-ink); margin: 0 3px; }
    .pay-dash .pay-table-wrap { overflow-x: auto; }
    .pay-dash table { width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 790px; }
    .pay-dash thead th {
        height: 51px; padding: 0 15px; text-align: left; border-bottom: 1px solid #e5e9ed;
        color: var(--pay-ink); font-size: 14px; font-weight: 700; white-space: nowrap;
    }
    .pay-dash tbody tr { height: 78px; border-bottom: 1px solid #e6ebef; }
    .pay-dash tbody td {
        padding: 0 15px; color: var(--pay-ink); font-size: 14px; white-space: nowrap;
    }
    .pay-dash tbody tr:last-child { border-bottom: 0; }
    .pay-dash th:nth-child(1), .pay-dash td:nth-child(1) { width: 24%; }
    .pay-dash th:nth-child(2), .pay-dash td:nth-child(2) { width: 20%; }
    .pay-dash th:nth-child(3), .pay-dash td:nth-child(3) { width: 10%; }
    .pay-dash th:nth-child(4), .pay-dash td:nth-child(4) { width: 12%; }
    .pay-dash th:nth-child(5), .pay-dash td:nth-child(5) { width: 10%; }
    .pay-dash th:nth-child(6), .pay-dash td:nth-child(6) { width: 12%; }
    .pay-dash th:nth-child(7), .pay-dash td:nth-child(7) { width: 12%; text-align: center; }
    .pay-dash .pay-action-cell { display: flex; align-items: center; gap: 5px; justify-content: center; }
    .pay-dash .pay-action {
        width: 48px; height: 34px; border: 1px solid #8bb0df; border-radius: 18px;
        background: #fff; color: var(--pay-blue); display: inline-flex; align-items: center;
        justify-content: center; text-decoration: none;
    }
    .pay-dash .pay-action svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.35; }
    .pay-dash .pay-action:hover { background: #f1f7ff; color: var(--pay-blue); }
    .pay-dash .pay-empty { padding: 35px; text-align: center; color: #7b8798; }
    .pay-dash .pay-footer {
        min-height: 55px; display: flex; align-items: center; justify-content: flex-end; gap: 16px;
        color: var(--pay-muted); font-size: 11px; padding: 10px 12px; flex-wrap: wrap;
    }
    .pay-dash .pay-results { margin-right: auto; }
    .pay-dash .pagination { display: flex; align-items: center; gap: 0; margin-bottom: 0; }
    .pay-dash .pagination .page-link {
        width: 38px; height: 30px; border: 1px solid #dce3eb; margin-left: -1px; background: #fff;
        color: #176ff2; display: grid; place-items: center; font-size: 13px; padding: 0;
        border-radius: 0; text-decoration: none;
    }
    .pay-dash .pagination .page-item:first-child .page-link { border-radius: 5px 0 0 5px; color: var(--pay-muted); }
    .pay-dash .pagination .page-item:last-child .page-link { border-radius: 0 5px 5px 0; color: var(--pay-muted); }
    .pay-dash .pagination .page-item.active .page-link { background: #176ff2; border-color: #176ff2; color: #fff; }
    .pay-dash .pagination .page-item.disabled .page-link { background: #fff; color: #c3cdd9; }
    .pay-dash .pagination .page-item .page-link:hover { background: #f2f7ff; color: #176ff2; }
    .pay-dash .pagination .page-item.active .page-link:hover { background: #176ff2; }
    @media (max-width: 600px) {
        .pay-dash .pay-toolbar { flex-wrap: wrap; gap: 10px; }
        .pay-dash .pay-search-box { order: 2; flex-basis: 100%; }
        .pay-dash .pay-record-btn { margin-left: auto; }
    }
</style>
@endpush

@section('content')
<div class="pay-dash">
    <form method="GET" class="pay-toolbar" id="payForm" aria-label="Payment search">
        <label class="pay-search-box" aria-label="Search payments">
            <button type="submit" class="pay-search-submit" aria-label="Search payments">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            </button>
            <input type="search" name="search" id="paySearchInput" placeholder="Receipt #, citation #, plate, or driver..." value="{{ request('search') }}" autocomplete="off">
        </label>

        @can('create', App\Models\Payment::class)
            <a href="{{ route('payments.create') }}" class="pay-record-btn">＋&nbsp; Record Payment</a>
        @endcan
    </form>

    <section class="pay-table-card" aria-label="Payments table">
        <div class="pay-entry-count">
            Showing <strong>{{ $payments->firstItem() ?? 0 }}</strong> to <strong>{{ $payments->lastItem() ?? 0 }}</strong> of <strong>{{ $payments->total() }}</strong> entries
        </div>

        <div class="pay-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Receipt #</th>
                        <th scope="col">Citation #</th>
                        <th scope="col">Vehicle</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Method</th>
                        <th scope="col">Paid At</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->receipt_number }}</td>
                            <td>{{ $payment->citation->citation_number }}</td>
                            <td>{{ $payment->citation->vehicle_plate }}</td>
                            <td>₱{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->payment_method->label() }}</td>
                            <td>{{ $payment->paid_at ? $payment->paid_at->format('M d, Y') : 'Pending' }}</td>
                            <td>
                                <div class="pay-action-cell">
                                    <a href="{{ route('payments.show', $payment) }}" class="pay-action" title="View payment" aria-label="View payment {{ $payment->receipt_number }}">
                                        <svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.3"/></svg>
                                    </a>
                                    @can('update', $payment)
                                        <a href="{{ route('payments.edit', $payment) }}" class="pay-action" title="Edit payment" aria-label="Edit payment {{ $payment->receipt_number }}">
                                            <svg viewBox="0 0 24 24"><path d="M4 20l4-.8L19 8.2a2 2 0 0 0-2.8-2.8L5.2 16.4 4 20Z"/><path d="M14.5 6.5l3 3"/></svg>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="pay-empty">No payments recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="pay-footer">
            <span class="pay-results">Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} results</span>
            @if ($payments->hasPages())
                {{ $payments->links() }}
            @endif
        </footer>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('payForm');
    const searchInput = document.getElementById('paySearchInput');
    if (!form || !searchInput) return;

    let debounce;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => form.submit(), 300);
    });
});
</script>
@endpush
