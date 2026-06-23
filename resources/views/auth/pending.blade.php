@extends('layouts.guest')

@section('title', 'Account Pending Approval')

@section('content')
<div class="text-center animate-on-load">
    <div class="login-brand-icon mb-3">
        <i class="bi bi-clock-history" style="font-size:1.5rem;"></i>
    </div>
    <h4 class="fw-bold mb-1">Account Pending Approval</h4>
    <p class="text-muted small mb-4">Your account is under review by an administrator.</p>

    <div class="d-flex justify-content-center gap-3 mb-4">
        <div class="text-center" style="flex:1;max-width:100px;">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1"
                 style="width:36px;height:36px;background:#10b981;color:#fff;font-size:0.85rem;">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="small fw-semibold text-muted" style="font-size:0.65rem;line-height:1.2;">Registered</div>
        </div>
        <div class="d-flex align-items-center" style="flex:0 0 40px;">
            <div class="w-100" style="height:2px;background:#2563eb;"></div>
        </div>
        <div class="text-center" style="flex:1;max-width:100px;">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1"
                 style="width:36px;height:36px;background:#2563eb;color:#fff;font-size:0.85rem;box-shadow:0 0 0 4px rgba(37,99,235,0.2);">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="small fw-semibold" style="font-size:0.65rem;line-height:1.2;color:#2563eb;">Pending Review</div>
        </div>
        <div class="d-flex align-items-center" style="flex:0 0 40px;">
            <div class="w-100" style="height:2px;background:#e2e8f0;"></div>
        </div>
        <div class="text-center" style="flex:1;max-width:100px;">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1"
                 style="width:36px;height:36px;background:#e2e8f0;color:#94a3b8;font-size:0.85rem;">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="small fw-semibold text-muted" style="font-size:0.65rem;line-height:1.2;">Approved</div>
        </div>
    </div>

    <div class="alert alert-info text-start py-3 mb-3" role="alert" style="border-left:4px solid #2563eb;border-radius:0.75rem;">
        <div class="d-flex gap-2">
            <i class="bi bi-info-circle mt-1" style="color:#2563eb;"></i>
            <div>
                <strong class="d-block small mb-1">What happens next?</strong>
                <ul class="mb-0 small" style="padding-left:1.1rem;">
                    <li>An administrator reviews your registration details.</li>
                    <li>You'll receive a notification once your account is approved.</li>
                    <li>You can then sign in and access the enforcement dashboard.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-light rounded-3 p-3 mb-4 text-start" style="border-radius:0.75rem !important;">
        <div class="d-flex gap-2">
            <i class="bi bi-headset mt-1" style="color:#64748b;"></i>
            <div class="small text-muted">
                <strong class="d-block text-dark mb-1">Need help?</strong>
                Contact the system administrator to expedite your approval.
            </div>
        </div>
    </div>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-secondary w-100 py-2">
            <i class="bi bi-arrow-left me-2"></i>Back to Login
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('account.procedure', ['form' => 'register']) }}" class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Return to Registration
        </a>
    </div>
</div>
@endsection
