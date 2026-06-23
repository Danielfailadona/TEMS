@extends('layouts.guest')

@section('title', 'Reset Password')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.substring(1);
    const params = new URLSearchParams(hash);
    const accessToken = params.get('access_token');
    if (accessToken) {
        document.getElementById('access_token').value = accessToken;
    }
});
</script>
@endpush

@section('content')
<div class="text-center mb-4 animate-on-load">
    <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="40" class="mb-2">
    <h4 class="mb-1">Reset Password</h4>
    <p class="text-muted mb-0">Choose a new password for your account.</p>
</div>

<form method="POST" action="{{ route('password.update') }}" class="animate-on-load">
    @csrf
    <input type="hidden" name="access_token" id="access_token" value="">

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold">New Password</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control @error('password') is-invalid @enderror"
                   id="password" name="password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required autofocus>
        </div>
        @error('password')
            <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password-confirm" class="form-label fw-semibold">Confirm Password</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-lock-check"></i></span>
            <input type="password" class="form-control" id="password-confirm" name="password_confirmation" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
        <i class="bi bi-check-circle me-2"></i>Reset Password
    </button>
</form>
@endsection
