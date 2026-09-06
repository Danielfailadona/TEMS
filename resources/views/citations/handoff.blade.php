@extends('layouts.app')

@section('title', 'Citation #'.$citation->citation_number.' — Handoff')

@section('content')
<div class="text-center animate-on-load">
    <div class="mb-2">
        <span class="badge {{ $citation->status->badgeClass() }} fs-6">{{ $citation->status->label() }}</span>
    </div>
    <h1 class="h3 mb-1">{{ $citation->citation_number }}</h1>
    <p class="text-muted mb-0">Show this QR to the violator to pay or view the ticket.</p>
</div>

<div class="card stat-card mx-auto mt-4 animate-on-load" style="max-width: 460px;">
    <div class="card-body text-center py-4" id="handoffCard">
        <div class="bg-white p-3 d-inline-block rounded border">
            {!! $citation->getQRCodeSvg(320) !!}
        </div>
        <div class="small text-muted mt-3">
            <i class="bi bi-info-circle me-1"></i>
            The violator scans this code to view the citation and pay online instantly.
        </div>
    </div>
</div>

<div class="card stat-card mx-auto mt-3 animate-on-load" style="max-width: 460px;">
    <div class="card-body">
        <div class="row g-2 text-start">
            <div class="col-6">
                <small class="text-muted d-block">Citation #</small>
                <strong>{{ $citation->citation_number }}</strong>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">Amount Due</small>
                <strong class="text-success">₱{{ number_format($citation->penalty_amount, 2) }}</strong>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">Plate Number</small>
                <strong>{{ $citation->vehicle_plate }}</strong>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">Violation</small>
                <strong>{{ $citation->violationType->name }}</strong>
            </div>
        </div>
    </div>
</div>

@if ($citation->evidence->isNotEmpty())
    <div class="card stat-card mx-auto mt-3 animate-on-load" style="max-width: 460px;">
        <div class="card-body">
            <div class="small fw-semibold text-muted mb-2">
                <i class="bi bi-images me-1"></i>Evidence ({{ $citation->evidence->count() }})
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                @foreach ($citation->evidence as $evidence)
                    <img src="{{ asset('storage/'.$evidence->file_path) }}" alt="Evidence"
                         class="rounded border" style="width:80px;height:80px;object-fit:cover;" onclick="openModal('{{ asset('storage/'.$evidence->file_path) }}')">
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Evidence Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="modalImage" src="" alt="Evidence" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<div class="handoff-actions">
    <a href="{{ route('citations.print', $citation) }}" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-printer me-2"></i>Print
    </a>
    <a href="mailto:?subject=Citation {{ $citation->citation_number }}&body={{ urlencode('Your citation: '.$citation->getPublicPaymentUrl()) }}"
       class="btn btn-outline-secondary">
        <i class="bi bi-share me-2"></i>Share
    </a>
    <a href="{{ route('citations.index') }}" class="btn btn-primary">
        <i class="bi bi-check-lg me-2"></i>Done
    </a>
</div>

<style>
    .handoff-actions {
        display: flex;
        gap: 0.5rem;
        padding: 0.5rem;
        position: sticky;
        bottom: 62px;
        z-index: 1020;
        background: rgba(var(--itevcms-primary-rgb, 37, 99, 235), 0.08);
        border: 1px solid rgba(var(--itevcms-primary-rgb, 37, 99, 235), 0.15);
        border-radius: 0.9rem;
        max-width: 460px;
        margin: 1rem auto 2rem;
    }
    .handoff-actions .btn { flex: 1; }
</style>

<script>
    function openModal(src) {
        document.getElementById('modalImage').src = src;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
</script>
@endpush