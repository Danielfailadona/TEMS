@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Notifications</h1>
    @if ($notifications->where('is_read', false)->count() > 0)
        <form method="POST" action="{{ route('notifications.markAllRead') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-check-all me-1"></i>Mark All as Read
            </button>
        </form>
    @endif
</div>

<div class="card stat-card">
    <div class="list-group list-group-flush">
        @forelse ($notifications as $notification)
            <div class="list-group-item py-3 {{ $notification->is_read ? '' : 'bg-light' }}">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="d-flex gap-3">
                        <div>
                            @php
                                $icon = match($notification->type) {
                                    'payment_received' => 'bi-cash-stack text-success',
                                    'citation_issued' => 'bi-receipt text-primary',
                                    'appeal_status' => 'bi-chat-square-text text-warning',
                                    'clamping_action' => 'bi-lock text-danger',
                                    'release' => 'bi-unlock text-secondary',
                                    'account_approved' => 'bi-person-check text-success',
                                    default => 'bi-bell text-muted',
                                };
                            @endphp
                            <i class="bi {{ $icon }} fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $notification->title }}</div>
                            <div class="text-muted small">{{ $notification->message }}</div>
                            @if ($notification->data)
                                <div class="mt-1">
                                    @if (isset($notification->data['citation_number']))
                                        <span class="badge bg-primary">{{ $notification->data['citation_number'] }}</span>
                                    @endif
                                    @if (isset($notification->data['payment_id']))
                                        <span class="badge bg-success">Payment #{{ $notification->data['payment_id'] }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="text-end text-nowrap">
                        <small class="text-muted d-block">{{ $notification->created_at->diffForHumans() }}</small>
                        @if (!$notification->is_read)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('notifications.destroy', $notification) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                <p class="mb-0">No notifications yet.</p>
            </div>
        @endforelse
    </div>
    @if ($notifications->hasPages())
        <div class="card-footer bg-white">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
