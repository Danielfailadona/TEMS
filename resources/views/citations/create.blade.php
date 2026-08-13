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
                    <small class="form-text text-muted d-block mt-2">You may attach multiple photos.</small>
                    <div class="evidence-preview row g-2 mt-2" id="evidencePreview"></div>
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
</script>
@endpush
@endsection
