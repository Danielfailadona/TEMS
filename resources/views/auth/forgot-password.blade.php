@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
<div class="text-center mb-4 animate-on-load">
    <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="40" class="mb-2">
    <h4 class="mb-1">Forgot Password</h4>
    <p class="text-muted mb-0">Enter your email to receive a reset link.</p>
</div>

@if (session('status'))
    <div class="alert alert-success py-2 small animate-on-load">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="animate-on-load">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Email Address</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email') }}" placeholder="your@email.com" required autofocus>
        </div>
        @error('email')
            <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
        <i class="bi bi-envelope-paper me-2"></i>Send Reset Link
    </button>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Back to Sign In
        </a>
    </div>
</form>
@endsection
