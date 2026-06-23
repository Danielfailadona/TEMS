@extends('layouts.guest')

@section('title', 'Verifying Email')

@section('content')
<div class="text-center mb-4 animate-on-load">
    <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="40" class="mb-2">
    <h4 class="mb-1">Verifying Your Email</h4>
    <p class="text-muted mb-0">Please wait while we verify your email address...</p>
</div>

<div class="text-center py-4 animate-on-load">
    <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Verifying...</span>
    </div>
</div>

<form id="verifyForm" method="POST" action="{{ route('verification.verify') }}" style="display:none;">
    @csrf
    <input type="hidden" name="access_token" id="access_token" value="">
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.substring(1);
    const params = new URLSearchParams(hash);
    const accessToken = params.get('access_token');
    if (accessToken) {
        document.getElementById('access_token').value = accessToken;
        document.getElementById('verifyForm').submit();
    }
});
</script>
@endpush
@endsection
