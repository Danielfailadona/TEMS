<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Http\Requests\StoreCitationRequest;
use App\Models\Archive;
use App\Models\Citation;
use App\Models\CitationEvidence;
use App\Models\CitizenReport;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\ViolationType;
use App\Services\AutoAssignService;
use App\Services\CitationNumberService;
use App\Services\SupabaseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CitizenReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CitizenReport::class);

        $user = auth()->user();
        $isRestricted = $user->isRole(Role::Enforcer);

        $query = CitizenReport::with(['processedBy', 'assignedTo', 'clampingRecord']);

        if ($isRestricted) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('status', ReportStatus::Pending->value);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_plate', 'like', "%{$search}%")
                  ->orWhere('report_number', 'like', "%{$search}%")
                  ->orWhere('requester_name', 'like', "%{$search}%")
                  ->orWhere('location_address', 'like', "%{$search}%")
                  ->orWhere('requester_phone', 'like', "%{$search}%");
            });
        }

        $reports = $query->latest()->paginate(5)->withQueryString();

        $statsQuery = CitizenReport::query();
        if ($isRestricted) {
            $statsQuery->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('status', ReportStatus::Pending->value);
            });
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', ReportStatus::Pending->value)->count(),
            'accepted' => (clone $statsQuery)->where('status', ReportStatus::Accepted->value)->count(),
            'rejected' => (clone $statsQuery)->where('status', ReportStatus::Rejected->value)->count(),
            'resolved' => (clone $statsQuery)->where('status', ReportStatus::Resolved->value)->count(),
        ];

        $pending = $stats['pending'];
        $stats['pending_state'] = $pending === 0 ? 'green' : ($pending <= 2 ? 'amber' : 'red');

        return view('citizen-reports.index', compact('reports', 'stats'));
    }

    public function show(CitizenReport $citizenReport): View
    {
        $this->authorize('view', $citizenReport);

        $citizenReport->load(['processedBy', 'assignedTo', 'citation.violationType', 'clampingRecord.citation', 'clampingRecord.officer']);

        $enforcers = User::where('role', Role::Enforcer->value)
            ->where('account_status', 'approved')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $violationTypes = ViolationType::where('is_active', true)->orderBy('name')->get();

        return view('citizen-reports.show', [
            'request' => $citizenReport,
            'enforcers' => $enforcers,
            'violationTypes' => $violationTypes,
        ]);
    }

    public function accept(Request $request, CitizenReport $citizenReport, AutoAssignService $autoAssign): RedirectResponse
    {
        $this->authorize('update', $citizenReport);

        $validated = $request->validate([
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $actor = auth()->user();

        // An enforcer accepting from the pool assigns the report to themselves;
        // admins choose an enforcer (or fall back to auto-assignment).
        if ($actor->isRole(Role::Enforcer)) {
            if (! $actor->canBeAssigned()) {
                return back()->withErrors([
                    'assigned_to' => 'You are currently marked unavailable. Set yourself available first before accepting reports.',
                ]);
            }
            $assignedTo = $actor->id;
        } elseif ($validated['assigned_to'] ?? null) {
            // Admin explicitly chose an enforcer — respect availability.
            $enforcer = User::findOrFail($validated['assigned_to']);

            if (! $enforcer->isRole(Role::Enforcer)) {
                return back()->withErrors(['assigned_to' => 'Selected user is not an enforcer.']);
            }

            if (! $enforcer->canBeAssigned()) {
                return back()->withErrors(['assigned_to' => "{$enforcer->name} is currently unavailable and cannot be assigned reports."]);
            }

            $assignedTo = $enforcer->id;
        } elseif ($citizenReport->assigned_to) {
            // Keep an existing assignment as-is.
            $assignedTo = $citizenReport->assigned_to;
        } else {
            $assignedTo = $autoAssign->pickEnforcer()?->id;
        }

        $citizenReport->update([
            'status' => ReportStatus::Accepted->value,
            'processed_by' => $actor->id,
            'processed_at' => now(),
            'assigned_to' => $assignedTo,
        ]);

        if ($citizenReport->assignedTo && $citizenReport->assigned_to !== $actor->id) {
            SystemNotification::notify(
                $citizenReport->assignedTo,
                'citizen_report',
                'New Citizen Report Assigned',
                "Report {$citizenReport->report_number} for {$citizenReport->vehicle_plate} has been assigned to you.",
                ['citizen_report_id' => $citizenReport->id]
            );
        }

        // No enforcer was available: the report stays accepted but unassigned.
        if (! $assignedTo) {
            foreach (User::whereIn('role', [Role::SuperAdmin, Role::Administrator])->get() as $admin) {
                SystemNotification::notify(
                    $admin,
                    'citizen_report',
                    'Report Awaiting Assignment',
                    "Report {$citizenReport->report_number} for {$citizenReport->vehicle_plate} was accepted but no enforcer is currently available to take it.",
                    ['citizen_report_id' => $citizenReport->id]
                );
            }

            return redirect()->route('citizen-reports.show', $citizenReport)
                ->with('warning', 'Report accepted, but no enforcer is currently available — it remains unassigned. All administrators have been notified.');
        }

        return redirect()->route('citizen-reports.show', $citizenReport)
            ->with('success', 'Citizen report accepted and assigned.');
    }

    /**
     * Enforcer claims a pending report from the shared pool (assigns to self).
     */
    public function claim(CitizenReport $citizenReport): RedirectResponse
    {
        $this->authorize('claim', $citizenReport);

        $citizenReport->update([
            'status' => ReportStatus::Accepted->value,
            'assigned_to' => auth()->id(),
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        // Notify the reporter if they have a user account
        if ($citizenReport->requester_email) {
            $reporter = User::where('email', $citizenReport->requester_email)->first();
            if ($reporter) {
                SystemNotification::notify(
                    $reporter,
                    'citizen_report',
                    'Report Assigned',
                    "Your citizen report {$citizenReport->report_number} for vehicle {$citizenReport->vehicle_plate} has been assigned to an enforcer.",
                    ['citizen_report_id' => $citizenReport->id]
                );
            }
        }

        return redirect()->route('citizen-reports.show', $citizenReport)
            ->with('success', 'Report claimed. It is now assigned to you.');
    }

    public function approve(Request $request, CitizenReport $citizenReport, AutoAssignService $autoAssign): RedirectResponse
    {
        return $this->accept($request, $citizenReport, $autoAssign);
    }

    public function reject(Request $request, CitizenReport $citizenReport): RedirectResponse
    {
        $this->authorize('update', $citizenReport);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $citizenReport->update([
            'status' => ReportStatus::Rejected->value,
            'rejection_reason' => $validated['rejection_reason'],
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return redirect()->route('citizen-reports.show', $citizenReport)
            ->with('success', 'Citizen report rejected.');
    }

    public function assign(Request $request, CitizenReport $citizenReport): RedirectResponse
    {
        $this->authorize('update', $citizenReport);

        $validated = $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $enforcer = User::findOrFail($validated['assigned_to']);

        if (! $enforcer->isRole(Role::Enforcer)) {
            return back()->withErrors(['assigned_to' => 'Selected user is not an enforcer.']);
        }

        if (! $enforcer->canBeAssigned()) {
            return back()->withErrors(['assigned_to' => "{$enforcer->name} is currently unavailable and cannot be assigned reports."]);
        }

        $citizenReport->update([
            'assigned_to' => $validated['assigned_to'],
        ]);

        return redirect()->route('citizen-reports.show', $citizenReport)
            ->with('success', 'Report assigned to enforcer.');
    }

    public function resolve(Request $request, CitizenReport $citizenReport): RedirectResponse
    {
        $this->authorize('update', $citizenReport);

        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($citizenReport, $validated) {
            $citizenReport->update([
                'status' => ReportStatus::Resolved->value,
                'resolution_type' => 'resolved_with_notes',
                'resolution_notes' => $validated['resolution_notes'] ?? null,
                'resolved_at' => now(),
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            Archive::log($citizenReport->refresh(), 'Citizen report resolved', auth()->id());
        });

        $admins = User::whereIn('role', [Role::SuperAdmin, Role::Administrator])->get();
        foreach ($admins as $admin) {
            SystemNotification::notify(
                $admin,
                'citizen_report',
                'Citizen Report Resolved',
                "Report {$citizenReport->report_number} for {$citizenReport->vehicle_plate} has been resolved.",
                ['citizen_report_id' => $citizenReport->id]
            );
        }

        // Notify the reporter if they have a user account
        if ($citizenReport->requester_email) {
            $reporter = User::where('email', $citizenReport->requester_email)->first();
            if ($reporter) {
                SystemNotification::notify(
                    $reporter,
                    'citizen_report',
                    'Report Resolved',
                    "Your citizen report {$citizenReport->report_number} for vehicle {$citizenReport->vehicle_plate} has been resolved. ".($citizenReport->resolution_notes ? 'Notes: '.$citizenReport->resolution_notes : ''),
                    ['citizen_report_id' => $citizenReport->id]
                );
            }
        }

        return redirect()->route('citizen-reports.show', $citizenReport)
            ->with('success', 'Citizen report marked as resolved.');
    }

    /**
     * Enforcer issues a citation derived from an accepted report. The citizen
     * report's vehicle/location data is fetched and pre-fills the citation —
     * nothing is duplicated beyond the citation's own record of truth.
     */
    public function issueCitation(StoreCitationRequest $request, CitizenReport $citizenReport, CitationNumberService $numberService): RedirectResponse
    {
        $this->authorize('update', $citizenReport);

        if ($citizenReport->citation_id) {
            return back()->with('error', 'A citation has already been issued for this report.');
        }

        $violationType = ViolationType::findOrFail($request->violation_type_id);
        $isCustom = $violationType->isCustom();

        $citation = DB::transaction(function () use ($request, $citizenReport, $numberService, $violationType, $isCustom) {
            $citation = Citation::create([
                'citation_number' => $numberService->generate(),
                'violation_type_id' => $violationType->id,
                'custom_violation_name' => $isCustom ? $request->custom_violation_name : null,
                'vehicle_plate' => $request->vehicle_plate ?: $citizenReport->vehicle_plate,
                'vehicle_make' => $request->vehicle_make ?: $citizenReport->vehicle_make,
                'vehicle_model' => $request->vehicle_model ?: $citizenReport->vehicle_model,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_color' => $request->vehicle_color ?: $citizenReport->vehicle_color,
                'driver_name' => $request->driver_name,
                'driver_license' => $request->driver_license,
                'issued_by' => auth()->id(),
                'penalty_amount' => $isCustom && $request->filled('penalty_amount')
                    ? (float) $request->penalty_amount
                    : $violationType->penalty_amount,
                'status' => CitationStatus::Issued,
                'location' => $request->location ?: $citizenReport->location_address,
                'latitude' => $request->latitude ?? $citizenReport->latitude,
                'longitude' => $request->longitude ?? $citizenReport->longitude,
                'notes' => $request->notes,
                'issued_at' => now(),
                'due_date' => $numberService->dueDate(),
            ]);

            $evidencePaths = [];
            if ($request->hasFile('evidence')) {
                foreach ($request->file('evidence') as $file) {
                    $path = SupabaseStorage::put('citations/'.$citation->id.'/'.$file->hashName(), $file);
                    CitationEvidence::create([
                        'citation_id' => $citation->id,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                    $evidencePaths[] = $path;
                }
            }

            // Clamping/impounding records are NOT auto-created here. Citations with
            // clampable/impoundable violations become eligible on the Clamping and
            // Impounding pages, where an Administrator makes the decision manually.

            $citizenReport->update([
                'citation_id' => $citation->id,
                'status' => ReportStatus::Resolved->value,
                'resolution_type' => 'citation_issued',
                'resolution_notes' => $request->notes ?: 'Citation issued from citizen report.',
                'resolved_at' => now(),
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            Archive::log($citation, 'Citation issued (from citizen report)', auth()->id());
            Archive::log($citizenReport->refresh(), 'Citizen report resolved with citation', auth()->id());

            return $citation;
        });

        return redirect()->route('citations.handoff', $citation)
            ->with('success', 'Citation issued from report. Show the QR code to the violator.');
    }

    public function destroy(CitizenReport $citizenReport): RedirectResponse
    {
        $this->authorize('delete', $citizenReport);

        if ($citizenReport->citation_id) {
            return back()->with('error', 'Cannot delete report '.$citizenReport->report_number.' because a citation was already issued from it. Delete the citation first.');
        }

        Archive::log($citizenReport, 'Citizen report deleted', auth()->id(), $citizenReport->toArray());

        $citizenReport->delete();

        return redirect()->route('citizen-reports.index')
            ->with('success', 'Citizen report '.$citizenReport->report_number.' has been deleted and archived.');
    }
}
