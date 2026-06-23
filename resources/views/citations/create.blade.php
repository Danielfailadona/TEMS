@extends('layouts.app')

@section('title', 'Issue Citation')

@section('content')
<h1 class="h3 mb-4">Issue Citation</h1>
<div class="card stat-card"><div class="card-body">
    <form method="POST" action="{{ route('citations.store') }}" enctype="multipart/form-data">@csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle Plate <span class="text-danger">*</span></label>
                <input type="text" name="vehicle_plate" class="form-control" value="{{ old('vehicle_plate') }}" placeholder="ABC-1234" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Vehicle Make</label>
                <input type="text" name="vehicle_make" class="form-control" value="{{ old('vehicle_make') }}" placeholder="e.g. Toyota">
            </div>
            <div class="col-md-6">
                <label class="form-label">Vehicle Model</label>
                <input type="text" name="vehicle_model" class="form-control" value="{{ old('vehicle_model') }}" placeholder="e.g. Vios">
            </div>
            <div class="col-md-6">
                <label class="form-label">Vehicle Type</label>
                <input type="text" name="vehicle_type" class="form-control" value="{{ old('vehicle_type') }}" placeholder="e.g. Sedan, SUV">
            </div>
            <div class="col-md-6">
                <label class="form-label">Vehicle Color</label>
                <input type="text" name="vehicle_color" class="form-control" value="{{ old('vehicle_color') }}" placeholder="e.g. Red">
            </div>
            <div class="col-md-6">
                <label class="form-label">Driver Name</label>
                <input type="text" name="driver_name" class="form-control" value="{{ old('driver_name') }}" placeholder="Driver's full name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Driver License</label>
                <input type="text" name="driver_license" class="form-control" value="{{ old('driver_license') }}" placeholder="License number">
            </div>
            <div class="col-md-6">
                <label class="form-label">Violation Type</label>
                <select name="violation_type_id" class="form-select" required>
                    <option value="">Select violation...</option>
                    @foreach ($violationTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('violation_type_id') == $type->id)>
                            {{ $type->name }} — ₱{{ number_format($type->penalty_amount, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Violation location">
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Evidence Photos</label>
                <input type="file" name="evidence[]" class="form-control" accept="image/*" multiple>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-danger" id="submitBtn">Issue Citation</button>
            <a href="{{ route('citations.index') }}" class="btn btn-link">Cancel</a>
        </div>
    </form>
</div></div>

@push('scripts')
<script>
    document.getElementById('submitBtn')?.addEventListener('click', function () {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';
        this.closest('form').submit();
    });
</script>
@endpush
@endsection
