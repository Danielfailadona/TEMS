<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Http\Requests\StoreClampingRequest;
use App\Models\Citation;
use App\Models\ClampingRecord;
use App\Models\ClampingRequest as CitizenClampingRequest;
use App\Services\CitationNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClampingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClampingRecord::class);

        $query = ClampingRecord::with(['officer', 'citation']);

        $records = $query->latest('clamped_at')->paginate(10);

        $pendingRequests = CitizenClampingRequest::where('status', 'pending')
            ->latest()
            ->get();

        $overdueCitations = collect();
        if (auth()->user()->isRole(\App\Enums\Role::SuperAdmin, \App\Enums\Role::Administrator, \App\Enums\Role::ClampingOfficer)) {
            $eligibleDays = config('itevcms.clamping_eligible_days');
            $overdueCitations = Citation::whereIn('status', [CitationStatus::Overdue, CitationStatus::Issued])
                ->whereDate('due_date', '<=', now()->subDays($eligibleDays))
                ->whereDoesntHave('clampingRecords')
                ->orderBy('vehicle_plate')
                ->get();
        }

        return view('clamping.index', compact('records', 'pendingRequests', 'overdueCitations'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ClampingRecord::class);

        $plate = $request->vehicle_plate;
        $citation = null;
        if ($plate) {
            $citation = Citation::where('vehicle_plate', $plate)
                ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue])
                ->latest('issued_at')
                ->first();
        }

        return view('clamping.create', compact('plate', 'citation'));
    }

    public function store(StoreClampingRequest $request, CitationNumberService $numberService): RedirectResponse
    {
        $this->authorize('create', ClampingRecord::class);

        $existingClamp = ClampingRecord::where('vehicle_plate', $request->vehicle_plate)
            ->where('status', ClampingStatus::AwaitingPayment)
            ->exists();

        if ($existingClamp) {
            return back()->withErrors(['vehicle_plate' => 'This vehicle is already clamped.']);
        }

        $citation = Citation::where('vehicle_plate', $request->vehicle_plate)
            ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue])
            ->latest('issued_at')
            ->first();

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('clamping', 'public');
        }

        $record = ClampingRecord::create([
            'notice_number' => $numberService->noticeNumber(),
            'vehicle_plate' => $request->vehicle_plate,
            'citation_id' => $citation?->id,
            'clamped_by' => auth()->id(),
            'status' => ClampingStatus::AwaitingPayment,
            'location' => $request->location,
            'notes' => $request->notes,
            'evidence_path' => $evidencePath,
            'clamped_at' => now(),
        ]);

        if ($citation) {
            $citation->update(['status' => CitationStatus::Clamped]);
        }

        return redirect()->route('impounding.show', $record)->with('success', 'Vehicle clamp recorded. It is now in the impounding pipeline.');
    }

    public function show(ClampingRecord $clamping): View
    {
        $this->authorize('view', $clamping);

        $clamping->load(['officer', 'citation']);

        return view('clamping.show', compact('clamping'));
    }
}
