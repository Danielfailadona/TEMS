@extends('layouts.app')

@section('title', 'Devices - '.$user->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Active Devices</h1>
        <p class="text-muted mb-0">{{ $user->name }} &mdash; {{ $user->email }}</p>
    </div>
    <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to User
    </a>
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Type</th>
                    <th>IP Address</th>
                    <th>Last Activity</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($devices as $device)
                    <tr>
                        <td class="small">{{ \Illuminate\Support\Str::limit($device->device_name ?? 'Unknown', 40) }}</td>
                        <td>{{ $device->device_type ?? '—' }}</td>
                        <td><code>{{ $device->ip_address }}</code></td>
                        <td>{{ $device->last_activity?->diffForHumans() ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $device->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $device->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if ($device->is_active)
                                <form method="POST" action="{{ route('devices.force-logout', $device) }}" class="d-inline" onsubmit="return confirm('Force logout this device?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Force Logout">
                                        <i class="bi bi-box-arrow-right"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No devices found for this user.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
