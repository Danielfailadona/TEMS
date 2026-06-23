@extends('layouts.app')

@section('title', 'Payment Cancelled')

@section('content')
<div class="text-center py-5 animate-on-load">
    <div class="mb-4">
        <i class="bi bi-x-circle-fill text-danger" style="font-size: 5rem;"></i>
    </div>
    <h2 class="mb-2">Payment Cancelled</h2>
    <p class="text-muted mb-4">Your payment was not completed. No charges have been made.</p>

    <div class="d-flex justify-content-center gap-3">
        <a href="{{ route('citations.index') }}" class="btn btn-primary">
            <i class="bi bi-file-earmark-text me-2"></i>My Citations
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
    </div>
</div>
@endsection
