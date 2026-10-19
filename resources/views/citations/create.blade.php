@extends('layouts.app')

@section('title', 'Issue Citation')

@push('styles')
<style>
    .evidence-capture {
        border: 2px dashed var(--itevcms-border);
        border-radius: 0.8rem;
        padding: 1rem;
        background: #fafbfc;
    }
    .evidence-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .evidence-preview .ev-thumb {
        position: relative;
        width: 96px;
        height: 96px;
        border-radius: 0.6rem;
        overflow: hidden;
        border: 1px solid var(--itevcms-border);
        background: #fff;
    }
    .evidence-preview .ev-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .evidence-preview .ev-thumb .ev-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        border: none;
        font-size: 0.7rem;
        line-height: 1;
        display: grid;
        place-items: center;
        cursor: pointer;
    }
    .evidence-preview .ev-thumb .ev-remove:hover { background: rgba(220, 38, 38, 0.9); }
</style>
@endpush

@section('content')
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
                <select name="violation_type_id" id="violationTypeSelect" class="form-select" required>
                    <option value="">Select violation...</option>
                    @foreach ($violationTypes as $type)
                        <option value="{{ $type->id }}" data-is-impoundable="{{ $type->is_impoundable ? '1' : '0' }}" @selected(old('violation_type_id') == $type->id)>
                            {{ $type->name }} — ₱{{ number_format($type->penalty_amount, 2) }}
                        </option>
                    @endforeach
                </select>
                <div class="mt-2" id="impoundableInfoPanel" style="display:none;">
                    <div class="card border-warning bg-warning-subtle">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="fw-semibold">This violation is eligible for impounding.</small>
                                    <div class="small mt-1">
                                        <strong>Violations Eligible for Impounding:</strong>
                                        <ul class="mb-0 mt-1 ps-3">
                                            <li>Unregistered or Delinquent Vehicles: Driving with expired, invalid, or missing registration (₱10,000 fine plus impoundment until properly registered and inspected).</li>
                                            <li>Unauthorized Modifications: Changing color, body, engine/chassis without prior LTO approval and registration update.</li>
                                            <li>Tampered or Improvised Plates: Using unauthorized, fake, or improperly attached plates, or illegal transfer of plates/stickers.</li>
                                            <li>Suspended or Revoked CR/OR: Operating while CR/OR is suspended or revoked.</li>
                                            <li>Defective or Missing Parts: Operating with missing/unsafe required parts that pose public safety hazard.</li>
                                            <li>Colorum Operations: Operating PUVs without valid CPC or franchise.</li>
                                            <li>Fake Driver's Licenses or Documents: Presenting fraudulent licenses, fake OR/CR, or tampered franchising documents.</li>
                                            <li>Major Driving &amp; Crime Violations: Using vehicle in commission of crime or severe offenses upon court conviction.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Location <span class="text-danger">*</span></label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Violation location" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Latitude</label>
                <input type="number" step="0.0000001" name="latitude" id="latitude" class="form-control" value="{{ old('latitude') }}" placeholder="e.g. 15.4872">
            </div>
            <div class="col-md-4">
                <label class="form-label">Longitude</label>
                <input type="number" step="0.0000001" name="longitude" id="longitude" class="form-control" value="{{ old('longitude') }}" placeholder="e.g. 120.9762">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" id="btnCurrentLocation" class="btn btn-outline-primary w-100">
                    <i class="bi bi-crosshair me-1"></i>Get Current Location
                </button>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Evidence Photos <span class="text-danger">*</span></label>
                <div class="evidence-capture" id="evidenceCapture">
                    <input type="file" name="evidence[]" id="evidenceCamera" accept="image/*" capture="environment" class="d-none" multiple>
                    <input type="file" name="evidence[]" id="evidenceGallery" accept="image/*" class="d-none" multiple>
                    <div class="evidence-buttons">
                        <button type="button" class="btn btn-outline-primary" id="btnCamera">
                            <i class="bi bi-camera me-1"></i>Take Photo
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnGallery">
                            <i class="bi bi-folder2-open me-1"></i>Choose File
                        </button>
                    </div>
                    <small class="form-text text-muted d-block mt-2">At least one photo is required. You may attach multiple photos.</small>
                    <div class="evidence-preview row g-2 mt-2" id="evidencePreview"></div>
                    @error('evidence')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="col-12" id="impoundingSection" style="display:none;">
                <div class="card border-danger">
                    <div class="card-header bg-danger-subtle">
                        <strong><i class="bi bi-truck-front me-2"></i>Impounding Details</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Impounding Notes (optional)</label>
                            <textarea name="impounding_notes" rows="3" class="form-control" placeholder="e.g. Vehicle parked obstructing traffic">{{ old('impounding_notes') }}</textarea>
                            @error('impounding_notes')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <small class="form-text text-muted">Impounding evidence will reuse the citation evidence photos submitted above.</small>
                    </div>
                </div>
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

    const cameraInput = document.getElementById('evidenceCamera');
    const galleryInput = document.getElementById('evidenceGallery');
    const preview = document.getElementById('evidencePreview');

    function renderPreview() {
        preview.innerHTML = '';
        [cameraInput, galleryInput].forEach(input => {
            const files = Array.from(input.files || []);
            files.forEach((file, fileIdx) => {
                if (!file.type.startsWith('image/')) return;
                const col = document.createElement('div');
                col.className = 'col-auto';
                const thumb = document.createElement('div');
                thumb.className = 'ev-thumb';
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                thumb.appendChild(img);
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ev-remove';
                btn.setAttribute('aria-label', 'Remove photo');
                btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                btn.addEventListener('click', () => {
                    const dt = new DataTransfer();
                    files.filter((_, i) => i !== fileIdx).forEach(f => dt.items.add(f));
                    input.files = dt.files;
                    renderPreview();
                });
                thumb.appendChild(btn);
                col.appendChild(thumb);
                preview.appendChild(col);
            });
        });
    }

    [cameraInput, galleryInput].forEach(input => input.addEventListener('change', renderPreview));
    document.getElementById('btnCamera').addEventListener('click', () => cameraInput.click());
    document.getElementById('btnGallery').addEventListener('click', () => galleryInput.click());

    const violationSelect = document.getElementById('violationTypeSelect');
    const impoundingSection = document.getElementById('impoundingSection');
    const impoundableInfo = document.getElementById('impoundableInfoPanel');
    function toggleImpounding() {
        if (!violationSelect) return;
        const option = violationSelect.options[violationSelect.selectedIndex];
        const isImpoundable = option && option.dataset.isImpoundable === '1';
        if (impoundingSection) impoundingSection.style.display = isImpoundable ? '' : 'none';
        if (impoundableInfo) impoundableInfo.style.display = isImpoundable ? '' : 'none';
    }
    if (violationSelect) {
        violationSelect.addEventListener('change', toggleImpounding);
        toggleImpounding();
    }

    const currentLocationBtn = document.getElementById('btnCurrentLocation');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    if (currentLocationBtn && latInput && lngInput) {
        currentLocationBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }
            this.disabled = true;
            const original = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Getting location...';
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    latInput.value = position.coords.latitude.toFixed(7);
                    lngInput.value = position.coords.longitude.toFixed(7);
                    currentLocationBtn.disabled = false;
                    currentLocationBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Location Set';
                    setTimeout(() => {
                        currentLocationBtn.innerHTML = original;
                    }, 2500);
                },
                (error) => {
                    alert('Unable to get GPS coordinates: ' + error.message);
                    currentLocationBtn.disabled = false;
                    currentLocationBtn.innerHTML = original;
                }
            );
        });
    }
</script>
@endpush
@endsection
