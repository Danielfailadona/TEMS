@extends('layouts.app')

@section('title', 'Clamping Requests')

@section('content')
<p class="text-muted mb-4 small">Manage citizen-reported clamping requests</p>

<div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
    <div class="col">
        <div class="card stat-card text-center py-3 h-100">
            <div class="fs-4 fw-bold" style="color:var(--itevcms-primary);">{{ $stats['total'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Total Requests</div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card text-center py-3 h-100 border-warning">
            <div class="fs-4 fw-bold text-warning">{{ $stats['pending'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Pending</div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card text-center py-3 h-100 border-success">
            <div class="fs-4 fw-bold text-success">{{ $stats['approved'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Approved</div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card text-center py-3 h-100" style="border-color:var(--bs-info);">
            <div class="fs-4 fw-bold text-info">{{ $stats['resolved'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Resolved</div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card text-center py-3 h-100 border-danger">
            <div class="fs-4 fw-bold text-danger">{{ $stats['rejected'] }}</div>
            <div class="small text-muted text-uppercase" style="letter-spacing:0.03em;font-size:0.7rem;">Rejected</div>
        </div>
    </div>
</div>

<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('clamping-requests.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label small mb-1">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search by plate, name, or location..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-12 col-lg-auto">
                    <label class="form-label small mb-1">&nbsp;</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('clamping-requests.index') }}" class="btn btn-outline-secondary btn-sm @if(!request('status') && !request('search')) active @endif">All</a>
                        <a href="{{ route('clamping-requests.index', array_merge(['status' => 'pending'], request()->only('search'))) }}" class="btn btn-outline-warning btn-sm @if(request('status') === 'pending') active @endif">Pending</a>
                        <a href="{{ route('clamping-requests.index', array_merge(['status' => 'approved'], request()->only('search'))) }}" class="btn btn-outline-success btn-sm @if(request('status') === 'approved') active @endif">Approved</a>
                        <a href="{{ route('clamping-requests.index', array_merge(['status' => 'rejected'], request()->only('search'))) }}" class="btn btn-outline-danger btn-sm @if(request('status') === 'rejected') active @endif">Rejected</a>
                        <a href="{{ route('clamping-requests.index', array_merge(['status' => 'resolved'], request()->only('search'))) }}" class="btn btn-outline-info btn-sm @if(request('status') === 'resolved') active @endif">Resolved</a>
                    </div>
                </div>
                <div class="col-12 col-lg-auto">
                    <label class="form-label small mb-1">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                        @if (request('status') || request('search'))
                            <a href="{{ route('clamping-requests.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
    @forelse ($requests as $r)
        <div class="col">
            <div class="card stat-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                    <div class="min-width-0">
                        <strong class="small text-truncate d-block">{{ $r->vehicle_plate }}</strong>
                        <small class="text-muted text-truncate d-block">{{ $r->requester_name ?? '—' }}</small>
                    </div>
                    <span class="badge bg-{{ $r->getStatusBadgeClass() }} rounded-pill">{{ $r->getStatusLabel() }}</span>
                </div>
                <div class="card-body small">
                    <div class="row g-2">
                        <div class="col-12"><strong class="text-muted d-block">Location</strong>
                            <span style="word-break:break-word;">{{ $r->location_address }}</span>
                        </div>
                        <div class="col-6"><strong class="text-muted d-block">Assigned To</strong>{{ $r->assignedTo?->name ?? '—' }}</div>
                        <div class="col-6"><strong class="text-muted d-block">Requested</strong>{{ $r->created_at->format('M d, Y') }}</div>
                        @if ($r->requester_phone)
                            <div class="col-12"><strong class="text-muted d-block">Contact</strong>{{ $r->requester_phone }}</div>
                        @endif
                        @if ($r->clampingRecord)
                            <div class="col-12">
                                <strong class="text-muted d-block">Clamp Notice</strong>
                                <a href="{{ route('clamping.show', $r->clampingRecord) }}">{{ $r->clampingRecord->notice_number }}</a>
                            </div>
                        @endif
                    </div>

                    @if ($r->latitude && $r->longitude)
                        <button class="btn btn-sm btn-outline-secondary mt-3 w-100" type="button"
                                data-bs-toggle="collapse" data-bs-target="#map-{{ $r->id }}" aria-expanded="false" aria-controls="map-{{ $r->id }}">
                            <i class="bi bi-map me-1"></i>Show Map
                        </button>
                        <div class="collapse mt-2" id="map-{{ $r->id }}">
                            <iframe
                                src="https://www.openstreetmap.org/export/embed.html?bbox={{ $r->longitude - 0.002 }}%2C{{ $r->latitude - 0.001 }}%2C{{ $r->longitude + 0.002 }}%2C{{ $r->latitude + 0.001 }}&amp;layer=mapnik&amp;marker={{ $r->latitude }}%2C{{ $r->longitude }}"
                                style="width:100%;height:180px;border:1px solid var(--itevcms-border);border-radius:0.5rem;"
                                loading="lazy" title="Request location"></iframe>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white d-flex justify-content-end">
                    <a href="{{ route('clamping-requests.show', $r) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card stat-card text-center py-5">
                <div class="card-body">
                    <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted mb-2">No clamping requests found.</h5>
                    <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

@if ($requests->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $requests->withQueryString()->links() }}
    </div>
@endif
@endsection