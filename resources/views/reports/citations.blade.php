@extends('layouts.app')

@section('title', 'Citation Report')

@section('content')
<div class="mb-4">
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-auto"><input type="date" name="date_from" class="form-control" value="{{ $from->format('Y-m-d') }}"></div>
    <div class="col-auto"><input type="date" name="date_to" class="form-control" value="{{ $to->format('Y-m-d') }}"></div>
    <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button></div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Total Citations</div><div class="h3 mb-0">{{ $totalCount }}</div></div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Status Distribution</div>@foreach ($byStatus as $status => $count) <span class="badge bg-{{ $status === 'Paid' ? 'success' : ($status === 'Overdue' ? 'warning' : ($status === 'Clamped' ? 'danger' : 'primary')) }} me-1">{{ $status }}: {{ $count }}</span> @endforeach</div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>By Violation Type</strong></div>
            <div class="card-body">
                <table class="table mb-0">
                    <thead><tr><th>Type</th><th class="text-end">Count</th></tr></thead>
                    <tbody>
                        @foreach ($byViolationType as $type => $count)
                            <tr><td>{{ $type }}</td><td class="text-end">{{ $count }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>By Enforcer</strong></div>
            <div class="card-body">
                <table class="table mb-0">
                    <thead><tr><th>Enforcer</th><th class="text-end">Citations</th></tr></thead>
                    <tbody>
                        @foreach ($byEnforcer as $name => $count)
                            <tr><td>{{ $name }}</td><td class="text-end">{{ $count }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
