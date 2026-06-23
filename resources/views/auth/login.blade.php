@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
<div class="text-center mb-4 animate-on-load">
    <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="48" class="mb-2">
    <p class="text-muted mb-0">Transportation Enforcement Management System</p>
</div>

<form method="POST" action="{{ route('login') }}" class="animate-on-load">
    @csrf
    
    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Email Address</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email') }}" placeholder="admin@example.com" required autofocus>
        </div>
        @error('email')
            <small class="text-danger d-block mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold">Password</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control @error('password') is-invalid @enderror"
                   id="password" name="password" placeholder="••••••••" required>
        </div>
        @error('password')
            <small class="text-danger d-block mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">Remember me for 30 days</label>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold mb-2">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
    </button>
    <div class="text-center mb-3">
        <a href="{{ route('password.request') }}" class="text-decoration-none small">Forgot password?</a>
    </div>

    <hr class="my-3">

    <div class="text-center mb-3">
        <p class="mb-0 text-muted">Don't have an account? <a href="{{ route('register') }}" class="fw-semibold text-decoration-none">Create one</a></p>
    </div>
</form>

<div class="mt-4 p-3 bg-light rounded-2 animate-on-load" style="animation-delay: 0.1s;">
    <div class="fw-semibold text-dark mb-2"><i class="bi bi-info-circle me-2"></i>Demo Account</div>
    <small class="text-muted">
        <div class="mb-1">Email: <code>admin@example.com</code></div>
        <div>Password: <code>password</code></div>
    </small>
</div>
@endsection

