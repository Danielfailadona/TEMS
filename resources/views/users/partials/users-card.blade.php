@include('users.partials.batch-toolbar')

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
                        {{-- Reject modal rendered outside table to prevent stagger on AJAX refresh --}}
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