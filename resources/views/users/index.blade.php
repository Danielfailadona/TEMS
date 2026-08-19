@php
    $totalUsers = \App\Models\User::count();
    $pendingUsers = \App\Models\User::where('account_status', 'pending')->count();
    $approvedUsers = \App\Models\User::where('account_status', 'approved')->count();
    $rejectedUsers = \App\Models\User::where('account_status', 'rejected')->count();
    $suspendedUsers = \App\Models\User::where('account_status', 'suspended')->count();
    $activeToday = \App\Models\User::where('last_login_at', '>=', now()->subDay())->count();
@endphp

@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="mb-4 text-end">
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add User</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-people text-primary fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Users</div>
                    <div class="fs-4 fw-bold">{{ $totalUsers }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-hourglass-split text-warning fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Pending Approval</div>
                    <div class="fs-4 fw-bold">{{ $pendingUsers }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="bi bi-activity text-success fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Today</div>
                    <div class="fs-4 fw-bold">{{ $activeToday }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-secondary bg-opacity-10 p-3">
                    <i class="bi bi-lock text-secondary fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Suspended</div>
                    <div class="fs-4 fw-bold">{{ $suspendedUsers }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $activeStatusFilter = request('account_status');
@endphp

<nav class="user-mgmt-tabs mb-3" id="status-tabs">
    <a href="#" class="user-mgmt-tab {{ $activeStatusFilter === null || $activeStatusFilter === '' ? 'active' : '' }}" data-filter="all">
        <i class="bi bi-people-fill me-1"></i> All Users
        <span class="tab-count">{{ $totalUsers }}</span>
    </a>
    <a href="#" class="user-mgmt-tab tab-pending {{ $activeStatusFilter === 'pending' ? 'active' : '' }}" data-filter="pending">
        <i class="bi bi-hourglass-split me-1"></i> Pending
        @if ($pendingUsers > 0)
            <span class="tab-count tab-count-alert">{{ $pendingUsers }}</span>
        @else
            <span class="tab-count">{{ $pendingUsers }}</span>
        @endif
    </a>
    <a href="#" class="user-mgmt-tab tab-approved {{ $activeStatusFilter === 'approved' ? 'active' : '' }}" data-filter="approved">
        <i class="bi bi-check-circle-fill me-1"></i> Approved
        <span class="tab-count">{{ $approvedUsers }}</span>
    </a>
    <a href="#" class="user-mgmt-tab tab-rejected {{ $activeStatusFilter === 'rejected' ? 'active' : '' }}" data-filter="rejected">
        <i class="bi bi-x-circle-fill me-1"></i> Rejected
        <span class="tab-count">{{ $rejectedUsers }}</span>
    </a>
    <a href="#" class="user-mgmt-tab tab-suspended {{ $activeStatusFilter === 'suspended' ? 'active' : '' }}" data-filter="suspended">
        <i class="bi bi-lock-fill me-1"></i> Suspended
        <span class="tab-count">{{ $suspendedUsers }}</span>
    </a>
</nav>

<form class="row g-2 mb-3" method="GET" id="user-filter-form">
  <input type="hidden" name="account_status" id="filter-status-input" value="{{ request('account_status') }}">
  <div class="col-md-3">
    <input type="search" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
  </div>
  <div class="col-md-2">
    <select name="role" class="form-select">
      <option value="">All Roles</option>
      @foreach ($roles as $role)
        <option value="{{ $role->value }}" {{ request('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary me-1" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    <a href="{{ route('users.index') }}" class="btn btn-link">Clear</a>
  </div>
</form>

<div class="card border-0 shadow-sm" id="users-card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="users-table">
      <thead class="table-light">
        <tr>
          <th width="40"><input type="checkbox" id="select-all" class="form-check-input"></th>
          <th>User</th>
          <th>Role</th>
          <th>Status</th>
          <th>Active</th>
          <th>Last Login</th>
          <th width="80"></th>
        </tr>
      </thead>
      <tbody>
                @forelse ($users as $user)
                    @php
                        $initial = strtoupper(substr($user->name, 0, 1));
                        $colors = ['primary', 'success', 'warning', 'danger', 'info', 'secondary', 'dark'];
                        $colorIndex = crc32($user->email) % count($colors);
                        $avatarColor = $colors[$colorIndex];
                        $statusBadge = match ($user->account_status) {
                            'approved' => ['bg-success', 'Approved'],
                            'pending' => ['bg-warning text-dark', 'Pending'],
                            'rejected' => ['bg-danger', 'Rejected'],
                            'suspended' => ['bg-secondary', 'Suspended'],
                            default => ['bg-info', ucfirst($user->account_status ?? 'Unknown')],
                        };
                        $roleBadge = match ($user->role->value) {
                            'super_admin' => 'bg-dark',
                            'administrator' => 'bg-danger',
                            'enforcer' => 'bg-primary',
                            'clamping_officer' => 'bg-warning text-dark',
                            'cashier' => 'bg-success',
                            'front_desk' => 'bg-info',
                            'vehicle_owner' => 'bg-info text-dark',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <tr class="{{ $user->account_status === 'pending' ? 'table-warning' : ($user->account_status === 'rejected' ? 'table-danger' : '') }}">
                        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $user->id }}"></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-initial rounded-circle bg-{{ $avatarColor }} bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px">
                                    <span class="fw-bold text-{{ $avatarColor }} small">{{ $initial }}</span>
                                </span>
                                <div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    <div class="small text-muted">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge {{ $roleBadge }}">{{ $user->role->label() }}</span></td>
                        <td>
                            <span class="badge rounded-pill {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                        </td>
                        <td>
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" class="form-check-input toggle-active" data-user-id="{{ $user->id }}" {{ $user->is_active ? 'checked' : '' }}>
                            </div>
                        </td>
                        <td class="small text-muted">
                            @if ($user->last_login_at)
                                {{ $user->last_login_at->diffForHumans() }}
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if ($user->account_status === 'pending')
                                        <li>
                                            <form method="POST" action="{{ route('users.approve', $user) }}" class="d-inline">
                                                @csrf
                                                <button class="dropdown-item text-success"><i class="bi bi-check-lg"></i> Approve</button>
                                            </form>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $user->id }}"><i class="bi bi-x-lg"></i> Reject</button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                    @endif
                                    <li><a href="{{ route('users.edit', $user) }}" class="dropdown-item"><i class="bi bi-pencil"></i> Edit</a></li>
                                    @if ($user->account_status !== 'pending')
                                        <li>
                                            <form method="POST" action="{{ route('users.toggle-status', $user) }}" class="d-inline">
                                                @csrf
                                                <button class="dropdown-item {{ $user->account_status === 'suspended' ? 'text-success' : 'text-warning' }}">
                                                    <i class="bi {{ $user->account_status === 'suspended' ? 'bi-unlock' : 'bi-lock' }}"></i>
                                                    {{ $user->account_status === 'suspended' ? 'Unsuspend' : 'Suspend' }}
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                    <li><a href="{{ route('users.devices', $user) }}" class="dropdown-item"><i class="bi bi-phone"></i> Devices</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    @if ($user->account_status === 'pending')
                        <div class="modal fade user-reject-modal" id="rejectModal-{{ $user->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" action="{{ route('users.reject', $user) }}" class="modal-content">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Reject User: {{ $user->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label class="form-label">Reason for rejection</label>
                                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Explain why this registration was rejected..."></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Reject User</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-3">
            <small class="text-muted">Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }}</small>
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    @media (min-width: 992px) {
        .table-responsive { overflow: visible; }
    }

    .user-mgmt-tabs {
        display: flex;
        gap: 0;
        border-bottom: 2px solid #e2e8f0;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .user-mgmt-tabs::-webkit-scrollbar { display: none; }

    .user-mgmt-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.7rem 1.1rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        white-space: nowrap;
        transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .user-mgmt-tab:hover {
        color: #1e293b;
        background: rgba(0, 0, 0, 0.02);
    }
    .user-mgmt-tab.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }

    .tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.5rem;
        height: 1.3rem;
        padding: 0 0.4rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        background: #f1f5f9;
        color: #64748b;
    }
    .user-mgmt-tab.active .tab-count {
        background: #dbeafe;
        color: #2563eb;
    }
    .tab-count-alert {
        background: #fef3c7;
        color: #d97706;
    }
    .user-mgmt-tab.active .tab-count-alert {
        background: #fef3c7;
        color: #d97706;
    }

    .tab-pending.active { color: #d97706; border-bottom-color: #f59e0b; }
    .tab-approved.active { color: #16a34a; border-bottom-color: #22c55e; }
    .tab-rejected.active { color: #dc2626; border-bottom-color: #ef4444; }
    .tab-suspended.active { color: #64748b; border-bottom-color: #94a3b8; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableCard = document.getElementById('users-card');
    const form = document.getElementById('user-filter-form');
    const statusInput = document.getElementById('filter-status-input');

    function submitFilter() {
        const url = new URL(form.action.split('?')[0], window.location.origin);
        new FormData(form).forEach((v, k) => {
            if (v !== '') url.searchParams.set(k, v);
        });
        history.replaceState({}, '', url.toString());
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newCard = doc.getElementById('users-card');
                if (newCard) tableCard.innerHTML = newCard.innerHTML;
                document.querySelectorAll('.user-reject-modal').forEach(m => m.remove());
                doc.querySelectorAll('.user-reject-modal').forEach(m => document.body.appendChild(m));
            })
            .catch(() => { window.location.href = url.toString(); });
    }

    tableCard.addEventListener('change', function (e) {
        if (e.target.id === 'select-all') {
            document.querySelectorAll('#users-table .row-checkbox').forEach(cb => cb.checked = e.target.checked);
            return;
        }
        const cb = e.target.closest('.toggle-active');
        if (!cb) return;
        const userId = cb.dataset.userId;
        const isActive = cb.checked;
        fetch(`/users/${userId}/toggle-active`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ is_active: isActive })
        }).catch(() => { cb.checked = !isActive; });
    });

    document.querySelectorAll('#status-tabs .user-mgmt-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('#status-tabs .user-mgmt-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            statusInput.value = tab.dataset.filter === 'all' ? '' : tab.dataset.filter;
            submitFilter();
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitFilter();
    });
});
</script>
@endpush
