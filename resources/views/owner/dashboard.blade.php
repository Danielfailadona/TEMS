@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
<div class="mb-4">
    <p class="text-muted mb-0">Your vehicles and enforcement status</p>
</div>

<div class="row g-3 mb-4">
    @foreach ([
        ['label' => 'My Vehicles', 'value' => $stats['my_vehicles']],
        ['label' => 'My Citations', 'value' => $stats['my_citations']],
        ['label' => 'Unpaid', 'value' => $stats['unpaid']],
        ['label' => 'Active Clamps', 'value' => $stats['active_clamps']],
    ] as $stat)
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">{{ $stat['label'] }}</div>
                    <div class="h3 mb-0">{{ $stat['value'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card stat-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Recent Citations</strong>
        <a href="{{ route('owner.citations') }}" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Citation #</th>
                    <th>Violation</th>
                    <th>Vehicle</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentCitations as $citation)
                    <tr>
                        <td><a href="{{ route('citations.show', $citation) }}">{{ $citation->citation_number }}</a></td>
                        <td>{{ $citation->violationType->name }}</td>
                        <td>{{ $citation->vehicle_plate }}</td>
                        <td>₱{{ number_format($citation->penalty_amount, 2) }}</td>
                        <td><span class="badge {{ $citation->status->badgeClass() }}">{{ $citation->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No citations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
