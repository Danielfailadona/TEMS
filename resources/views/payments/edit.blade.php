@extends('layouts.app')

@section('title', 'Edit Payment')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Payment — {{ $payment->receipt_number }}</h1>
    <a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-secondary">Back to Receipt</a>
</div>

<div class="card stat-card mx-auto" style="max-width:600px">
    <div class="card-body">
        <form method="POST" action="{{ route('payments.update', $payment) }}">@csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">Receipt Number</label>
                <input class="form-control" value="{{ $payment->receipt_number }}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Citation</label>
                <input class="form-control" value="{{ $payment->citation->citation_number }}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount</label>
                <input class="form-control" value="₱{{ number_format($payment->amount, 2) }}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-select" required>
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method->value }}" @selected($payment->payment_method === $method)>{{ $method->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Reference Number</label>
                <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', $payment->reference_number) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $payment->notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Payment</button>
        </form>
    </div>
</div>
@endsection
