<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\CitizenReport;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\AutoAssignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Operations > Task Assignment — admins triage citizen reports and
     * distribute the pending queue across available enforcers.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $enforcers = User::query()
            ->where('role', Role::Enforcer->value)
            ->where('account_status', 'approved')
            ->withCount(['assignedCitizenReports as open_reports_count' => function ($query) {
                $query->whereNotIn('status', [
                    ReportStatus::Resolved->value,
                    ReportStatus::Rejected->value,
                ]);
            }])
            ->with('enforcerLocation')
            ->orderBy('name')
            ->get();

        $unassigned = CitizenReport::with(['processedBy'])
            ->where('status', ReportStatus::Accepted->value)
            ->whereNull('assigned_to')
            ->latest()
            ->get();

        $assigned = CitizenReport::with(['assignedTo', 'processedBy'])
            ->whereNotNull('assigned_to')
            ->whereNotIn('status', [
                ReportStatus::Resolved->value,
                ReportStatus::Rejected->value,
            ])
            ->latest()
            ->get();

        return view('operations.task-assignment', compact('enforcers', 'unassigned', 'assigned'));
    }

    /**
     * Assign a specific report to a chosen enforcer.
     */
    public function assign(Request $request, CitizenReport $citizenReport): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $citizenReport->update(['assigned_to' => $validated['assigned_to']]);

        $this->notifyAssigned($citizenReport);

        return back()->with('success', "Report {$citizenReport->report_number} assigned.");
    }

    /**
     * Auto-assign a single report to a random eligible enforcer.
     */
    public function autoAssign(CitizenReport $citizenReport, AutoAssignService $autoAssign): RedirectResponse
    {
        $this->authorizeAdmin();

        $enforcer = $autoAssign->pickEnforcer();

        if (! $enforcer) {
            return back()->with('error', 'No eligible enforcer is currently available.');
        }

        $citizenReport->update([
            'assigned_to' => $enforcer->id,
            'status' => ReportStatus::Accepted->value,
            'processed_by' => $citizenReport->processed_by ?? auth()->id(),
            'processed_at' => $citizenReport->processed_at ?? now(),
        ]);

        $this->notifyAssigned($citizenReport);

        return back()->with('success', "Report {$citizenReport->report_number} auto-assigned to {$enforcer->name}.");
    }

    /**
     * Auto-assign every accepted-but-unassigned report.
     */
    public function autoAssignAll(AutoAssignService $autoAssign): RedirectResponse
    {
        $this->authorizeAdmin();

        $pending = CitizenReport::where('status', ReportStatus::Accepted->value)
            ->whereNull('assigned_to')
            ->get();

        $count = 0;

        foreach ($pending as $report) {
            $enforcer = $autoAssign->pickEnforcer();

            if (! $enforcer) {
                break;
            }

            $report->update(['assigned_to' => $enforcer->id]);
            $this->notifyAssigned($report);
            $count++;
        }

        return back()->with('success', $count > 0
            ? "{$count} report(s) auto-assigned."
            : 'No eligible enforcer is currently available.');
    }

    /**
     * Toggle an enforcer's manual availability flag.
     */
    public function toggleAvailability(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        abort_unless($user->isRole(Role::Enforcer), 404);

        $user->update(['is_available' => ! $user->is_available]);

        return back()->with('success', "{$user->name} marked " . ($user->is_available ? 'available' : 'unavailable') . '.');
    }

    protected function notifyAssigned(CitizenReport $report): void
    {
        if (! $report->assignedTo) {
            return;
        }

        SystemNotification::notify(
            $report->assignedTo,
            'citizen_report',
            'New Citizen Report Assigned',
            "Report {$report->report_number} for {$report->vehicle_plate} has been assigned to you.",
            ['citizen_report_id' => $report->id]
        );
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
