@extends('layouts.app')

@section('title', 'Record Clamp')

@section('content')
<div class="card stat-card"><div class="card-body">
    <form method="POST" action="{{ route('clamping.store') }}" enctype="multipart/form-data">@csrf
        <div class="mb-3">
            <div class="card border-primary bg-primary-subtle">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="fw-semibold">Clamping Eligibility Reference</small>
                            <div class="small mt-1">
                                <strong>Core Violations Eligible for Clamping:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    <li>Posted "No Parking" or "Tow-Away" Zones: Any road, street, or Mabuhay lane designated with official no-parking signs.</li>
                                    <li>Sidewalks and Paths: Parked on sidewalks, walking paths, pedestrian crosswalks, or alleys.</li>
                                    <li>Vital Entrances & Driveways: Blocking an authorized driveway or parked within four meters of a hospital, clinic, or fire station entrance.</li>
                                    <li>Intersections & Curves: Parked within six meters of any curved lane or street intersection.</li>
                                    <li>Fire Hydrants: Stopped or parked within four meters of a fire hydrant.</li>
                                    <li>Bicycle Lanes: Straddling or completely obstructing a designated active transport lane.</li>
                                    <li>Double Parking: Parked on the roadside of any legally parked vehicle.</li>
                                    <li>Bridges: Parked at the foot or directly on top of a bridge.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle Plate</label>
                <input type="text" name="vehicle_plate" class="form-control" value="{{ old('vehicle_plate', $citation->vehicle_plate ?? '') }}" placeholder="ABC-1234" required>
                @if (isset($citation))
                    <div class="small text-muted mt-1">Citation: {{ $citation->citation_number }} — {{ $citation->violationLabel() }}</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Evidence Photo <span class="text-danger">*</span></label>
                <input type="file" name="evidence" class="form-control @error('evidence') is-invalid @enderror" accept="image/*" required>
                @error('evidence')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
            </div>
        </div>
        <button type="submit" class="btn btn-danger mt-3">Record Clamp</button>
        <a href="{{ route('clamping.index') }}" class="btn btn-link">Cancel</a>
    </form>
</div></div>
@endsection
