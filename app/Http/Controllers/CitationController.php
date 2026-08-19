<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Http\Requests\StoreCitationRequest;
use App\Models\Archive;
use App\Models\Citation;
use App\Models\CitationEvidence;
use App\Models\ClampingRecord;
use App\Models\ViolationType;
use App\Services\CitationNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CitationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Citation::class);

        $query = Citation::with(['violationType', 'enforcer']);

        $citations = $query
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('citation_number', 'like', "%{$search}%")
                        ->orWhere('vehicle_plate', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('issued_at')
            ->paginate(10)
            ->withQueryString();

        return view('citations.index', compact('citations'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Citation::class);

        return view('citations.create', [
            'violationTypes' => ViolationType::where('is_active', true)->orderBy('name')->get(),
            'selectedPlate' => $request->vehicle_plate,
        ]);
    }

    public function store(StoreCitationRequest $request, CitationNumberService $numberService): RedirectResponse
    {
        $this->authorize('create', Citation::class);

        $violationType = ViolationType::findOrFail($request->violation_type_id);

        $citation = DB::transaction(function () use ($request, $numberService, $violationType) {
            $citation = Citation::create([
                'citation_number' => $numberService->generate(),
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => $request->vehicle_plate,
                'vehicle_make' => $request->vehicle_make,
                'vehicle_model' => $request->vehicle_model,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_color' => $request->vehicle_color,
                'driver_name' => $request->driver_name,
                'driver_license' => $request->driver_license,
                'issued_by' => auth()->id(),
                'penalty_amount' => $violationType->penalty_amount,
                'status' => CitationStatus::Issued,
                'location' => $request->location,
                'notes' => $request->notes,
                'issued_at' => now(),
                'due_date' => $numberService->dueDate(),
            ]);

            if ($request->hasFile('evidence')) {
                foreach ($request->file('evidence') as $file) {
                    $path = $file->store('citations/'.$citation->id, 'public');
                    CitationEvidence::create([
                        'citation_id' => $citation->id,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            }

            Archive::create([
                'archivable_type' => Citation::class,
                'archivable_id' => $citation->id,
                'archived_by' => auth()->id(),
                'archived_at' => now(),
                'reason' => 'Citation issued',
                'snapshot' => $citation->toArray(),
            ]);

            return $citation;
        });

        return redirect()->route('citations.show', $citation)->with('success', 'Citation issued successfully.');
    }

    public function show(Citation $citation): View
    {
        $this->authorize('view', $citation);

        $citation->load(['violationType', 'enforcer', 'evidence', 'payment.cashier', 'clampingRecords']);

        return view('citations.show', compact('citation'));
    }

    public function referToImpounding(Citation $citation, CitationNumberService $numberService): RedirectResponse
    {
        $this->authorize('referToImpounding', ClampingRecord::class);

        if (! $citation->violationType?->is_impoundable) {
            return back()->with('error', 'This violation type is not eligible for impounding.');
        }

        if (! in_array($citation->status, [CitationStatus::Issued, CitationStatus::Overdue], true)) {
            return back()->with('error', 'Only issued or overdue citations can be referred for impounding.');
        }

        if ($citation->clampingRecords()->exists()) {
            return back()->with('error', 'This citation already has a clamping record.');
        }

        $clamping = DB::transaction(function () use ($citation, $numberService) {
            $clamping = ClampingRecord::create([
                'notice_number' => $numberService->noticeNumber(),
                'vehicle_plate' => $citation->vehicle_plate,
                'citation_id' => $citation->id,
                'clamped_by' => auth()->id(),
                'status' => ClampingStatus::AwaitingPayment,
                'location' => $citation->location,
                'notes' => 'Referred for impounding by ' . auth()->user()->name,
                'clamped_at' => now(),
            ]);

            Archive::create([
                'archivable_type' => ClampingRecord::class,
                'archivable_id' => $clamping->id,
                'archived_by' => auth()->id(),
                'archived_at' => now(),
                'reason' => 'Vehicle clamped (referred from citation)',
                'snapshot' => $clamping->toArray(),
            ]);

            $citation->update(['status' => CitationStatus::Clamped]);

            return $clamping;
        });

        return redirect()->route('impounding.show', $clamping)
            ->with('success', 'Vehicle referred for impounding successfully.');
    }
}
