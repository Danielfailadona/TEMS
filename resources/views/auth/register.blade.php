@extends('layouts.guest')

@section('title', 'Create Account')

@section('content')
<div class="text-center mb-4 animate-on-load">
    <div style="background: linear-gradient(135deg, #2563eb, #0f2b4a); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">LTEM</div>
    <p class="text-muted mb-0">Create your account</p>
</div>

<form method="POST" action="{{ route('register') }}" class="animate-on-load">
    @csrf
    
    <div class="mb-3">
        <label for="name" class="form-label fw-semibold">Full Name</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
                   id="name" name="name" value="{{ old('name') }}" placeholder="John Doe" required>
        </div>
        @error('name')
            <small class="text-danger d-block mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Email Address</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email') }}" placeholder="user@example.com" required>
        </div>
        @error('email')
            <small class="text-danger d-block mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label for="phone" class="form-label fw-semibold">Phone Number</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                   id="phone" name="phone" value="{{ old('phone') }}" placeholder="+63912345678">
        </div>
        @error('phone')
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
        <small class="text-muted d-block mt-2">At least 8 characters with uppercase, lowercase, and numbers</small>
        @error('password')
            <small class="text-danger d-block mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-lock-check"></i></span>
            <input type="password" class="form-control"
                   id="password_confirmation" name="password_confirmation" placeholder="••••••••" required>
        </div>
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
        <label class="form-check-label" for="terms">
            I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a>
        </label>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold mb-3">
        <i class="bi bi-person-plus me-2"></i>Create Account
    </button>

    <hr class="my-3">

    <div class="text-center">
        <p class="mb-0 text-muted">Already have an account? <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Sign In</a></p>
    </div>
</form>
@endsection
