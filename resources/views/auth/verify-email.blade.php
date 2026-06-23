@extends('layouts.guest')

@section('title', 'Verify Email')

@section('content')
<div class="text-center animate-on-load">
    <div class="login-brand-icon mb-3">
        <i class="bi bi-envelope-check" style="font-size:1.5rem;"></i>
    </div>
    <h4 class="fw-bold mb-1">Verify Your Email</h4>
    <p class="text-muted small mb-4">A verification link has been sent to your email address.</p>

    <div class="alert alert-info text-start py-3 mb-4" role="alert" style="border-left:4px solid #2563eb;border-radius:0.75rem;">
        <div class="d-flex gap-2">
            <i class="bi bi-envelope-open mt-1" style="color:#2563eb;"></i>
            <div class="small">
                <strong class="d-block mb-1">Check your inbox</strong>
                Please click the verification link in the email to activate your account.
                If you don't see it, check your spam or junk folder.
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2 small animate-on-load" style="border-radius:0.75rem;">
            <i class="bi bi-check-circle me-1"></i>{{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.resend') }}" class="animate-on-load">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold small">Email Address</label>
            <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email" value="{{ old('email', auth()->user()?->email) }}" placeholder="your@email.com" required>
            </div>
            @error('email')
                <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
            <i class="bi bi-send me-2"></i>Resend Verification Email
        </button>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left me-1"></i>Back to Sign In
            </a>
        </div>
    </form>
</div>
@endsection
