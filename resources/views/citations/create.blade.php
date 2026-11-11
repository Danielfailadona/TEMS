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
                        <option value="{{ $type->id }}" data-is-impoundable="{{ $type->is_impoundable ? '1' : '0' }}" data-is-clampable="{{ $type->is_clampable ? '1' : '0' }}" data-is-custom="{{ $type->isCustom() ? '1' : '0' }}" @selected(old('violation_type_id') == $type->id)>
                            {{ $type->name }} — ₱{{ number_format($type->penalty_amount, 2) }}
                        </option>
                    @endforeach
                </select>
                <div class="mt-2" id="customViolationPanel" style="display:none;">
                    <div class="card border-info bg-info-subtle">
                        <div class="card-body py-2">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold mb-1">Violation Description <span class="text-danger">*</span></label>
                                    <input type="text" name="custom_violation_name" id="customViolationName" class="form-control form-control-sm" value="{{ old('custom_violation_name') }}" maxlength="255" placeholder="Describe the violation">
                                    @error('custom_violation_name')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold mb-1">Penalty Amount (₱) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="penalty_amount" id="customPenaltyAmount" class="form-control form-control-sm" value="{{ old('penalty_amount') }}" placeholder="e.g. 500.00">
                                    @error('penalty_amount')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
</div>
            </div>
        </div>
    </div>
            </div>

            <div class="row g-3 mt-2" id="violationDetailsPanels" style="display:none;">
                <div class="col-12">
                    <div class="card border-info bg-info-subtle">
                        <div class="card-body py-2">
                            <small><i class="bi bi-info-circle me-1"></i>For violations that are clampable or impoundable, the clamping/impounding decision is made separately by an Administrator on the Clamping / Impounding pages — issuing this citation does not clamp or impound the vehicle.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Location <span class="text-danger">*</span></label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Violation location" required>
            </div>
            <div class="col-md-6">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Latitude</label>
                        <input type="number" step="0.0000001" name="latitude" id="latitude" class="form-control" value="{{ old('latitude') }}" placeholder="e.g. 15.4872">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" id="longitude" class="form-control" value="{{ old('longitude') }}" placeholder="e.g. 120.9762">
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <button type="button" id="btnCurrentLocation" class="btn btn-outline-primary w-100">
                            <i class="bi bi-crosshair me-1"></i>Get Current Location
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Driver behavior, weather, officer observations, etc.">{{ old('notes') }}</textarea>
            </div>
            <div class="col-md-6">
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
    const violationDetailsPanels = document.getElementById('violationDetailsPanels');
    const customViolationPanel = document.getElementById('customViolationPanel');
    function toggleViolationPanels() {
        if (!violationSelect) return;
        const option = violationSelect.options[violationSelect.selectedIndex];
        const isClampable = option && (option.dataset.isImpoundable === '1' || option.dataset.isClampable === '1');
        const isCustom = option && option.dataset.isCustom === '1';
        // Eligibility info panel: clamping/impounding happens on the Admin pages
        if (violationDetailsPanels) {
            violationDetailsPanels.style.display = isClampable ? 'flex' : 'none';
        }
        // Custom violation panel shows only for "Others" type
        if (customViolationPanel) {
            customViolationPanel.style.display = isCustom ? 'block' : 'none';
        }
    }
    if (violationSelect) {
        violationSelect.addEventListener('change', toggleViolationPanels);
        toggleViolationPanels();
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
