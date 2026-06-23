@php($user = $user ?? null)
<div class="row g-4">
    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user?->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user?->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Password{{ $user ? ' (leave blank to keep)' : '' }} <span class="text-danger">{{ $user ? '' : '*' }}</span></label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $user ? '' : 'required' }}>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user?->phone) }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Role <span class="text-danger">*</span></label>
                <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $user?->role?->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" @checked(old('is_active', $user?->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Active account</label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card bg-light border-0">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-info-circle"></i> Account Info</h6>
                <hr>
                @if ($user)
                    <div class="mb-2"><small class="text-muted d-block">Account Status</small> <span class="badge rounded-pill bg-{{ match($user->account_status) { 'approved' => 'success', 'pending' => 'warning text-dark', 'rejected' => 'danger', 'suspended' => 'secondary', default => 'info' } }}">{{ ucfirst($user->account_status) }}</span></div>
                    <div class="mb-2"><small class="text-muted d-block">Created</small> {{ $user->created_at->format('M d, Y') }}</div>
                    <div class="mb-2"><small class="text-muted d-block">Last Updated</small> {{ $user->updated_at->format('M d, Y') }}</div>
                    @if ($user->last_login_at)
                        <div class="mb-2"><small class="text-muted d-block">Last Login</small> {{ $user->last_login_at->diffForHumans() }}</div>
                    @endif
                    @if ($user->email_verified_at)
                        <div class="mb-0"><small class="text-muted d-block">Email Verified</small> {{ $user->email_verified_at->format('M d, Y') }}</div>
                    @endif
                @else
                    <p class="text-muted small mb-0">Account details will appear here after creation.</p>
                @endif
            </div>
        </div>
    </div>
</div>
