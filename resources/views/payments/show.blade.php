@extends('layouts.app')

@section('title', 'Receipt '.$payment->receipt_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h1 class="h3 mb-0">Payment Receipt</h1>
    <div class="d-flex gap-2">
        @can('update', $payment)
            <a href="{{ route('payments.edit', $payment) }}" class="btn btn-outline-secondary">Edit</a>
        @endcan
        <button onclick="window.print()" class="btn btn-outline-primary">Print Receipt</button>
    </div>
</div>

<div class="alert alert-info py-2 no-print" role="alert" style="max-width:640px; margin: 0 auto 1rem;">
    <small>
        <i class="bi bi-info-circle me-1"></i>
        <strong>Tip:</strong> In the print dialog, click ⋯ &rarr; <em>More settings</em> &rarr; toggle off
        <em>Headers and footers</em> to remove the URL and page number.
    </small>
</div>

<div class="card stat-card mx-auto print-page-receipt" style="max-width:640px">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="64" class="mb-2">
            <h4 class="mb-0 fw-bold">{{ config('itevcms.app_name') }}</h4>
            <div class="text-muted small mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">
                Transportation Enforcement Management System
            </div>
            <small class="text-muted">Official Payment Receipt</small>
        </div>
        <hr>
        <div class="row mb-2"><div class="col-5 text-muted">Receipt #</div><div class="col-7 fw-semibold">{{ $payment->receipt_number }}</div></div>
        <div class="row mb-2"><div class="col-5 text-muted">Citation #</div><div class="col-7">{{ $payment->citation->citation_number }}</div></div>
        <div class="row mb-2"><div class="col-5 text-muted">Vehicle</div><div class="col-7">{{ $payment->citation->vehicle_plate }}</div></div>
        <div class="row mb-2"><div class="col-5 text-muted">Violation</div><div class="col-7">{{ $payment->citation->violationType->name }}</div></div>
        <div class="row mb-2"><div class="col-5 text-muted">Amount Paid</div><div class="col-7 fw-semibold">₱{{ number_format($payment->amount, 2) }}</div></div>
        <div class="row mb-2"><div class="col-5 text-muted">Payment Method</div><div class="col-7">{{ $payment->isOnlinePayment() ? ucfirst($payment->online_payment_method ?? 'Online Payment') : $payment->payment_method->label() }}</div></div>
        @if ($payment->reference_number)
            <div class="row mb-2"><div class="col-5 text-muted">Reference</div><div class="col-7">{{ $payment->reference_number }}</div></div>
        @endif
        @if ($payment->paymongo_checkout_id)
            <div class="row mb-2"><div class="col-5 text-muted">Checkout ID</div><div class="col-7"><code class="small">{{ $payment->paymongo_checkout_id }}</code></div></div>
        @endif
        <div class="row mb-2"><div class="col-5 text-muted">Cashier</div><div class="col-7">{{ $payment->cashier->name ?? 'Online Payment' }}</div></div>
        <div class="row mb-2"><div class="col-5 text-muted">Date Paid</div><div class="col-7">{{ $payment->paid_at?->format('M d, Y h:i A') ?? 'Pending' }}</div></div>
        <hr>
        <p class="text-muted small mb-0 text-center">This receipt serves as proof of payment for the cited violation.</p>
    </div>
</div>

@if ($payment->citation->evidence->isNotEmpty())
    <div class="card stat-card mx-auto mt-3 no-print" style="max-width:640px">
        <div class="card-body">
            <h6 class="card-title text-muted text-uppercase small fw-semibold mb-3">
                <i class="bi bi-images me-1"></i>Evidence ({{ $payment->citation->evidence->count() }})
            </h6>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($payment->citation->evidence as $evidence)
                    <img src="{{ asset('storage/'.$evidence->file_path) }}"
                         alt="Evidence"
                         class="rounded border"
                         style="width: 96px; height: 96px; object-fit: cover; cursor: zoom-in;"
                         onclick="openModal('{{ asset('storage/'.$evidence->file_path) }}')">
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Evidence Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="modalImage" src="" alt="Evidence" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(src) {
        document.getElementById('modalImage').src = src;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
</script>
@endsection
