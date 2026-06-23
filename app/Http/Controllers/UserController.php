<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\DeviceManager;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', ['users' => $users, 'roles' => Role::cases()]);
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

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
