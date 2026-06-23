@extends('layouts.guest')

@section('title', 'Request Submitted')

@section('content')
<div class="container py-5" style="max-width: 500px;">
    <div class="text-center animate-on-load">
        <div style="width: 4rem; height: 4rem; background: rgba(16, 185, 129, 0.12); border-radius: 1rem; display: grid; place-items: center; color: #10b981; font-size: 2rem; margin: 0 auto 1.5rem;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h2 class="fw-bold mb-2">Request Submitted Successfully!</h2>
        <p class="text-muted mb-4">Your clamping request has been received and will be reviewed by our enforcement team.</p>
    </div>

    <div class="card stat-card mb-4 animate-on-load" style="animation-delay: 0.1s;">
        <div class="card-body">
            <h6 class="text-primary fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>What Happens Next</h6>
            <div class="d-flex gap-3 mb-3">
                <div style="width: 2rem; height: 2rem; background: #2563eb; color: white; border-radius: 999px; display: grid; place-items: center; font-size: 0.85rem; font-weight: bold; flex-shrink: 0;">1</div>
                <div>
                    <div class="fw-semibold">We'll Review Your Request</div>
                    <small class="text-muted">Within 1 hour, our team will verify the vehicle and location</small>
                </div>
            </div>
            <div class="d-flex gap-3 mb-3">
                <div style="width: 2rem; height: 2rem; background: #2563eb; color: white; border-radius: 999px; display: grid; place-items: center; font-size: 0.85rem; font-weight: bold; flex-shrink: 0;">2</div>
                <div>
                    <div class="fw-semibold">Enforcement Action</div>
                    <small class="text-muted">If approved, we'll dispatch officers to secure the vehicle</small>
                </div>
            </div>
            <div class="d-flex gap-3">
                <div style="width: 2rem; height: 2rem; background: #2563eb; color: white; border-radius: 999px; display: grid; place-items: center; font-size: 0.85rem; font-weight: bold; flex-shrink: 0;">3</div>
                <div>
                    <div class="fw-semibold">You'll Be Notified</div>
                    <small class="text-muted">We'll send updates via email and SMS to {{ session()->get('email', 'your email') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info d-flex gap-2 align-items-start animate-on-load" style="animation-delay: 0.2s;">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>
            <strong>Reference Number:</strong><br>
            <small class="text-muted">Save this number for your records (issued via email)</small>
        </div>
    </div>

    <div class="d-flex gap-2 flex-column">
        <a href="{{ route('citizen.citation.lookup') }}" class="btn btn-primary">
            <i class="bi bi-house-fill me-2"></i>Back to Citizen Portal
        </a>
        <a href="/" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Return to Home
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-on-load').forEach(el => observer.observe(el));
});
</script>

@endsection
