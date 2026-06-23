@extends('layouts.app')

@section('title', 'Payments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Payments</h1>
    @can('create', App\Models\Payment::class)
        <a href="{{ route('payments.create') }}" class="btn btn-primary">Record Payment</a>
    @endcan
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Citation #</th>
                    <th>Vehicle</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Paid At</th>
                    <th></th>
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
                        <td>{{ $payment->paid_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">View</a>
                            @can('update', $payment)
                                <a href="{{ route('payments.edit', $payment) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No payments recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($payments->hasPages())<div class="card-footer bg-white">{{ $payments->links() }}</div>@endif
</div>
@endsection
