<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Enums\ImpoundingStatus;
use App\Enums\Role;
use App\Http\Requests\StoreCitationRequest;
use App\Models\Archive;
use App\Models\Citation;
use App\Models\CitationEvidence;
use App\Models\ClampingRecord;
use App\Models\ImpoundingRecord;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\ViolationType;
use App\Services\CitationNumberService;
use App\Services\SupabaseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CitationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Citation::class);

        $query = Citation::with(['violationType', 'enforcer', 'impoundingRecord']);

        $citations = $query
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('citation_number', 'like', "%{$search}%")
                        ->orWhere('vehicle_plate', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('issued_at')
            ->paginate(5)
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
        $isCustom = $violationType->isCustom();
        $penaltyAmount = $isCustom && $request->filled('penalty_amount')
            ? (float) $request->penalty_amount
            : (float) $violationType->penalty_amount;

        $citation = DB::transaction(function () use ($request, $numberService, $violationType, $isCustom, $penaltyAmount) {
            $citation = Citation::create([
                'citation_number' => $numberService->generate(),
                'violation_type_id' => $violationType->id,
                'custom_violation_name' => $isCustom ? $request->custom_violation_name : null,
                'vehicle_plate' => $request->vehicle_plate,
                'vehicle_make' => $request->vehicle_make,
                'vehicle_model' => $request->vehicle_model,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_color' => $request->vehicle_color,
                'driver_name' => $request->driver_name,
                'driver_license' => $request->driver_license,
                'issued_by' => auth()->id(),
                'penalty_amount' => $penaltyAmount,
                'status' => CitationStatus::Issued,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
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

            if ($violationType->is_impoundable) {
                $violationCode = $violationType->code ?? 'default';
                $fees = config('itevcms.impounding.violation_fees')[$violationCode] ?? config('itevcms.impounding.violation_fees.default');

                $impounding = ImpoundingRecord::create([
                    'notice_number' => $numberService->impoundNumber(),
                    'citation_id' => $citation->id,
                    'impounded_by' => auth()->id(),
                    'status' => ImpoundingStatus::Impounded,
                    'location' => $citation->location,
                    'notes' => $request->input('impounding_notes'),
                    'evidence_path' => $evidencePaths[0] ?? null,
                    'towing_fee' => $fees['towing_fee'],
                    'storage_fee_per_day' => $fees['storage_fee_per_day'],
                    'admin_fee' => $fees['admin_fee'],
                    'grace_until' => now()->addHours(config('itevcms.impounding.grace_hours', 24)),
                    'impounded_at' => now(),
                ]);

                Archive::log($impounding, 'Vehicle impounded (auto-created from citation)', auth()->id());

                $citation->update(['status' => CitationStatus::Impounded]);
            } elseif ($violationType->is_clampable) {
                $citation->update(['status' => CitationStatus::Clamped]);
            }

            Archive::log($citation, 'Citation issued', auth()->id());

            return $citation;
        });

        // Notify vehicle owner (if exists in users) and assigned enforcer
        if ($citation->driver_id) {
            $driver = User::find($citation->driver_id);
            if ($driver) {
                SystemNotification::notify(
                    $driver,
                    'citation_issued',
                    'New Citation Issued',
                    "A citation ({$citation->citation_number}) has been issued for your vehicle {$citation->vehicle_plate}. Amount: ₱".number_format($citation->penalty_amount, 2).". Due: {$citation->due_date->format('M d, Y')}.",
                    ['citation_id' => $citation->id]
                );
            }
        }

        // Notify the issuing enforcer
        if ($citation->issued_by) {
            $enforcer = User::find($citation->issued_by);
            if ($enforcer) {
                SystemNotification::notify(
                    $enforcer,
                    'citation_issued',
                    'Citation Issued',
                    "You issued citation {$citation->citation_number} for {$citation->vehicle_plate}.",
                    ['citation_id' => $citation->id]
                );
            }
        }

        // Notify if impounding was created
        if ($violationType->is_impoundable) {
            $impounding = ImpoundingRecord::where('citation_id', $citation->id)->latest()->first();
            if ($impounding) {
                // Notify admins
                $admins = User::whereIn('role', [Role::SuperAdmin, Role::Administrator])->get();
                foreach ($admins as $admin) {
                    SystemNotification::notify(
                        $admin,
                        'impounding_created',
                        'Vehicle Impounded',
                        "Vehicle {$citation->vehicle_plate} was impounded via citation {$citation->citation_number}. Notice: {$impounding->notice_number}",
                        ['impounding_record_id' => $impounding->id, 'citation_id' => $citation->id]
                    );
                }
            }
        }

        // Notify if clamping was created
        if ($violationType->is_clampable) {
            $admins = User::whereIn('role', [Role::SuperAdmin, Role::Administrator])->get();
            foreach ($admins as $admin) {
                SystemNotification::notify(
                    $admin,
                    'clamping_created',
                    'Vehicle Clamped',
                    "Vehicle {$citation->vehicle_plate} was clamped via citation {$citation->citation_number}.",
                    ['citation_id' => $citation->id]
                );
            }
        }

        $impoundSuffix = $violationType->is_impoundable ? ' An impounding record was created for this violation.' : '';
        $clampSuffix = (!$violationType->is_impoundable && $violationType->is_clampable) ? ' Vehicle is clamped.' : '';

        if (auth()->user()->isRole(Role::Enforcer)) {
            return redirect()->route('citations.handoff', $citation)
                ->with('success', 'Citation issued successfully. Show the QR code to the violator.'.$impoundSuffix.$clampSuffix);
        }

        return redirect()->route('citations.show', $citation)->with('success', 'Citation issued successfully.'.$impoundSuffix.$clampSuffix);
    }

    public function handoff(Citation $citation): View
    {
        $this->authorize('handoff', $citation);

        $citation->load(['violationType', 'enforcer', 'evidence']);

        return view('citations.handoff', compact('citation'));
    }

    public function printCitation(Citation $citation): View
    {
        $this->authorize('view', $citation);

        $citation->load(['violationType', 'enforcer', 'evidence', 'payment']);
        $isEnforcerCopy = true;

        return view('citations.print', compact('citation', 'isEnforcerCopy'));
    }

    public function show(Citation $citation): View
    {
        $this->authorize('view', $citation);

        $citation->load(['violationType', 'enforcer', 'evidence', 'payment.cashier', 'clampingRecords', 'impoundingRecord']);

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
                'notes' => 'Referred for impounding by '.auth()->user()->name,
                'clamped_at' => now(),
            ]);

            Archive::log($clamping, 'Vehicle clamped (referred from citation)', auth()->id());

            $citation->update(['status' => CitationStatus::Clamped]);

            return $clamping;
        });

        return redirect()->route('clamping.show', $clamping)
            ->with('success', 'Vehicle clamped successfully. Create the impounding record to continue.');
    }
}
