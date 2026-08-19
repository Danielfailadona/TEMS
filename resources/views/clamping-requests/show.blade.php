@extends('layouts.app')

@section('title', 'Clamping Request — '.$request->vehicle_plate)

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@latest/dist/maplibre-gl.css">
<style>
    #request-map { width:100%; height:400px; border-radius:0.75rem; position:relative; }
    .map-detail-overlay {
        position:absolute; bottom:12px; left:12px; z-index:10;
        background:rgba(15,23,42,0.92); backdrop-filter:blur(12px);
        border:1px solid rgba(255,255,255,0.12); border-radius:0.75rem;
        padding:0.85rem 1rem; color:#e2e8f0; font-family:system-ui,sans-serif;
        max-width:300px; pointer-events:auto; box-shadow:0 8px 32px rgba(0,0,0,0.35);
        transition:opacity 0.2s, transform 0.2s;
    }
    .map-detail-overlay.is-hidden { opacity:0; transform:translateY(8px); pointer-events:none; }
    .map-detail-overlay .mdo-title { font-weight:700; font-size:0.9rem; margin-bottom:0.4rem; }
    .map-detail-overlay .mdo-row { display:flex; justify-content:space-between; padding:0.15rem 0; font-size:0.75rem; }
    .map-detail-overlay .mdo-row .mdo-lbl { color:rgba(148,163,184,0.9); }
    .map-detail-overlay .mdo-row .mdo-val { font-weight:600; text-align:right; }
    .map-detail-overlay .mdo-badge { display:inline-block; font-size:0.62rem; font-weight:700; border-radius:999px; padding:0.1rem 0.45rem; }
    .map-detail-overlay .mdo-badge.active { background:rgba(251,191,36,0.18); color:#fbbf24; }
    .map-detail-overlay .mdo-close { position:absolute; top:6px; right:8px; background:none; border:none; color:rgba(203,213,225,0.6); cursor:pointer; font-size:0.85rem; padding:2px 4px; }
    .map-detail-overlay .mdo-close:hover { color:#fff; }
    .info-chip {
        display:inline-flex; align-items:center; gap:0.35rem;
        font-size:0.75rem; padding:0.25rem 0.65rem;
        background:rgba(255,255,255,0.15); border-radius:999px;
        border:1px solid rgba(255,255,255,0.25);
    }
    .info-chip-solid {
        display:inline-flex; align-items:center; gap:0.35rem;
        font-size:0.75rem; padding:0.25rem 0.65rem;
        border-radius:999px;
    }
    .evidence-thumb {
        cursor:pointer; transition:transform 0.2s ease, box-shadow 0.2s ease;
        border-radius:0.6rem; overflow:hidden;
        width:100%; height:260px; object-fit:cover;
    }
    .evidence-thumb:hover { transform:scale(1.02); box-shadow:0 8px 25px rgba(0,0,0,0.15); }
    .section-group { margin-bottom:0.5rem; }
    .section-group:last-child { margin-bottom:0; }
    .section-group-label {
        font-size:0.65rem; font-weight:700; text-transform:uppercase;
        letter-spacing:0.05em; color:#94a3b8; margin-bottom:0.25rem;
    }
    .note-entry {
        padding:0.75rem; border-radius:0.6rem; background:#f8fafc;
        border-left:3px solid #2563eb; margin-bottom:0.5rem;
    }
    .note-entry:last-child { margin-bottom:0; }

    /* Merged Status + Evidence card */
    .merged-sidebar .status-icon-wrap {
        width:48px; height:48px; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-size:1.3rem; flex-shrink:0;
    }
    .merged-sidebar .info-row {
        display:flex; align-items:center; justify-content:space-between;
        padding:0.5rem 0; border-bottom:1px solid #f1f5f9;
    }
    .merged-sidebar .info-row:last-child { border-bottom:none; }

    /* Horizontal stepper */
    .stepper {
        display:flex; align-items:stretch; gap:0;
        position:relative;
    }
    .stepper-step {
        flex:1; display:flex; flex-direction:column; align-items:center;
        position:relative; text-align:center;
    }
    .stepper-step:not(:last-child)::after {
        content:''; position:absolute; top:20px; left:calc(50% + 22px);
        right:calc(-50% + 22px); height:3px; background:#e2e8f0; z-index:0;
        border-radius:2px;
    }
    .stepper-step.completed:not(:last-child)::after { background:#10b981; }
    .stepper-step.active:not(:last-child)::after { background:linear-gradient(90deg, #10b981 0%, #2563eb 100%); }
    .stepper-circle {
        width:40px; height:40px; border-radius:50%; display:flex;
        align-items:center; justify-content:center; font-size:0.9rem;
        position:relative; z-index:1; flex-shrink:0;
        transition:all 0.3s ease; border:3px solid #e2e8f0; background:#fff;
    }
    .stepper-step.completed .stepper-circle {
        background:#10b981; border-color:#10b981; color:#fff;
    }
    .stepper-step.active .stepper-circle {
        background:#2563eb; border-color:#2563eb; color:#fff;
        box-shadow:0 0 0 5px rgba(37,99,235,0.15);
    }
    .stepper-step.rejected .stepper-circle {
        background:#ef4444; border-color:#ef4444; color:#fff;
    }
    .stepper-label { margin-top:0.5rem; }
    .stepper-label .step-name { font-size:0.8rem; font-weight:600; }
    .stepper-label .step-date { font-size:0.65rem; color:#94a3b8; }

    /* Sticky action bar */
    .sticky-action-bar {
        position:sticky; bottom:0; z-index:1040;
        background:rgba(255,255,255,0.92);
        backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px);
        border-top:1px solid #e5e7eb;
        padding:1rem 0; margin-top:2rem;
    }

    /* Fix status badge visibility inside dark hero header */
    .detail-hero .status-badge { background:rgba(255,255,255,0.2); color:#fff; border-left-color:#fff; }
    .detail-hero .status-badge i { color:#fff; }
    .detail-hero .status-badge-pending { background:rgba(245,158,11,0.5); }
    .detail-hero .status-badge-approved,
    .detail-hero .status-badge-resolved { background:rgba(5,150,105,0.5); }
    .detail-hero .status-badge-rejected { background:rgba(220,38,38,0.5); }
</style>
@endpush

@section('content')
{{-- Header --}}
<div class="detail-hero mb-4" style="padding:1.25rem 1.5rem;">
    <div class="detail-hero-content" style="grid-template-columns:1fr auto;">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="info-chip"><i class="bi bi-hash"></i>#{{ $request->id }}</span>
                <span class="info-chip"><i class="bi bi-person"></i>{{ $request->requester_name }}</span>
                <span class="info-chip"><i class="bi bi-calendar"></i>{{ $request->created_at->format('M d, Y') }}</span>
                @if ($request->assignedTo)
                    <span class="info-chip"><i class="bi bi-person-check"></i>{{ $request->assignedTo->name }}</span>
                @endif
                <span class="info-chip"><i class="bi bi-flag"></i>{{ $request->created_at->diffForHumans() }}</span>
            </div>
            <h1 class="mb-1" style="font-size:1.5rem;">{{ $request->vehicle_plate }}</h1>
            <p class="mb-0" style="opacity:0.85;font-size:0.85rem;">
                <i class="bi bi-geo-alt me-1"></i>{{ $request->location_address }}
            </p>
        </div>
        <div class="text-end d-flex flex-column align-items-end gap-2">
            <span class="status-badge status-badge-{{ $request->status }}" style="font-size:0.85rem;">
                <i class="bi bi-{{ $request->status === 'pending' ? 'hourglass-split' : ($request->status === 'approved' ? 'check-circle' : ($request->status === 'rejected' ? 'x-circle' : 'check-all')) }} me-1"></i>
                {{ $request->getStatusLabel() }}
            </span>
            <div class="d-flex gap-2">
                @can('update', $request)
                    @if ($request->status === 'approved')
                        @if ($request->assigned_to)
                            <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
                                <i class="bi bi-arrow-repeat me-1"></i>Reassign
                            </button>
                        @endif
                        <form action="{{ route('clamping-requests.resolve', $request) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-info btn-sm">
                                <i class="bi bi-check-all me-1"></i>Mark Resolved
                            </button>
                        </form>
                    @endif
                @endcan
                <a href="{{ route('clamping-requests.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Top: 2-column split --}}
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="detail-section h-100">
            <div class="detail-section-title">
                <i class="bi bi-person-badge me-2"></i>Request Information
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="section-group">
                        <div class="section-group-label"><i class="bi bi-person me-1"></i>Requester</div>
                        <div class="fw-semibold">{{ $request->requester_name ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="section-group">
                        <div class="section-group-label"><i class="bi bi-telephone me-1"></i>Contact</div>
                        <div class="fw-semibold">{{ $request->requester_phone ?? '—' }}</div>
                        @if ($request->requester_email)
                            <div class="small text-muted">{{ $request->requester_email }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="section-group">
                        <div class="section-group-label"><i class="bi bi-truck me-1"></i>Vehicle</div>
                        <div class="fw-bold">{{ $request->vehicle_plate }}</div>
                        @if ($request->vehicle_description)
                            <div class="small text-muted">{{ $request->vehicle_description }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="section-group">
                        <div class="section-group-label"><i class="bi bi-calendar-check me-1"></i>Date Requested</div>
                        <div class="fw-semibold">{{ $request->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="section-group">
                        <div class="section-group-label"><i class="bi bi-geo-alt me-1"></i>Location Address</div>
                        <div>{{ $request->location_address }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card stat-card merged-sidebar h-100 overflow-hidden"
             style="border-left:4px solid var(--bs-{{ $request->getStatusBadgeClass() }});">
            <div class="card-body d-flex flex-column gap-2">
                {{-- Status row --}}
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon-wrap"
                         style="background:var(--bs-{{ $request->getStatusBadgeClass() }}-subtle);color:var(--bs-{{ $request->getStatusBadgeClass() }});">
                        <i class="bi bi-{{ $request->status === 'pending' ? 'hourglass-split' : ($request->status === 'approved' ? 'check-circle' : ($request->status === 'rejected' ? 'x-circle' : 'check-all')) }}"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6">{{ $request->getStatusLabel() }}</div>
                        <small class="text-muted">
                            @if ($request->status === 'pending')
                                <i class="bi bi-clock me-1"></i>Waiting for admin review
                            @elseif ($request->status === 'approved')
                                <i class="bi bi-check-circle me-1"></i>Approved {{ $request->processed_at?->diffForHumans() ?? '' }}
                            @elseif ($request->status === 'rejected')
                                <i class="bi bi-x-circle me-1"></i>Rejected {{ $request->processed_at?->diffForHumans() ?? '' }}
                            @elseif ($request->status === 'resolved')
                                <i class="bi bi-check-all me-1"></i>Resolved
                            @endif
                        </small>
                    </div>
                </div>

                {{-- Dates row --}}
                <div class="row g-2 text-center small pt-1">
                    <div class="col-4">
                        <div class="fw-bold">{{ $request->created_at->format('M d') }}</div>
                        <div class="text-muted" style="font-size:0.65rem;">Submitted</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold">{{ $request->processed_at?->format('M d') ?? '—' }}</div>
                        <div class="text-muted" style="font-size:0.65rem;">Processed</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold">{{ $request->status === 'resolved' ? $request->updated_at->format('M d') : '—' }}</div>
                        <div class="text-muted" style="font-size:0.65rem;">Completed</div>
                    </div>
                </div>

                {{-- Rejection reason --}}
                @if ($request->rejection_reason)
                    <div class="p-2 rounded" style="background:#fef2f2;border-left:3px solid #ef4444;">
                        <div class="small fw-semibold text-danger"><i class="bi bi-exclamation-circle me-1"></i>Rejection Reason</div>
                        <div class="small text-muted">{{ $request->rejection_reason }}</div>
                    </div>
                @endif

                {{-- Evidence photo --}}
                @php $hasEvidence = $request->evidence_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($request->evidence_photo); @endphp
                @if ($hasEvidence)
                    <div>
                        <div class="section-group-label mb-1"><i class="bi bi-camera me-1"></i>Evidence Photo</div>
                        <a href="{{ Storage::url($request->evidence_photo) }}" target="_blank" class="d-block">
                            <img src="{{ Storage::url($request->evidence_photo) }}" alt="Evidence" class="evidence-thumb">
                        </a>
                        <div class="text-center mt-1">
                            <small class="text-muted"><i class="bi bi-arrows-angle-expand me-1"></i>Click to view full size</small>
                        </div>
                    </div>
                @endif

                {{-- People rows --}}
                @if ($request->assignedTo)
                    <div class="info-row">
                        <span class="text-muted small"><i class="bi bi-person me-1"></i>Assigned To</span>
                        <span class="fw-semibold small">{{ $request->assignedTo->name }}</span>
                    </div>
                @endif
                @if ($request->processedBy)
                    <div class="info-row">
                        <span class="text-muted small"><i class="bi bi-clipboard-check me-1"></i>Processed By</span>
                        <span class="fw-semibold small">{{ $request->processedBy->name }}</span>
                    </div>
                @endif
                @if ($request->clampingRecord)
                    <div class="info-row">
                        <span class="text-muted small"><i class="bi bi-truck me-1"></i>Clamping Record</span>
                        <a href="{{ route('clamping.show', $request->clampingRecord) }}" class="fw-semibold small text-decoration-none">
                            {{ $request->clampingRecord->notice_number }}
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Full-width: Progress --}}
<div class="detail-section mb-4">
    <div class="detail-section-title">
        <i class="bi bi-diagram-3 me-2"></i>Progress
    </div>
    <div class="stepper">
        {{-- Step 1: Submitted --}}
        <div class="stepper-step completed">
            <div class="stepper-circle">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="stepper-label">
                <div class="step-name">Submitted</div>
                <div class="step-date">{{ $request->created_at->format('M d') }}</div>
            </div>
        </div>

        {{-- Step 2: Under Review / Rejected --}}
        @php
            $isRejected = $request->status === 'rejected';
            $isPastReview = in_array($request->status, ['approved', 'rejected', 'resolved']);
        @endphp
        <div class="stepper-step {{ $isRejected ? 'rejected' : ($isPastReview ? 'completed' : 'active') }}">
            <div class="stepper-circle">
                @if ($isRejected)
                    <i class="bi bi-x-lg"></i>
                @elseif ($isPastReview)
                    <i class="bi bi-check-lg"></i>
                @else
                    <i class="bi bi-hourglass-split"></i>
                @endif
            </div>
            <div class="stepper-label">
                <div class="step-name">{{ $isRejected ? 'Rejected' : 'Under Review' }}</div>
                <div class="step-date">
                    @if ($request->processed_at)
                        {{ $request->processed_at->format('M d') }}
                    @else
                        Pending
                    @endif
                </div>
            </div>
        </div>

        {{-- Step 3: Resolved / Awaiting --}}
        <div class="stepper-step {{ $request->status === 'resolved' ? 'completed' : ($request->status === 'approved' ? 'active' : '') }}">
            <div class="stepper-circle">
                @if ($request->status === 'resolved')
                    <i class="bi bi-check-lg"></i>
                @elseif ($request->status === 'approved')
                    <i class="bi bi-check-circle"></i>
                @else
                    <i class="bi bi-circle"></i>
                @endif
            </div>
            <div class="stepper-label">
                <div class="step-name">
                    @if ($request->status === 'resolved') Resolved
                    @elseif ($request->assignedTo) Awaiting Resolution
                    @else Awaiting Assignment
                    @endif
                </div>
                <div class="step-date">
                    @if ($request->assignedTo)
                        {{ $request->assignedTo->name }}
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Full-width: Map --}}
<div class="card stat-card mb-4 border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white border-bottom px-4 py-3">
        <strong><i class="bi bi-geo-alt me-2"></i>Location Map</strong>
    </div>
    <div class="card-body p-0">
        @if ($request->latitude && $request->longitude)
            <div id="request-map"></div>
            <div class="map-detail-overlay is-hidden" id="request-map-detail"></div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-map" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                <span class="small">No location coordinates available for this request.</span>
            </div>
        @endif
    </div>
</div>

{{-- Full-width: Notes --}}
@if ($request->additional_notes)
    <div class="detail-section mb-4">
        <div class="detail-section-title">
            <i class="bi bi-chat-text me-2"></i>Notes
        </div>
        <div class="note-entry">
            <div class="d-flex gap-2">
                <i class="bi bi-pencil-square mt-1" style="color:#2563eb;"></i>
                <div>
                    <div class="fw-semibold small">Requester</div>
                    <div class="text-muted small">{{ $request->additional_notes }}</div>
                    <div class="text-muted" style="font-size:0.65rem;margin-top:0.15rem;">
                        {{ $request->created_at->format('M d, Y h:i A') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Sticky Action Bar --}}
@can('update', $request)
    @if ($request->status === 'pending')
        <div class="sticky-action-bar">
            <div class="d-flex justify-content-center gap-4 align-items-center">
                <button type="button" class="btn btn-lg btn-outline-secondary px-5" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle me-2"></i>Reject
                </button>
                <div class="vr opacity-25" style="height:2.5rem;"></div>
                <button type="button" class="btn btn-lg btn-success px-5 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                    <i class="bi bi-check-circle me-2"></i>Approve & Assign
                </button>
            </div>
        </div>
    @endif
@endcan

{{-- Approve Modal --}}
@can('update', $request)
    @if ($request->status === 'pending')
        <div class="modal fade" id="approveModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('clamping-requests.approve', $request) }}">
                    @csrf
                    <div class="modal-content" style="border-radius:1rem;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold"><i class="bi bi-check-circle text-success me-2"></i>Approve & Assign</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">Assign this request to a clamping officer or enforcer:</p>
                            <select name="assigned_to" class="form-select" required>
                                <option value="">— Select Personnel —</option>
                                @foreach ($enforcers as $enforcer)
                                    <option value="{{ $enforcer->id }}">{{ $enforcer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Approve & Assign</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="rejectModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('clamping-requests.reject', $request) }}">
                    @csrf
                    <div class="modal-content" style="border-radius:1rem;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold"><i class="bi bi-x-circle text-danger me-2"></i>Reject Request</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label fw-semibold small">Reason for Rejection</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Provide a reason..."></textarea>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle me-1"></i>Reject Request</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($request->status === 'approved')
        <div class="modal fade" id="assignModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('clamping-requests.assign', $request) }}">
                    @csrf
                    <div class="modal-content" style="border-radius:1rem;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold"><i class="bi bi-arrow-repeat text-primary me-2"></i>Reassign Personnel</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <select name="assigned_to" class="form-select" required>
                                <option value="">— Select Personnel —</option>
                                @foreach ($enforcers as $enforcer)
                                    <option value="{{ $enforcer->id }}" @selected($request->assigned_to === $enforcer->id)>{{ $enforcer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check me-1"></i>Assign</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcan
@endsection

@push('scripts')
<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
@if ($request->latitude && $request->longitude)
<script>
    (function () {
        const lat = {{ $request->latitude }};
        const lng = {{ $request->longitude }};
        const address = {!! json_encode($request->location_address) !!};
        const status = {!! json_encode($request->status) !!};
        const vehiclePlate = {!! json_encode($request->vehicle_plate ?? 'N/A') !!};
        const detailEl = document.getElementById('request-map-detail');

        const map = new maplibregl.Map({
            container: 'request-map',
            style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
            center: [lng, lat],
            zoom: 15,
        });

        map.on('load', function () {
            map.addSource('pin', {
                type: 'geojson',
                data: {
                    type: 'FeatureCollection',
                    features: [{
                        type: 'Feature',
                        geometry: { type: 'Point', coordinates: [lng, lat] },
                        properties: { description: address },
                    }],
                },
            });

            map.addLayer({
                id: 'pin-layer',
                type: 'circle',
                source: 'pin',
                paint: {
                    'circle-radius': 10,
                    'circle-color': '#dc3545',
                    'circle-stroke-width': 3,
                    'circle-stroke-color': '#fff',
                },
            });

            if (detailEl) {
                detailEl.innerHTML = `
                    <button class="mdo-close" onclick="document.getElementById('request-map-detail').classList.add('is-hidden')">&times;</button>
                    <div class="mdo-title">Clamping Request Location</div>
                    <div class="mdo-row"><span class="mdo-lbl">Status</span><span class="mdo-badge active">${status}</span></div>
                    <div class="mdo-row"><span class="mdo-lbl">Vehicle</span><span class="mdo-val">${vehiclePlate}</span></div>
                    <div class="mdo-row"><span class="mdo-lbl">Address</span><span class="mdo-val">${address || '—'}</span></div>
                    <div class="mdo-row"><span class="mdo-lbl">Coordinates</span><span class="mdo-val">${lat.toFixed(7)}, ${lng.toFixed(7)}</span></div>
                `;
                detailEl.classList.remove('is-hidden');
            }
        });
    })();
</script>
@endif
@endpush
