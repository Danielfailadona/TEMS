@extends('layouts.app')

@section('title', 'Clamping Requests')

@section('content')
<p class="text-muted mb-4 small">Manage citizen-reported clamping requests</p>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card stat-card text-center py-3">
            <div class="fs-4 fw-bold" style="color:var(--itevcms-primary);">{{ $stats['total'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Total Requests</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card text-center py-3 border-warning">
            <div class="fs-4 fw-bold text-warning">{{ $stats['pending'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Pending</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card text-center py-3 border-success">
            <div class="fs-4 fw-bold text-success">{{ $stats['approved'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Approved</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card text-center py-3" style="border-color:var(--bs-info);">
            <div class="fs-4 fw-bold text-info">{{ $stats['resolved'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Resolved</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card text-center py-3 border-danger">
            <div class="fs-4 fw-bold text-danger">{{ $stats['rejected'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Rejected</div>
        </div>
    </div>
</div>

<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('clamping-requests.index') }}">
            <div class="row g-2 align-items-center">
                <div class="col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search by plate, name, or location..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-lg-auto">
                    <div class="d-flex gap-2">
                        <a href="{{ route('clamping-requests.index') }}" class="btn btn-outline-secondary btn-sm @if(!request('status') && !request('search')) active @endif">All</a>
                        <a href="{{ route('clamping-requests.index', array_merge(['status' => 'pending'], request()->only('search'))) }}" class="btn btn-outline-warning btn-sm @if(request('status') === 'pending') active @endif">Pending</a>
                        <a href="{{ route('clamping-requests.index', array_merge(['status' => 'approved'], request()->only('search'))) }}" class="btn btn-outline-success btn-sm @if(request('status') === 'approved') active @endif">Approved</a>
                        <a href="{{ route('clamping-requests.index', array_merge(['status' => 'rejected'], request()->only('search'))) }}" class="btn btn-outline-danger btn-sm @if(request('status') === 'rejected') active @endif">Rejected</a>
                        <a href="{{ route('clamping-requests.index', array_merge(['status' => 'resolved'], request()->only('search'))) }}" class="btn btn-outline-info btn-sm @if(request('status') === 'resolved') active @endif">Resolved</a>
                    </div>
                </div>
                <div class="col-lg-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Vehicle Plate</th>
                    <th>Location</th>
                    <th>Assigned To</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $r)
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $r->requester_name ?? '—' }}</div>
                            @if ($r->requester_phone)
                                <small class="text-muted">{{ $r->requester_phone }}</small>
                            @endif
                        </td>
                        <td><span class="fw-semibold">{{ $r->vehicle_plate }}</span></td>
                        <td class="small text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $r->location_address }}">
                            {{ $r->location_address }}
                        </td>
                        <td class="small">{{ $r->assignedTo?->name ?? '—' }}</td>
                        <td class="small text-muted">{{ $r->created_at->format('M d, Y') }}</td>
                        <td><span class="badge bg-{{ $r->getStatusBadgeClass() }} rounded-pill">{{ $r->getStatusLabel() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('clamping-requests.show', $r) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                        No clamping requests found.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($requests->hasPages())
        <div class="card-footer bg-white d-flex justify-content-center">
            {{ $requests->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
