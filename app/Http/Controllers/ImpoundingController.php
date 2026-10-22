<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Enums\ImpoundingStatus;
use App\Enums\Role;
use App\Models\Archive;
use App\Models\Citation;
use App\Models\ClampingRecord;
use App\Models\ImpoundingRecord;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\VehicleRelease;
use App\Services\CitationNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ImpoundingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ImpoundingRecord::class);

        $query = ImpoundingRecord::with(['officer', 'citation.violationType', 'clampingRecord']);

        $status = ImpoundingStatus::tryFrom((string) $request->query('status'));

        if ($status !== null) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', [
                ImpoundingStatus::Impounded,
                ImpoundingStatus::AwaitingPayment,
                ImpoundingStatus::Paid,
                ImpoundingStatus::WaitingRelease,
            ]);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('vehicle_plate', 'like', "%{$search}%")
                    ->orWhere('notice_number', 'like', "%{$search}%")
                    ->orWhereHas('citation', fn ($c) => $c->where('citation_number', 'like', "%{$search}%"))
                    ->orWhereHas('citation.violationType', fn ($v) => $v->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('officer', fn ($o) => $o->where('name', 'like', "%{$search}%"));
            });
        }

        $records = $query->latest('impounded_at')->paginate(10)->appends($request->query());

        return view('impounding.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ImpoundingRecord::class);

        $plate = $request->vehicle_plate;
        $citation = null;
        if ($plate) {
            $citation = Citation::where('vehicle_plate', $plate)
                ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue])
                ->whereHas('violationType', fn ($q) => $q->where('is_impoundable', true))
                ->latest('issued_at')
                ->first();
        }

        return view('impounding.create', compact('plate', 'citation'));
    }

    public function store(Request $request, CitationNumberService $numberService): RedirectResponse
    {
        $this->authorize('create', ImpoundingRecord::class);

        $validated = $request->validate([
            'vehicle_plate' => 'required|string|max:20',
            'citation_id' => 'nullable|exists:citations,id',
            'location' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'evidence' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'evidence.required' => 'An evidence photo is required.',
            'evidence.image' => 'The evidence file must be an image.',
            'evidence.mimes' => 'The evidence photo must be a JPEG, PNG, or WebP.',
            'evidence.max' => 'The evidence photo must be 5 MB or smaller.',
        ]);

        $existingImpound = ImpoundingRecord::where('vehicle_plate', $validated['vehicle_plate'])
            ->whereIn('status', [ImpoundingStatus::Impounded, ImpoundingStatus::AwaitingPayment, ImpoundingStatus::Paid, ImpoundingStatus::WaitingRelease])
            ->exists();

        if ($existingImpound) {
            return back()->withErrors(['vehicle_plate' => 'This vehicle is already impounded.']);
        }

        $citation = Citation::where('vehicle_plate', $validated['vehicle_plate'])
            ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue])
            ->whereHas('violationType', fn ($q) => $q->where('is_impoundable', true))
            ->latest('issued_at')
            ->first();

        // Determine fees based on violation type
        $violationCode = $citation?->violationType?->code ?? 'default';
        $fees = config('itevcms.impounding.violation_fees')[$violationCode] ?? config('itevcms.impounding.violation_fees.default');

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            try {
                $evidencePath = \App\Services\SupabaseStorage::put('impounding/'.$request->file('evidence')->hashName(), $request->file('evidence'));
            } catch (\Throwable $e) {
                report($e);
                return back()->with('error', 'Failed to upload impounding evidence. Please try again.');
            }
        }

        $clampingRecord = null;
        if ($citation) {
            $clampingRecord = ClampingRecord::where('citation_id', $citation->id)
                ->where('status', '!=', ClampingStatus::Released)
                ->latest('clamped_at')
                ->first();
        }
        if (! $clampingRecord) {
            $clampingRecord = ClampingRecord::where('vehicle_plate', $validated['vehicle_plate'])
                ->where('status', '!=', ClampingStatus::Released)
                ->latest('clamped_at')
                ->first();
        }

        $record = ImpoundingRecord::create([
            'notice_number' => $numberService->impoundNumber(),
            'citation_id' => $citation?->id,
            'clamping_record_id' => $clampingRecord?->id,
            'impounded_by' => auth()->id(),
            'status' => ImpoundingStatus::Impounded,
            'location' => $validated['location'],
            'notes' => $validated['notes'],
            'evidence_path' => $evidencePath,
            'towing_fee' => $fees['towing_fee'],
            'storage_fee_per_day' => $fees['storage_fee_per_day'],
            'admin_fee' => $fees['admin_fee'],
            'grace_until' => now()->addHours(config('itevcms.impounding.grace_hours', 24)),
            'impounded_at' => now(),
        ]);

        Archive::log($record, 'Vehicle impounded', auth()->id());

        if ($citation) {
            $citation->update(['status' => CitationStatus::Impounded]);
        }

        // Notify vehicle owner (if exists in users)
        if ($record->citation && $record->citation->driver_id) {
            $driver = User::find($record->citation->driver_id);
            if ($driver) {
                SystemNotification::notify(
                    $driver,
                    'impounding_created',
                    'Vehicle Impounded',
                    "Your vehicle {$record->vehicle_plate} has been impounded. Notice: {$record->notice_number}. Total fees will accrue after grace period.",
                    ['impounding_record_id' => $record->id]
                );
            }
        }

        // Notify the impounding officer
        if ($record->impounded_by) {
            $officer = User::find($record->impounded_by);
            if ($officer) {
                SystemNotification::notify(
                    $officer,
                    'impounding_created',
                    'Impound Recorded',
                    "You impounded vehicle {$record->vehicle_plate}. Notice: {$record->notice_number}",
                    ['impounding_record_id' => $record->id]
                );
            }
        }

        // Notify admins
        $admins = User::whereIn('role', [Role::SuperAdmin, Role::Administrator])->get();
        foreach ($admins as $admin) {
            SystemNotification::notify(
                $admin,
                'impounding_created',
                'Vehicle Impounded',
                "Vehicle {$record->vehicle_plate} was impounded. Notice: {$record->notice_number}",
                ['impounding_record_id' => $record->id]
            );
        }

        return redirect()->route('impounding.show', $record)->with('success', 'Vehicle impounded successfully. Fees will accrue after grace period.');
    }

    public function show(ImpoundingRecord $impounding): View
    {
        $this->authorize('view', $impounding);

        $impounding->load([
            'officer',
            'citation.violationType',
            'citation.payment.cashier',
            'payments.cashier',
            'release.releasedBy',
            'clampingRecord',
        ]);

        return view('impounding.show', compact('impounding'));
    }

    public function markPaid(Request $request, ImpoundingRecord $impounding): RedirectResponse
    {
        $this->authorize('markPaid', $impounding);

        $validated = $request->validate([
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
        ]);

        app(PaymentRecorder::class)->recordImpounding($impounding, [
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'],
            ], auth()->id());

        return redirect()->route('impounding.show', $impounding)
            ->with('success', 'Payment recorded. Total: ₱' . number_format($impounding->fresh()->getTotalFees(), 2));
    }

    public function markWaitingRelease(ImpoundingRecord $impounding): RedirectResponse
    {
        $this->authorize('markWaitingRelease', $impounding);

        $impounding->update(['status' => ImpoundingStatus::WaitingRelease]);

        return redirect()->route('impounding.show', $impounding)
            ->with('success', 'Vehicle marked as waiting for release.');
    }

    public function printRelease(ImpoundingRecord $impounding): View
    {
        $this->authorize('view', $impounding);

        abort_if($impounding->status !== ImpoundingStatus::Released, 404);

        $impounding->load([
            'officer',
            'citation.violationType',
            'citation.payment.cashier',
            'payments.cashier',
            'release.releasedBy',
        ]);

        return view('impounding.print-release', compact('impounding'));
    }

    public function processRelease(Request $request, ImpoundingRecord $impounding): RedirectResponse
    {
        $this->authorize('processRelease', $impounding);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($impounding, $validated) {
            $releaseNumber = 'REL-' . str_pad((VehicleRelease::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);

            VehicleRelease::create([
                'release_number' => $releaseNumber,
                'impounding_record_id' => $impounding->id,
                'released_by' => auth()->id(),
                'notes' => $validated['notes'],
                'released_at' => now(),
            ]);

            $impounding->update(['status' => ImpoundingStatus::Released]);

            if ($impounding->citation) {
                $impounding->citation->update(['status' => CitationStatus::Released]);
            }

            Archive::log($impounding, 'Vehicle released (Notice: ' . $impounding->notice_number . ')', auth()->id());
        });

        return redirect()->route('impounding.index')
            ->with('success', 'Vehicle released successfully. Record archived.');
    }
}