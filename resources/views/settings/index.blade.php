@extends('layouts.app')

@section('title', 'Settings')

@push('styles')
<style>
    .settings-section { margin-bottom: 2rem; }
    .settings-card {
        border: none; border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    }
    .settings-card .card-header {
        background: transparent; border-bottom: 1px solid #f1f5f9;
        padding: 1.25rem 1.5rem; font-weight: 700; font-size: 0.95rem;
    }
    .settings-card .card-body { padding: 1.5rem; }
    .toggle-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.75rem 0; border-bottom: 1px solid #f8fafc;
    }
    .toggle-row:last-child { border-bottom: none; }
    .toggle-row .form-check-label { font-weight: 500; cursor: pointer; }
    .toggle-row .form-text { font-size: 0.75rem; color: #94a3b8; margin: 0; }
    .session-row {
        display: flex; align-items: center; gap: 1rem;
        padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9;
    }
    .session-row:last-child { border-bottom: none; }
    .session-device { flex: 1; }
    .session-device .device-icon { font-size: 1.25rem; width: 2rem; text-align: center; }
    .session-device .device-name { font-weight: 500; font-size: 0.875rem; }
    .session-device .device-meta { font-size: 0.75rem; color: #94a3b8; }
    .session-current .badge { font-size: 0.65rem; }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('settings.update') }}">
            @csrf
            @method('PUT')

            {{-- Notification Preferences --}}
            <div class="card settings-card settings-section">
                <div class="card-header">
                    <i class="bi bi-bell me-2"></i>Notification Preferences
                </div>
                <div class="card-body">
                    <div class="toggle-row">
                        <div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="notify_email" name="notify_email" value="1"
                                       @checked($user->preferences['notifications']['email'] ?? true)>
                                <label class="form-check-label" for="notify_email">
                                    <i class="bi bi-envelope me-1 text-muted"></i>Email Notifications
                                </label>
                            </div>
                            <div class="form-text">Receive notifications via email</div>
                        </div>
                    </div>
                    <div class="toggle-row">
                        <div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="notify_in_app" name="notify_in_app" value="1"
                                       @checked($user->preferences['notifications']['in_app'] ?? true)>
                                <label class="form-check-label" for="notify_in_app">
                                    <i class="bi bi-bell me-1 text-muted"></i>In-App Notifications
                                </label>
                            </div>
                            <div class="form-text">Show notification badge and alerts in the app</div>
                        </div>
                    </div>

                    <hr class="my-2">
                    <div class="small fw-semibold text-muted mb-2">Notify me about:</div>

                    <div class="toggle-row">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="notify_citations" name="notify_citations" value="1"
                                   @checked($user->preferences['notifications']['citations'] ?? true)>
                            <label class="form-check-label" for="notify_citations">Citation events</label>
                        </div>
                    </div>
                    <div class="toggle-row">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="notify_payments" name="notify_payments" value="1"
                                   @checked($user->preferences['notifications']['payments'] ?? true)>
                            <label class="form-check-label" for="notify_payments">Payment confirmations</label>
                        </div>
                    </div>
                    <div class="toggle-row">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="notify_appeals" name="notify_appeals" value="1"
                                   @checked($user->preferences['notifications']['appeals'] ?? true)>
                            <label class="form-check-label" for="notify_appeals">Appeal submissions & reviews</label>
                        </div>
                    </div>
                    <div class="toggle-row">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="notify_clamping" name="notify_clamping" value="1"
                                   @checked($user->preferences['notifications']['clamping'] ?? true)>
                            <label class="form-check-label" for="notify_clamping">Clamping & impounding updates</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Display Preferences --}}
            <div class="card settings-card settings-section">
                <div class="card-header">
                    <i class="bi bi-layout-three-columns me-2"></i>Display Preferences
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Items Per Page</label>
                            <select name="pagination_size" class="form-select">
                                <option value="10" @selected(($user->preferences['pagination_size'] ?? 10) == 10)>10</option>
                                <option value="25" @selected(($user->preferences['pagination_size'] ?? 10) == 25)>25</option>
                                <option value="50" @selected(($user->preferences['pagination_size'] ?? 10) == 50)>50</option>
                            </select>
                            <div class="form-text">Number of items shown in tables</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($user->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
            {{-- GPS Tracking --}}
            <div class="card settings-card settings-section">
                <div class="card-header">
                    <i class="bi bi-satellite me-2"></i>GPS Tracking
                </div>
                <div class="card-body">
                    <div class="toggle-row">
                        <div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="gps_enabled" name="gps_enabled" value="1"
                                       @checked($user->preferences['gps_enabled'] ?? $gpsLocation?->status === 'active')>
                                <label class="form-check-label" for="gps_enabled">
                                    <i class="bi bi-geo-alt me-1 text-muted"></i>Enable GPS Tracking
                                </label>
                            </div>
                            <div class="form-text">Allow the system to track your real-time location for zone monitoring and dispatching.</div>
                        </div>
                    </div>
                    @if ($gpsLocation && $gpsLocation->latitude && $gpsLocation->longitude)
                    <hr class="my-2">
                    <div class="small text-muted">
                        <i class="bi bi-info-circle me-1"></i>Last known location:
                        <code>{{ $gpsLocation->latitude }}, {{ $gpsLocation->longitude }}</code>
                        @if ($gpsLocation->last_seen_at)
                            &middot; {{ $gpsLocation->last_seen_at->diffForHumans() }}
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="text-end mb-4">
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-check-lg me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        {{-- Active Sessions --}}
        <div class="card settings-card settings-section">
            <div class="card-header">
                <i class="bi bi-laptop me-2"></i>Active Sessions
            </div>
            <div class="card-body">
                @if ($sessions->isEmpty())
                    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>No active sessions found.</p>
                @else
                    @php
                        $currentSessionId = session()->getId();
                    @endphp
                    @foreach ($sessions as $session)
                        <div class="session-row">
                            <div class="session-device d-flex align-items-center gap-2">
                                <span class="device-icon">
                                    @php
                                        $ua = $session->user_agent ?? '';
                                        $isMobile = preg_match('/Mobile|Android|iPhone/', $ua);
                                        $isChrome = preg_match('/Chrome/', $ua);
                                        $isFirefox = preg_match('/Firefox/', $ua);
                                        $isSafari = preg_match('/Safari/', $ua) && !$isChrome;
                                    @endphp
                                    @if ($isMobile)
                                        <i class="bi bi-phone"></i>
                                    @elseif ($isChrome)
                                        <i class="bi bi-chrome"></i>
                                    @elseif ($isFirefox)
                                        <i class="bi bi-shield"></i>
                                    @else
                                        <i class="bi bi-laptop"></i>
                                    @endif
                                </span>
                                <div>
                                    <div class="device-name">
                                        {{ $session->user_agent ? \Illuminate\Support\Str::limit(implode(' ', array_slice(explode(' ', $session->user_agent), 0, 3)), 40) : 'Unknown device' }}
                                        @if ($session->id === $currentSessionId)
                                            <span class="badge bg-success ms-1">Current</span>
                                        @endif
                                    </div>
                                    <div class="device-meta">
                                        IP: {{ $session->ip_address }} &middot;
                                        {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Account Summary --}}
        <div class="card settings-card">
            <div class="card-header">
                <i class="bi bi-person-circle me-2"></i>Account
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Role</span>
                        <span class="fw-semibold">{{ $user->role->label() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Status</span>
                        <span class="fw-semibold text-capitalize">{{ $user->account_status }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Member Since</span>
                        <span class="fw-semibold">{{ $user->created_at->format('M Y') }}</span>
                    </div>
                </div>
                <hr>
                <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-pencil me-1"></i>Edit Profile
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
