@extends('layouts.app')

@section('title', 'Report Submitted')

@push('styles')
<style>
    .animate-on-load {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards;
    }
    .animate-on-load:nth-child(1) { animation-delay: 0.1s; }
    .animate-on-load:nth-child(2) { animation-delay: 0.2s; }
    @keyframes fadeInUp {
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" style="z-index: 100;">
    <div class="container-fluid px-4 px-lg-5">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('welcome') }}">
            <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="32" class="me-2">
            <span style="background: linear-gradient(135deg, #0f2b4a, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">TEMs</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('welcome') }}#features">Features</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('citizen.citation.lookup') }}">Ticket Lookup</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="{{ route('citizen.report-parking.landing') }}">Report Parking</a></li>
                <li class="nav-item">
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign In</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign Up</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-5 animate-on-load">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                </div>
                <h1 class="h2 fw-bold">Report Submitted Successfully!</h1>
                <p class="text-muted">Your report has been received and is now under review.</p>
            </div>

            <div class="card stat-card mb-4 animate-on-load">
                <div class="card-body p-4 text-center">
                    <div class="mb-3">
                        <span class="text-muted small d-block">Your Report Number</span>
                        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                            <div class="bg-primary bg-opacity-10 text-primary px-4 py-2 rounded font-monospace fw-bold fs-4" style="letter-spacing: 0.1em;">{{ $reference }}</div>
                            <button type="button" class="btn btn-outline-primary" onclick="copyReference()" id="copyBtn">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-info d-inline-flex align-items-center gap-2 mb-0" style="max-width: 100%;">
                        <i class="bi bi-info-circle me-1"></i>
                        <span class="small">Save this report number to track your report status.</span>
                    </div>
                </div>
            </div>

            <div class="card stat-card mb-4 animate-on-load">
                <div class="card-header bg-white"><strong>What Happens Next?</strong></div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-3 text-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 60px; height: 60px;">
                                <i class="bi bi-1-circle text-primary" style="font-size: 1.5rem;"></i>
                            </div>
                            <h6 class="fw-bold">Under Review</h6>
                            <p class="text-muted small">Our team will verify your report within 24 hours.</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 60px; height: 60px;">
                                <i class="bi bi-2-circle text-info" style="font-size: 1.5rem;"></i>
                            </div>
                            <h6 class="fw-bold">Verification</h6>
                            <p class="text-muted small">We'll verify the location and vehicle details.</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 60px; height: 60px;">
                                <i class="bi bi-3-circle text-warning" style="font-size: 1.5rem;"></i>
                            </div>
                            <h6 class="fw-bold">Action Taken</h6>
                            <p class="text-muted small">If verified, our team will clamp the vehicle.</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 60px; height: 60px;">
                                <i class="bi bi-4-circle text-success" style="font-size: 1.5rem;"></i>
                            </div>
                            <h6 class="fw-bold">Resolution</h6>
                            <p class="text-muted small">Vehicle owner pays fines and clamping is released.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4 animate-on-load">
                <a href="{{ route('citizen.report-parking.track') }}" class="btn btn-outline-primary px-4 py-2">
                    <i class="bi bi-search me-1"></i> Track Your Report
                </a>
                <a href="{{ route('citizen.report-parking.landing') }}" class="btn btn-outline-secondary ms-2 px-4 py-2">
                    <i class="bi bi-plus-circle me-1"></i> Submit Another
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .animate-on-load {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards;
    }
    .animate-on-load:nth-child(1) { animation-delay: 0.1s; }
    .animate-on-load:nth-child(2) { animation-delay: 0.2s; }
    @keyframes fadeInUp {
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@push('scripts')
<script>
function copyReference() {
    const ref = '{{ $reference }}';
    navigator.clipboard.writeText(ref).then(() => {
        const btn = document.getElementById('copyBtn');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check me-1"></i> Copied!';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    }).catch(() => {
        const btn = document.getElementById('copyBtn');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Copy failed';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-danger');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    });
}
</script>
@endpush