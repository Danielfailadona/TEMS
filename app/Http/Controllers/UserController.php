<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Appeal;
use App\Models\Archive;
use App\Models\CitizenReport;
use App\Models\ClampingRecord;
use App\Models\DeviceManager;
use App\Models\ImpoundingRecord;
use App\Models\SavedSearch;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\VehicleRelease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $users = User::query()
            ->when($request->search, fn ($q, $search) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->when($request->account_status, fn ($q, $s) => $q->where('account_status', $s))
            ->orderBy(\Illuminate\Support\Facades\DB::raw("CASE account_status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 WHEN 'suspended' THEN 3 ELSE 4 END"))
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->withQueryString();

        $totalUsers = User::count();
        $pendingUsers = User::where('account_status', 'pending')->count();
        $approvedUsers = User::where('account_status', 'approved')->count();
        $rejectedUsers = User::where('account_status', 'rejected')->count();
        $suspendedUsers = User::where('account_status', 'suspended')->count();
        $activeToday = User::where('last_login_at', '>=', now()->subDay())->count();

$pending = $pendingUsers;
        $pendingState = $pending === 0 ? 'green' : ($pending <= 2 ? 'amber' : 'red');

        // Compute status label and filter URLs for view
        $qs = $request->only(['search', 'account_status', 'role']);
        $qs = array_filter($qs, fn ($v) => $v !== null && $v !== '');

        $statusValue = $request->account_status;
        $statusValues = array_column(AccountStatus::cases(), 'value');
        $statusLabel = 'Filters';
        if ($statusValue && in_array($statusValue, $statusValues, true)) {
            $statusLabel = AccountStatus::from($statusValue)->label();
        }

        $urlStatusAll = route('users.index', array_filter(array_diff_key($qs, ['account_status' => ''])));
        $urlStatusPending = route('users.index', array_merge($qs, ['account_status' => 'pending']));
        $urlStatusApproved = route('users.index', array_merge($qs, ['account_status' => 'approved']));
        $urlStatusRejected = route('users.index', array_merge($qs, ['account_status' => 'rejected']));
        $urlStatusSuspended = route('users.index', array_merge($qs, ['account_status' => 'suspended']));
        $urlClearFilters = route('users.index', array_filter(array_diff_key($qs, array_flip(['role', 'account_status']))));

        // Active filter chips
        $activeFilters = [];
        if (($qs['role'] ?? null) !== null) {
            $activeFilters[] = [
                'label' => Role::tryFrom($qs['role'])?->label() ?? $qs['role'],
                'href' => route('users.index', array_filter(array_diff_key($qs, ['role' => ''])))
            ];
        }
        if (($qs['account_status'] ?? null) !== null) {
            $statusLabels = [
                'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended',
            ];
            $activeFilters[] = [
                'label' => $statusLabels[$qs['account_status']] ?? \Illuminate\Support\Str::headline($qs['account_status']),
                'href' => route('users.index', array_filter(array_diff_key($qs, ['account_status' => ''])))
            ];
        }
        if (($qs['search'] ?? null) !== null) {
            $activeFilters[] = [
                'label' => 'Search: "' . $qs['search'] . '"',
                'href' => route('users.index', array_filter(array_diff_key($qs, ['search' => ''])))
            ];
        }

        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('users.partials.users-card', ['users' => $users, 'roles' => Role::cases()]);
        }

        return view('users.index', [
            'users' => $users,
            'roles' => Role::cases(),
            'totalUsers' => $totalUsers,
            'pendingUsers' => $pendingUsers,
            'pending' => $pendingUsers,
            'approvedUsers' => $approvedUsers,
            'rejectedUsers' => $rejectedUsers,
            'suspendedUsers' => $suspendedUsers,
            'activeToday' => $activeToday,
            'pendingState' => $pendingState,
            'statusLabel' => $statusLabel,
            'urlStatusAll' => $urlStatusAll,
            'urlStatusPending' => $urlStatusPending,
            'urlStatusApproved' => $urlStatusApproved,
            'urlStatusRejected' => $urlStatusRejected,
            'urlStatusSuspended' => $urlStatusSuspended,
            'urlClearFilters' => $urlClearFilters,
            'activeFilters' => $activeFilters,
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('users.create', ['roles' => Role::cases()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorizeAdmin();

        User::create($request->validated());

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorizeAdmin();

        return view('users.edit', ['user' => $user, 'roles' => Role::cases()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Any enforcement/financial history blocks deletion — suspend instead.
        $blockers = [];
        if ($user->issuedCitations()->exists()) {
            $blockers[] = 'issued citations';
        }
        if ($user->processedPayments()->exists()) {
            $blockers[] = 'processed payments';
        }
        if (ClampingRecord::where('clamped_by', $user->id)->exists()
            || ImpoundingRecord::where('impounded_by', $user->id)->exists()) {
            $blockers[] = 'clamping/impounding records';
        }
        if (VehicleRelease::where('released_by', $user->id)->exists()) {
            $blockers[] = 'vehicle releases';
        }
        if (Appeal::where('submitted_by', $user->id)->exists()
            || Appeal::where('reviewed_by', $user->id)->exists()) {
            $blockers[] = 'appeals';
        }
        if ($user->assignedCitizenReports()->exists()
            || CitizenReport::where('processed_by', $user->id)->exists()) {
            $blockers[] = 'citizen reports';
        }

        if ($blockers !== []) {
            return back()->with('error', "Cannot delete {$user->name}: the account is linked to ".implode(', ', $blockers).'. Suspend the account instead.');
        }

        $name = $user->name;

        DB::transaction(function () use ($user) {
            Archive::log($user, 'User deleted', auth()->id(), $user->toArray());

            $user->teams()->detach();
            $user->enforcerSchedules()->delete();
            $user->notifications()->delete();
            $user->devices()->delete();
            SavedSearch::where('user_id', $user->id)->delete();
            $user->delete();
        });

        activity()->log("Deleted user {$name}");

        return redirect()->route('users.index')->with('success', "User \"{$name}\" deleted.");
    }

    public function approve(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $user->update(['account_status' => 'approved']);

        SystemNotification::notify(
            $user,
            'account_approved',
            'Account Approved',
            'Your account has been approved. You can now access the system.',
        );

        activity()->performedOn($user)->log("Approved user {$user->name}");

        return back()->with('success', "User {$user->name} approved.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $user->update([
            'account_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        activity()->performedOn($user)->log("Rejected user {$user->name}");

        return back()->with('success', "User {$user->name} rejected.");
    }

    public function toggleActive(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();

        $isActive = $request->boolean('is_active');
        $user->update(['is_active' => $isActive]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_active' => $isActive]);
        }

        return back()->with('success', "User {$user->name} " . ($isActive ? 'activated' : 'deactivated') . ".");
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $newStatus = $user->account_status === 'suspended' ? 'approved' : 'suspended';
        $user->update(['account_status' => $newStatus]);

        activity()->performedOn($user)->log("Changed user {$user->name} status to {$newStatus}");

        return back()->with('success', "User {$user->name} {$newStatus}.");
    }

    public function devices(User $user): View
    {
        $this->authorizeAdmin();

        $devices = $user->devices()->latest('last_activity')->get();

        return view('users.devices', compact('user', 'devices'));
    }

    public function forceLogout(Request $request, DeviceManager $device): RedirectResponse
    {
        $this->authorizeAdmin();

        $device->update(['is_active' => false]);

        // Invalidate the session associated with this device
        if ($device->session_id) {
            \DB::table('sessions')->where('id', $device->session_id)->delete();
        }

        activity()->performedOn($device->user)->log("Force logged out device of {$device->user->name}");

        return back()->with('success', 'Device session terminated.');
    }

    public function batchAction(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'action' => 'required|in:approve,reject,suspend,unsuspend',
            'user_ids' => 'required|string',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $userIds = array_filter(explode(',', $validated['user_ids']));
        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'No valid users selected.');
        }

        $action = $validated['action'];
        $affected = 0;

        DB::transaction(function () use ($users, $action, $validated, &$affected) {
            foreach ($users as $user) {
                $currentStatus = $user->account_status;
                $shouldProcess = false;
                $newStatus = $currentStatus;
                $reason = null;

                switch ($action) {
                    case 'approve':
                        if ($currentStatus === 'pending') {
                            $shouldProcess = true;
                            $newStatus = 'approved';
                        }
                        break;
                    case 'reject':
                        if ($currentStatus === 'pending') {
                            $shouldProcess = true;
                            $newStatus = 'rejected';
                            $reason = $validated['rejection_reason'] ?? 'No reason provided';
                        }
                        break;
                    case 'suspend':
                        if (in_array($currentStatus, ['approved', 'pending'])) {
                            $shouldProcess = true;
                            $newStatus = 'suspended';
                        }
                        break;
                    case 'unsuspend':
                        if ($currentStatus === 'suspended') {
                            $shouldProcess = true;
                            $newStatus = 'approved';
                        }
                        break;
                }

                if ($shouldProcess) {
                    $user->update(['account_status' => $newStatus]);
                    
                    if ($action === 'reject' && $reason) {
                        $user->update(['rejection_reason' => $reason]);
                    }

                    activity()->performedOn($user)->log("Batch {$action} user {$user->name} (status: {$currentStatus} → {$newStatus})");
                    $affected++;
                }
            }
        });

        $actionLabel = match ($action) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'suspend' => 'suspended',
            'unsuspend' => 'unsuspended',
            default => $action,
        };

        return back()->with('success', "{$affected} user(s) {$actionLabel} successfully.");
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
