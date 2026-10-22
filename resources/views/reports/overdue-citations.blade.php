@extends('layouts.app')

@section('title', 'Overdue Citations')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <a href="{{ auth()->user()->isCashier() ? route('dashboard') : route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <span class="text-muted small"><i class="bi bi-clock-history me-1"></i>Unpaid citations past their due date</span>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-auto">
        <input type="date" name="date_from" class="form-control" value="{{ optional($from)->format('Y-m-d') }}" title="Due date from">
    </div>
    <div class="col-auto">
        <input type="date" name="date_to" class="form-control" value="{{ optional($to)->format('Y-m-d') }}" title="Due date to">
    </div>
    <div class="col-auto">
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Citation #, plate, driver">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
    </div>
    @if (request()->hasAny(['date_from', 'date_to', 'search']))
        <div class="col-auto">
            <a href="{{ route('reports.overdue-citations') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    @endif
</form>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Overdue Citations</div>
                <div class="h3 mb-0 text-danger">{{ number_format($citations->count()) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Total Outstanding Amount</div>
                <div class="h3 mb-0 text-danger">₱{{ number_format($totalAmount, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card stat-card mb-4">
    <div class="card-header bg-white"><strong>By Overdue Age</strong></div>
    <div class="card-body">
        @if ($byBucket->isEmpty())
            <p class="text-muted mb-0">No overdue citations in this range.</p>
        @else
            <div class="table-responsive">
                <table class="table mb-0">
                <thead><tr><th>Bucket</th><th class="text-end">Count</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @foreach ($byBucket as $bucket => $data)
                        <tr>
                            <td>{{ $bucket }}</td>
                            <td class="text-end">{{ number_format($data['count']) }}</td>
                            <td class="text-end fw-semibold">₱{{ number_format($data['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="card stat-card">
    <div class="card-header bg-white"><strong>Overdue Citation Details</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Citation #</th>
                    <th>Violation</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Issued</th>
                    <th>Due</th>
                    <th class="text-end">Days Overdue</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($citations as $citation)
                    <tr>
                        <td><a href="{{ route('citations.show', $citation) }}">{{ $citation->citation_number }}</a></td>
                        <td>{{ $citation->violationLabel() }}</td>
                        <td>{{ $citation->vehicle_plate }}</td>
                        <td>{{ $citation->driver_name }}</td>
                        <td>{{ optional($citation->issued_at)->format('M j, Y') }}</td>
                        <td>{{ optional($citation->due_date)->format('M j, Y') }}</td>
                        <td class="text-end fw-semibold text-danger">
                            {{ $citation->due_date ? (int) $citation->due_date->diffInDays(today()) : '—' }}
                        </td>
                        <td class="text-end fw-semibold">₱{{ number_format($citation->penalty_amount, 2) }}</td>
                        <td><span class="badge {{ $citation->status->badgeClass() }}">{{ $citation->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No overdue citations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
