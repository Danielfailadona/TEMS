@extends('layouts.app')

@section('title', 'Vehicle Clamping')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Vehicle Clamping</h1>
    @can('create', App\Models\ClampingRecord::class)
        <a href="{{ route('clamping.create') }}" class="btn btn-danger">Record Clamp</a>
    @endcan
</div>

@if (isset($pendingRequests) && $pendingRequests->isNotEmpty())
    <div class="card stat-card mb-4">
        <div class="card-header bg-white"><strong>Citizen Clamping Requests</strong></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Requester</th><th>Vehicle Plate</th><th>Location</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($pendingRequests as $request)
                        <tr>
                            <td>{{ $request->requester_name ?? '—' }}</td>
                            <td>{{ $request->vehicle_plate }}</td>
                            <td>{{ $request->location ?? '—' }}</td>
                            <td>{{ $request->created_at->format('M d, Y') }}</td>
                            <td><span class="badge bg-warning">{{ ucfirst($request->status ?? 'pending') }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if (isset($overdueCitations) && $overdueCitations->isNotEmpty())
    <div class="card stat-card mb-4">
        <div class="card-header bg-white"><strong>Eligible Vehicles</strong></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Plate #</th><th></th></tr></thead>
                <tbody>
                    @foreach ($overdueCitations as $citation)
                        <tr>
                            <td>{{ $citation->vehicle_plate }}</td>
                            <td class="text-end">
                                <a href="{{ route('clamping.create', ['vehicle_plate' => $citation->vehicle_plate]) }}" class="btn btn-sm btn-danger">Clamp</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card stat-card">
    <div class="card-header bg-white"><strong>Clamping Records</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Notice #</th>
                    <th>Vehicle</th>
                    <th>Officer</th>
                    <th>Clamped At</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>{{ $record->notice_number }}</td>
                        <td>{{ $record->vehicle_plate }}</td>
                        <td>{{ $record->officer->name }}</td>
                        <td>{{ $record->clamped_at->format('M d, Y') }}</td>
                        <td><span class="badge {{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span></td>
                        <td class="text-end"><a href="{{ route('clamping.show', $record) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No clamping records.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())<div class="card-footer bg-white">{{ $records->links() }}</div>@endif
</div>
@endsection
