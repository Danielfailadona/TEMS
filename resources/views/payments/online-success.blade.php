@extends('layouts.app')

@section('title', 'Payment Successful')

@section('content')
<div class="text-center py-5 animate-on-load">
    <div class="mb-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
    </div>
    <h2 class="mb-2">Payment Successful!</h2>
    <p class="text-muted mb-4">Your payment has been processed successfully.</p>

    @if ($payment->paid_at)
        <div class="card stat-card mx-auto mb-4" style="max-width: 480px;">
            <div class="card-body text-start">
                <div class="row mb-2">
                    <div class="col-5 text-muted">Receipt #</div>
                    <div class="col-7 fw-semibold">{{ $payment->receipt_number }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Citation #</div>
                    <div class="col-7">{{ $payment->citation->citation_number }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Amount Paid</div>
                    <div class="col-7 fw-semibold">₱{{ number_format($payment->amount, 2) }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Payment Method</div>
                    <div class="col-7">{{ $payment->online_payment_method ? ucfirst($payment->online_payment_method) : 'Online Payment' }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('payments.show', $payment) }}" class="btn btn-primary">
                <i class="bi bi-receipt me-2"></i>View Receipt
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
        </div>

        @if ($payment->citation->evidence->isNotEmpty())
            <div class="card stat-card mx-auto mt-4 text-start" style="max-width: 480px;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-semibold mb-3">
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
    @else
        <div class="alert alert-warning mx-auto" style="max-width: 480px;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Your payment is being processed. Please wait a moment and check your citation status.
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
    @endif
</div>

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
