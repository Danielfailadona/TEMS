<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Archive;
use App\Models\ClampingRequest;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClampingRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClampingRequest::class);

        $query = ClampingRequest::with(['processedBy', 'assignedTo', 'clampingRecord']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_plate', 'like', "%{$search}%")
                  ->orWhere('requester_name', 'like', "%{$search}%")
                  ->orWhere('location_address', 'like', "%{$search}%")
                  ->orWhere('requester_phone', 'like', "%{$search}%");
            });
        }

        $requests = $query->latest()->paginate(10);

        $stats = [
            'total' => ClampingRequest::count(),
            'pending' => ClampingRequest::where('status', 'pending')->count(),
            'approved' => ClampingRequest::where('status', 'approved')->count(),
            'rejected' => ClampingRequest::where('status', 'rejected')->count(),
            'resolved' => ClampingRequest::where('status', 'resolved')->count(),
        ];

        return view('clamping-requests.index', compact('requests', 'stats'));
    }

    public function show(ClampingRequest $clampingRequest): View
    {
        $this->authorize('view', $clampingRequest);

        $clampingRequest->load(['processedBy', 'assignedTo', 'clampingRecord.citation', 'clampingRecord.officer']);

        $enforcers = User::whereIn('role', [Role::Enforcer, Role::ClampingOfficer])
            ->orderBy('name')
            ->get();

        return view('clamping-requests.show', [
            'request' => $clampingRequest,
            'enforcers' => $enforcers,
        ]);
    }

    public function approve(Request $request, ClampingRequest $clampingRequest): RedirectResponse
    {
        $this->authorize('update', $clampingRequest);

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $clampingRequest->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'assigned_to' => $validated['assigned_to'] ?? $clampingRequest->assigned_to,
        ]);

        if ($clampingRequest->assignedTo) {
            SystemNotification::notify(
                $clampingRequest->assignedTo,
                'clamping_action',
                'New Clamping Task Assigned',
                "Clamping request #{$clampingRequest->id} for {$clampingRequest->vehicle_plate} has been assigned to you.",
                ['clamping_request_id' => $clampingRequest->id]
            );
        }

        return redirect()->route('clamping-requests.show', $clampingRequest)
            ->with('success', 'Clamping request approved and assigned.');
    }

    public function reject(Request $request, ClampingRequest $clampingRequest): RedirectResponse
    {
        $this->authorize('update', $clampingRequest);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $clampingRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return redirect()->route('clamping-requests.show', $clampingRequest)
            ->with('success', 'Clamping request rejected.');
    }

    public function assign(Request $request, ClampingRequest $clampingRequest): RedirectResponse
    {
        $this->authorize('update', $clampingRequest);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $clampingRequest->update([
            'assigned_to' => $validated['assigned_to'],
        ]);

        return redirect()->route('clamping-requests.show', $clampingRequest)
            ->with('success', 'Task assigned to enforcer.');
    }

    public function resolve(ClampingRequest $clampingRequest): RedirectResponse
    {
        $this->authorize('update', $clampingRequest);

        $clampingRequest->update([
            'status' => 'resolved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        Archive::create([
            'archivable_type' => ClampingRequest::class,
            'archivable_id' => $clampingRequest->id,
            'archived_by' => auth()->id(),
            'archived_at' => now(),
            'reason' => 'Clamping request resolved',
            'snapshot' => $clampingRequest->refresh()->toArray(),
        ]);

        $admins = User::whereIn('role', [Role::SuperAdmin, Role::Administrator])->get();
        foreach ($admins as $admin) {
            SystemNotification::notify(
                $admin,
                'clamping_action',
                'Clamping Request Resolved',
                "Clamping request #{$clampingRequest->id} for {$clampingRequest->vehicle_plate} has been resolved.",
                ['clamping_request_id' => $clampingRequest->id]
            );
        }

        return redirect()->route('clamping-requests.show', $clampingRequest)
            ->with('success', 'Clamping request marked as resolved.');
    }
}
