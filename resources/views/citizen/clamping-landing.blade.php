@extends('layouts.guest')

@section('title', 'Request Clamping')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center mb-5 animate-on-load">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-lock-fill text-primary" style="font-size: 2rem;"></i>
                </div>
                <h1 class="h2 fw-bold">Report Illegal Parking</h1>
                <p class="text-muted">Help keep our streets safe by reporting illegally parked vehicles.</p>
            </div>

            <div class="row g-4 animate-on-load">
                <!-- Request Clamping Card -->
                <div class="col-md-6">
                    <a href="{{ route('citizen.clamping.form') }}" class="text-decoration-none h-100">
                        <div class="card stat-card h-100 border-0 shadow-sm card-hover">
                            <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-center align-items-center text-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                    <i class="bi bi-plus-circle text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h3 class="h4 fw-bold mb-2">Request Clamping</h3>
                                <p class="text-muted mb-4">Report an illegally parked vehicle by providing location, vehicle details, and photo evidence.</p>
                                <div class="d-flex flex-wrap gap-2 justify-content-center text-muted small">
                                    <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-geo-alt me-1"></i> GPS Location</span>
                                    <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-camera me-1"></i> Photo Evidence</span>
                                    <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-shield-check me-1"></i> Verified</span>
                                </div>
                                <div class="mt-4">
                                    <span class="btn btn-primary btn-lg px-4">Start Request</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Track Request Card -->
                <div class="col-md-6">
                    <a href="{{ route('citizen.clamping.track') }}" class="text-decoration-none h-100">
                        <div class="card stat-card h-100 border-0 shadow-sm card-hover">
                            <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-center align-items-center text-center">
                                <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                    <i class="bi bi-search text-info" style="font-size: 2rem;"></i>
                                </div>
                                <h3 class="h4 fw-bold mb-2">Track Request</h3>
                                <p class="text-muted mb-4">Check the status of your clamping request using the reference number.</p>
                                <div class="d-flex flex-wrap gap-2 justify-content-center text-muted small">
                                    <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-clock-history me-1"></i> Real-time Status</span>
                                    <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-person-badge me-1"></i> Assigned Officer</span>
                                    <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-check-circle me-1"></i> Resolution</span>
                                </div>
                                <div class="mt-4">
                                    <span class="btn btn-info btn-lg px-4">Track Request</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12">
                    <div class="card stat-card border-0 shadow-sm animate-on-load">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>How It Works</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-3 text-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 60px; height: 60px;">
                                        <i class="bi bi-1-circle text-primary" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <h6 class="fw-bold">Submit Request</h6>
                                    <p class="text-muted small">Fill in the vehicle details, location, and upload photo evidence.</p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 60px; height: 60px;">
                                        <i class="bi bi-2-circle text-info" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <h6 class="fw-bold">Review & Verify</h6>
                                    <p class="text-muted small">Our team reviews your request and verifies the violation.</p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 60px; height: 60px;">
                                        <i class="bi bi-3-circle text-warning" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <h6 class="fw-bold">Clamping Action</h6>
                                    <p class="text-muted small">If verified, our team clamps the vehicle and secures the area.</p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 60px; height: 60px;">
                                        <i class="bi bi-4-circle text-success" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <h6 class="fw-bold">Resolution</h6>
                                    <p class="text-muted small">Vehicle owner pays fines and clamping is released upon payment.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.12) !important;
    }
    .animate-on-load {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards;
    }
    .animate-on-load:nth-child(1) { animation-delay: 0.1s; }
    .animate-on-load:nth-child(2) { animation-delay: 0.2s; }
    @keyframes fadeInUp {
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush