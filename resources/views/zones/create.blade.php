@extends('layouts.app')

@section('title', 'Create Zone')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Zone</h1>
    <a href="{{ route('zones.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Zones</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('zones.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Zone Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Assigned Team</label>
                            <select name="team_id" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Center Latitude</label>
                            <input type="number" step="0.0000001" name="center_latitude" id="zone-lat" class="form-control @error('center_latitude') is-invalid @enderror" required>
                            @error('center_latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Center Longitude</label>
                            <input type="number" step="0.0000001" name="center_longitude" id="zone-lng" class="form-control @error('center_longitude') is-invalid @enderror" required>
                            @error('center_longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Radius (meters)</label>
                            <input type="number" step="1" name="radius_m" id="zone-radius" class="form-control @error('radius_m') is-invalid @enderror" value="500" required>
                            @error('radius_m')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                                <label class="form-check-label" for="is_active">Active zone</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Zone</button>
                        <a href="{{ route('zones.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <div id="zone-editor-map" class="rounded" style="height: 500px; width: 100%;"></div>
                <div class="form-text mt-2 text-center">
                    <i class="bi bi-info-circle"></i> Click on the map to place the zone center. Adjust the radius in the form.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite('resources/js/zone-picker.js')
<script>
ZONES_MAP_DATA = @json($zones);
document.addEventListener('DOMContentLoaded', function () {
    const fn = window.__zonePicker?.initZoneEditor;
    if (typeof fn === 'function') {
        fn('zone-editor-map', {
            latInputId: 'zone-lat',
            lngInputId: 'zone-lng',
            radiusInputId: 'zone-radius',
            zones: ZONES_MAP_DATA,
        });
    }
});
</script>
@endpush
