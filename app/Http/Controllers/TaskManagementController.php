<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\EnforcerSchedule;
use App\Models\User;
use App\Services\AutoAssignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:super_admin,administrator');
    }

    /**
     * Display the Task Management dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $query = User::query()
            ->where('role', Role::Enforcer->value)
            ->where('account_status', 'approved')
            ->withCount(['assignedCitizenReports as open_reports_count' => function ($q) {
                $q->whereNotIn('status', [
                    \App\Enums\ReportStatus::Resolved->value,
                    \App\Enums\ReportStatus::Rejected->value,
                ]);
            }])
            ->with(['enforcerLocation', 'teams', 'enforcerSchedules' => function ($q) {
                $q->orderBy('start_time');
            }])
            ->orderBy('name');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('badge_id', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('availability')) {
            $availability = $request->availability;
            if ($availability === 'schedule_assignable') {
                $query->scheduleAssignable();
            } elseif ($availability === 'available') {
                $query->where('is_available', true)->where('is_online', true);
            } elseif ($availability === 'unavailable') {
                $query->where(function ($q) {
                    $q->where('is_available', false)->orWhere('is_online', false);
                });
            } elseif ($availability === 'force_available') {
                $query->where('availability_override', 'force_available');
            } elseif ($availability === 'force_unavailable') {
                $query->where('availability_override', 'force_unavailable');
            }
        }

        if ($request->filled('zone')) {
            $query->whereHas('teams', function ($q) use ($request) {
                $q->where('teams.id', $request->zone);
            });
        }

        if ($request->filled('override')) {
            $query->where('availability_override', $request->override);
        }

        $enforcers = $query->paginate(5)->withQueryString()->through(function ($enforcer) {
            $dayOfWeek = now()->dayOfWeek;
            $time = now()->format('H:i:s');
            
            // Find today's schedule from the new structure
            $todaysSchedule = $enforcer->enforcerSchedules
                ->filter(function ($schedule) use ($dayOfWeek, $time) {
                    return in_array($dayOfWeek, $schedule->days ?? []) 
                        && ($schedule->is_active_days[$dayOfWeek] ?? false)
                        && $schedule->start_time <= $time 
                        && $schedule->end_time > $time;
                })
                ->first();

            $isWithinSchedule = $enforcer->isWithinSchedule();
            $isScheduleAssignable = $enforcer->isScheduleAssignable();

            return [
                'enforcer' => $enforcer,
                'todays_schedule' => $todaysSchedule,
                'is_within_schedule' => $isWithinSchedule,
                'is_schedule_assignable' => $isScheduleAssignable,
                'override_status' => $enforcer->availability_override,
            ];
        });

        // Stats
        $forceAvailable = User::where('role', Role::Enforcer->value)
            ->where('account_status', 'approved')
            ->where('availability_override', 'force_available')
            ->count();

        $forceUnavailable = User::where('role', Role::Enforcer->value)
            ->where('account_status', 'approved')
            ->where('availability_override', 'force_unavailable')
            ->count();

        $stats = [
            'total_enforcers' => User::where('role', Role::Enforcer->value)
                ->where('account_status', 'approved')
                ->count(),
            'schedule_assignable' => User::where('role', Role::Enforcer->value)
                ->where('account_status', 'approved')
                ->scheduleAssignable()
                ->count(),
            'force_available' => $forceAvailable,
            'force_unavailable' => $forceUnavailable,
            'force_available_state' => $forceAvailable === 0 ? 'red' : ($forceAvailable <= 2 ? 'amber' : 'green'),
            'force_unavailable_state' => $forceUnavailable === 0 ? 'green' : ($forceUnavailable <= 2 ? 'amber' : 'red'),
        ];

        $zones = \App\Models\Team::whereHas('members', function ($q) {
            $q->where('role', Role::Enforcer->value);
        })->orderBy('name')->get();

        return view('operations.task-management', compact('enforcers', 'stats', 'zones'));
    }

    /**
     * Show enforcer detail modal content.
     */
    public function show(User $enforcer): View
    {
        $this->authorizeAdmin();

        abort_unless($enforcer->isRole(Role::Enforcer), 404);

        $enforcer->load(['enforcerLocation', 'teams', 'enforcerSchedules' => function ($q) {
            $q->orderBy('start_time');
        }]);

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return view('operations.partials.enforcer-detail', compact('enforcer', 'dayNames'));
    }

/**
     * Update an enforcer's schedule.
     */
    public function updateSchedule(Request $request, User $enforcer): RedirectResponse
    {
        $this->authorizeAdmin();

        abort_unless($enforcer->isRole(Role::Enforcer), 404);

        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'days' => 'required|array|min:1',
            'days.*' => 'integer|between:0,6',
            'is_active_days' => 'array',
            'is_active_days.*' => 'boolean',
        ]);

        // Treat submitted days as the source of truth; keep is_active_days in sync
        $days = array_values(array_unique(array_map('intval', $validated['days'])));
        sort($days);
        $isActiveDays = [];
        foreach ($days as $day) {
            $isActiveDays[$day] = true;
        }

        // Delete existing schedules
        $enforcer->enforcerSchedules()->delete();

        // Create new single schedule with days array and per-day active status
        EnforcerSchedule::create([
            'enforcer_id' => $enforcer->id,
            'days' => $days,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_active_days' => $isActiveDays,
        ]);

        return back()->with('success', 'Schedule updated successfully.');
    }

    /**
     * Update enforcer's availability override.
     */
    public function updateOverride(Request $request, User $enforcer): RedirectResponse
    {
        $this->authorizeAdmin();

        abort_unless($enforcer->isRole(Role::Enforcer), 404);

        $validated = $request->validate([
            'availability_override' => 'required|in:auto,force_available,force_unavailable',
        ]);

        $enforcer->update([
            'availability_override' => $validated['availability_override'],
        ]);

        return back()->with('success', 'Availability override updated successfully.');
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
