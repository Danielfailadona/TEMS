@php
    $totalUsers = \App\Models\User::count();
    $pendingUsers = \App\Models\User::where('account_status', 'pending')->count();
    $activeToday = \App\Models\User::where('last_login_at', '>=', now()->subDay())->count();
    $suspendedUsers = \App\Models\User::where('account_status', 'suspended')->count();
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

<div class="btn-group flex-wrap gap-1 mb-3" id="status-pills" role="group">
  <button class="btn btn-sm btn-outline-primary {{ $activeStatusFilter === null || $activeStatusFilter === '' ? 'active' : '' }}" data-filter="all" type="button">All Users</button>
  <button class="btn btn-sm btn-outline-warning {{ $activeStatusFilter === 'pending' ? 'active' : '' }}" data-filter="pending" type="button">Pending</button>
  <button class="btn btn-sm btn-outline-success {{ $activeStatusFilter === 'approved' ? 'active' : '' }}" data-filter="approved" type="button">Approved</button>
  <button class="btn btn-sm btn-outline-danger {{ $activeStatusFilter === 'rejected' ? 'active' : '' }}" data-filter="rejected" type="button">Rejected</button>
  <button class="btn btn-sm btn-outline-secondary {{ $activeStatusFilter === 'suspended' ? 'active' : '' }}" data-filter="suspended" type="button">Suspended</button>
</div>

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
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableCard = document.getElementById('users-card');
    const form = document.getElementById('user-filter-form');
    const statusInput = document.getElementById('filter-status-input');

    function submitFilter() {
        const url = new URL(form.action, window.location.origin);
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

    document.querySelectorAll('#status-pills .btn').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('#status-pills .btn').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            statusInput.value = pill.dataset.filter === 'all' ? '' : pill.dataset.filter;
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
