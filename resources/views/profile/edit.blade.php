@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center p-4">
                <div class="position-relative d-inline-block mb-3">
                    <div class="avatar-preview rounded-circle overflow-hidden border border-3 border-white shadow"
                         style="width: 120px; height: 120px; background: linear-gradient(135deg, #2563eb, #0f2b4a);">
                        @if($user->profile_photo_path)
                            <img src="{{ Storage::url($user->profile_photo_path) }}" alt="{{ $user->name }}"
                                 class="w-100 h-100 object-fit-cover">
                        @else
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center text-white"
                                 style="font-size: 2.5rem; font-weight: 700;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-light rounded-circle position-absolute bottom-0 end-0 shadow-sm border"
                            onclick="document.getElementById('photoInput').click()" title="Change photo">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>

                <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                <p class="text-muted small mb-2">{{ $user->email }}</p>

                <span class="badge bg-primary fs-6 px-3 py-1 rounded-pill">
                    {{ $user->role->label() }}
                </span>

                <hr class="my-3">

                <div class="text-start small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Status</span>
                        @php
                            $statusClass = match($user->account_status) {
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                'suspended' => 'secondary',
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $statusClass }} rounded-pill">{{ ucfirst($user->account_status) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Phone</span>
                        <span>{{ $user->phone ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Last Login</span>
                        <span>{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Member Since</span>
                        <span>{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <input type="file" id="photoInput" name="profile_photo" accept="image/*" class="d-none"
                   onchange="previewAvatar(event)">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-person me-2"></i>Personal Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            </div>
                            @error('name')
                                <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            </div>
                            @error('email')
                                <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold small">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+63912345678">
                            </div>
                            @error('phone')
                                <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-lock me-2"></i>Change Password</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="current_password" class="form-label fw-semibold small">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                       id="current_password" name="current_password" placeholder="Enter current password">
                            </div>
                            @error('current_password')
                                <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="new_password" class="form-label fw-semibold small">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-check"></i></span>
                                <input type="password" class="form-control @error('new_password') is-invalid @enderror"
                                       id="new_password" name="new_password" placeholder="Min. 8 characters">
                            </div>
                            <small class="text-muted d-block mt-1">At least 8 characters with uppercase, lowercase, and numbers</small>
                            @error('new_password')
                                <small class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="new_password_confirmation" class="form-label fw-semibold small">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-check"></i></span>
                                <input type="password" class="form-control"
                                       id="new_password_confirmation" name="new_password_confirmation" placeholder="Repeat new password">
                            </div>
                        </div>
                    </div>
                    <small class="text-info d-block mt-2"><i class="bi bi-info-circle me-1"></i>Leave password fields blank to keep current password.</small>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-check-lg me-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewAvatar(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.avatar-preview');
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-100 h-100 object-fit-cover">`;
            }
        };
        reader.readAsDataURL(file);
    }
</script>
@endpush
