@extends('layouts.app')

@section('title', 'Record Clamp')

@section('content')
<div class="card stat-card"><div class="card-body">
    <form method="POST" action="{{ route('clamping.store') }}" enctype="multipart/form-data">@csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle Plate</label>
                <input type="text" name="vehicle_plate" class="form-control" value="{{ old('vehicle_plate', $citation->vehicle_plate ?? '') }}" placeholder="ABC-1234" required>
                @if (isset($citation))
                    <div class="small text-muted mt-1">Citation: {{ $citation->citation_number }} — {{ $citation->violationType->name }}</div>
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
                <label class="form-label">Evidence Photo</label>
                <input type="file" name="evidence" class="form-control" accept="image/*">
            </div>
        </div>
        <button type="submit" class="btn btn-danger mt-3">Record Clamp</button>
        <a href="{{ route('clamping.index') }}" class="btn btn-link">Cancel</a>
    </form>
</div></div>
@endsection
