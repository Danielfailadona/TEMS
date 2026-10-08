@php
    $trackReference = request('reference', '');
@endphp

<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('citizen.clamping.landing') }}" class="row g-3">
            <input type="hidden" name="tab" value="track">
            <div class="col-12 col-md-9">
                <label class="form-label visually-hidden">Reference Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-tag"></i></span>
                    <input type="text" name="reference" class="form-control form-control-lg" placeholder="Enter reference number (e.g., CLP-20250115-A1B2)" value="{{ $trackReference }}" required>
                </div>
            </div>
            <div class="col-12 col-md-3 d-grid">
                <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                    <i class="bi bi-search me-1"></i> Track
                </button>
            </div>
        </form>
    </div>
</div>

@if ($trackReference)
    @if ($requestInfo)
        <div class="card stat-card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Request Details</strong>
                <span class="badge {{ $requestInfo->status === 'pending' ? 'bg-warning text-dark' : ($requestInfo->status === 'approved' ? 'bg-success' : ($requestInfo->status === 'rejected' ? 'bg-danger' : 'bg-info')) }} fs-6">{{ ucfirst($requestInfo->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small d-block">Reference Number</div>
                        <div class="fw-bold fs-5">{{ $requestInfo->reference_number }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small d-block">Submitted</div>
                        <div class="fw-bold">{{ $requestInfo->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small d-block">Vehicle Plate</div>
                        <div class="fw-bold">{{ $requestInfo->vehicle_plate }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small d-block">Vehicle Description</div>
                        <div>{{ $requestInfo->vehicle_description ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small d-block">Location</div>
                        <div>{{ $requestInfo->location_address }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small d-block">Coordinates</div>
                        <div class="font-monospace small">{{ $requestInfo->latitude }}, {{ $requestInfo->longitude }}</div>
                    </div>
                    @if ($requestInfo->status === 'approved' || $requestInfo->status === 'rejected' || $requestInfo->status === 'resolved')
                        <div class="col-md-6">
                            <div class="text-muted small d-block">Processed By</div>
                            <div>{{ $requestInfo->processedBy?->name ?? 'System' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small d-block">Processed At</div>
                            <div>{{ $requestInfo->processed_at?->format('M d, Y h:i A') ?? '—' }}</div>
                        </div>
                    @endif
                    @if ($requestInfo->status === 'rejected')
                        <div class="col-12">
                            <div class="text-muted small d-block">Rejection Reason</div>
                            <div class="alert alert-warning">{{ $requestInfo->rejection_reason }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card stat-card mb-4">
            <div class="card-header bg-white"><strong>Status Timeline</strong></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle p-1" style="width:10px;height:10px;"></span>
                        <div>
                            <small class="fw-semibold d-block">Submitted</small>
                            <small class="text-muted">{{ $requestInfo->created_at->format('M d, Y h:i A') }}</small>
                        </div>
                    </div>
                    @if ($requestInfo->status !== 'pending')
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge bg-info rounded-circle p-1" style="width:10px;height:10px;"></span>
                            <div>
                                <small class="fw-semibold d-block">Under Review</small>
                                <small class="text-muted">{{ $requestInfo->processed_at?->format('M d, Y h:i A') ?? '—' }}</small>
                            </div>
                        </div>
                    @endif
                    @if (in_array($requestInfo->status, ['approved', 'rejected', 'resolved']))
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge {{ $requestInfo->status === 'approved' ? 'bg-success' : 'bg-danger' }} rounded-circle p-1" style="width:10px;height:10px;"></span>
                            <div>
                                <small class="fw-semibold d-block">{{ $requestInfo->status === 'approved' ? 'Approved' : 'Rejected' }}</small>
                                <small class="text-muted">{{ $requestInfo->processed_at?->format('M d, Y h:i A') ?? '—' }}</small>
                            </div>
                        </div>
                    @endif
                    @if ($requestInfo->status === 'resolved')
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge bg-success rounded-circle p-1" style="width:10px;height:10px;"></span>
                            <div>
                                <small class="fw-semibold d-block">Clamping Resolved</small>
                                <small class="text-muted">{{ $requestInfo->updated_at?->format('M d, Y h:i A') ?? '—' }}</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($requestInfo->evidence_photo)
            <div class="card stat-card mb-4">
                <div class="card-header bg-white"><strong>Submitted Evidence</strong></div>
                <div class="card-body text-center">
                    @if (\App\Services\SupabaseStorage::has($requestInfo->evidence_photo))
                        <img src="{{ \App\Services\SupabaseStorage::publicUrl($requestInfo->evidence_photo) }}" alt="Evidence" class="rounded border" style="max-height:300px;">
                        <small class="text-muted d-block mt-2">Submitted photo evidence</small>
                    @else
                        <div class="text-muted small fst-italic">Evidence file unavailable</div>
                    @endif
                </div>
            </div>
        @endif
    @else
        <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle me-1"></i>
            No clamping request found with reference number: <strong>{{ $trackReference }}</strong>
        </div>
    @endif
@endif