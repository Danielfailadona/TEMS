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

<div class="card stat-card mx-auto" style="max-width:640px">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <h4 class="mb-0">{{ config('itevcms.app_name') }}</h4>
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
        <div class="row mb-2"><div class="col-5 text-muted">Cashier</div><div class="col-7">{{ $payment->cashier->name }}</div></div>
        <div class="row mb-2"><div class="col-5 text-muted">Date Paid</div><div class="col-7">{{ $payment->paid_at?->format('M d, Y h:i A') ?? 'Pending' }}</div></div>
        <hr>
        <p class="text-muted small mb-0 text-center">This receipt serves as proof of payment for the cited violation.</p>
    </div>
</div>
@endsection
