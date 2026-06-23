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
    @else
        <div class="alert alert-warning mx-auto" style="max-width: 480px;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Your payment is being processed. Please wait a moment and check your citation status.
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
    @endif
</div>
@endsection
