@extends('layouts.app')

@section('title', 'Revenue Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Revenue Report</h1>
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-auto">
        <input type="date" name="date_from" class="form-control" value="{{ $from->format('Y-m-d') }}">
    </div>
    <div class="col-auto">
        <input type="date" name="date_to" class="form-control" value="{{ $to->format('Y-m-d') }}">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Total Revenue</div>
                <div class="h3 mb-0 text-success">₱{{ number_format($totalRevenue, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Total Payments</div>
                <div class="h3 mb-0">{{ $totalCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Average per Payment</div>
                <div class="h3 mb-0">₱{{ number_format($totalCount > 0 ? $totalRevenue / $totalCount : 0, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Revenue by Payment Method</strong></div>
            <div class="card-body">
                <table class="table mb-0">
                    <thead><tr><th>Method</th><th class="text-end">Count</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @foreach ($byMethod as $method => $data)
                            <tr>
                                <td>{{ ucfirst($method) }}</td>
                                <td class="text-end">{{ $data['count'] }}</td>
                                <td class="text-end fw-semibold">₱{{ number_format($data['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Daily Collections</strong></div>
            <div class="card-body">
                <canvas id="revenueChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const labels = @json($dailyTotals->keys()->values());
    const data = @json($dailyTotals->values());
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: { labels, datasets: [{ label: 'Revenue (₱)', data, borderColor: '#16a34a', fill: true, backgroundColor: 'rgba(22,163,74,0.1)' }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
});
</script>
@endpush
