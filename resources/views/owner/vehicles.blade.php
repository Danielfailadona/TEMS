@extends('layouts.app')

@section('title', 'My Vehicles')

@section('content')
<h1 class="h3 mb-4">My Vehicles</h1>
<div class="card stat-card">
    <div class="card-body text-center py-5">
        <i class="bi bi-car-front-fill fs-1 text-muted mb-3 d-block"></i>
        <p class="mb-0">Vehicle information is now captured directly on each citation record.</p>
        <a href="{{ route('owner.citations') }}" class="btn btn-outline-primary mt-3">View My Citations</a>
    </div>
</div>
@endsection
