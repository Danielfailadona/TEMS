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
use App\Services\PaymentRecorder;
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
                $inner->where('notice_number', 'like', "%{$search}%")
                    ->orWhereHas('citation', fn ($c) => $c->where('vehicle_plate', 'like', "%{$search}%"))
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

        if ($request->filled('citation_id')) {
            $citation = Citation::with('violationType')->find($request->citation_id);
        } elseif ($plate) {
            $citation = Citation::where('vehicle_plate', $plate)
                ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue])
                ->whereHas('violationType', fn ($q) => $q->where('is_impoundable', true))
                ->latest('issued_at')
                ->first();
        }

        // Citations eligible for impounding: impoundable violation, still Issued/Overdue,
        // unpaid, and no active (non-released) impounding record. The admin selects one
        // here and records the impound.
        $eligibleQuery = Citation::with(['violationType', 'enforcer'])
            ->whereHas('violationType', fn ($v) => $v->where('is_impoundable', true))
            ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue])
            ->whereDoesntHave('impoundingRecord', fn ($r) => $r->where('status', '!=', ImpoundingStatus::Released))
            ->whereDoesntHave('payment', fn ($p) => $p->whereNotNull('paid_at'));

        if ($request->filled('eligible_search')) {
            $term = trim($request->eligible_search);
            $eligibleQuery->where(function ($q) use ($term) {
                $q->where('citation_number', 'like', "%{$term}%")
                    ->orWhere('vehicle_plate', 'like', "%{$term}%")
                    ->orWhere('driver_name', 'like', "%{$term}%");
            });
        }

        if ($request->filled('eligible_status') && in_array($request->eligible_status, [CitationStatus::Issued->value, CitationStatus::Overdue->value], true)) {
            $eligibleQuery->where('status', $request->eligible_status);
        }

        $eligibleCitations = $eligibleQuery->orderBy('due_date')->limit(100)->get();

        // Fee schedule per violation code, used by the client-side picker to
        // update the fee cards when a citation is selected.
        $feeSchedule = [];
        foreach ($eligibleCitations as $eligible) {
            $code = $eligible->violationType?->code ?? 'default';
            if (! array_key_exists($code, $feeSchedule)) {
                $fees = config('itevcms.impounding.violation_fees')[$code] ?? config('itevcms.impounding.violation_fees.default');
                $feeSchedule[$code] = [
                    'towing_fee' => (float) ($fees['towing_fee'] ?? 0),
                    'storage_fee_per_day' => (float) ($fees['storage_fee_per_day'] ?? 0),
                    'admin_fee' => (float) ($fees['admin_fee'] ?? 0),
                ];
            }
        }
        $feeSchedule['default'] = $feeSchedule['default']
            ?? collect(config('itevcms.impounding.violation_fees.default'))
                ->map(fn ($v) => (float) $v)->all();

        // Fees for the pre-selected citation (defaults when none is linked)
        $violationCode = $citation?->violationType?->code ?? 'default';
        $fees = $feeSchedule[$violationCode] ?? $feeSchedule['default'];

        return view('impounding.create', compact('plate', 'citation', 'fees', 'eligibleCitations', 'feeSchedule'));
    }

    public function store(Request $request, CitationNumberService $numberService): RedirectResponse
    {
        $this->authorize('create', ImpoundingRecord::class);

        $validated = $request->validate([
            'vehicle_plate' => 'required|string|max:20',
            'citation_id' => 'required|integer|exists:citations,id',
            'location' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $citation = Citation::with('violationType')->findOrFail($validated['citation_id']);

        if (! $citation->violationType?->is_impoundable) {
            return back()->withErrors(['citation_id' => 'The linked citation is not eligible for impounding.']);
        }

        if (! in_array($citation->status, [CitationStatus::Issued, CitationStatus::Overdue], true)) {
            return back()->withErrors(['citation_id' => 'Only issued or overdue citations can be impounded.']);
        }

        $existingImpound = ImpoundingRecord::whereHas('citation', function ($query) use ($citation) {
            $query->where('vehicle_plate', $citation->vehicle_plate);
        })
            ->whereIn('status', [ImpoundingStatus::Impounded, ImpoundingStatus::AwaitingPayment, ImpoundingStatus::Paid, ImpoundingStatus::WaitingRelease])
            ->exists();

        if ($existingImpound) {
            return back()->withErrors(['vehicle_plate' => 'This vehicle is already impounded.']);
        }

        // Determine fees based on violation type
        $violationCode = $citation->violationType?->code ?? 'default';
        $fees = config('itevcms.impounding.violation_fees')[$violationCode] ?? config('itevcms.impounding.violation_fees.default');

        $clampingRecord = ClampingRecord::where('citation_id', $citation->id)
            ->where('status', '!=', ClampingStatus::Released)
            ->latest('clamped_at')
            ->first();
        if (! $clampingRecord) {
            $clampingRecord = ClampingRecord::whereHas('citation', function ($query) use ($citation) {
                $query->where('vehicle_plate', $citation->vehicle_plate);
            })
                ->where('status', '!=', ClampingStatus::Released)
                ->latest('clamped_at')
                ->first();
        }

        $record = ImpoundingRecord::create([
            'notice_number' => $numberService->impoundNumber(),
            'citation_id' => $citation->id,
            'clamping_record_id' => $clampingRecord?->id,
            'impounded_by' => auth()->id(),
            'status' => ImpoundingStatus::Impounded,
            'location' => $validated['location'],
            'notes' => $validated['notes'],
            // Evidence is inherited from the citation's own photos — no separate upload.
            'evidence_path' => $citation->evidence()->first()?->file_path,
            'towing_fee' => $fees['towing_fee'],
            'storage_fee_per_day' => $fees['storage_fee_per_day'],
            'admin_fee' => $fees['admin_fee'],
            'grace_until' => now()->addHours(config('itevcms.impounding.grace_hours', 24)),
            'impounded_at' => now(),
        ]);

        Archive::log($record, 'Vehicle impounded', auth()->id());

        $citation->update(['status' => CitationStatus::Impounded]);

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
                'clamping_record_id' => $impounding->clamping_record_id,
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

    public function destroy(ImpoundingRecord $impounding): RedirectResponse
    {
        $this->authorize('delete', $impounding);

        $blockers = [];
        if ($impounding->payments()->exists()) {
            $blockers[] = 'a payment record';
        }
        if ($impounding->release()->exists()) {
            $blockers[] = 'a vehicle release';
        }

        if ($blockers !== []) {
            return back()->with('error', 'Cannot delete impounding record '.$impounding->notice_number.' because it is linked to '.implode(' and ', $blockers).'. Delete or resolve the linked records first.');
        }

        DB::transaction(function () use ($impounding) {
            Archive::log($impounding, 'Impounding record deleted', auth()->id(), $impounding->toArray());

            $impounding->delete();
        });

        return redirect()->route('impounding.index')
            ->with('success', 'Impounding record '.$impounding->notice_number.' has been deleted and archived.');
    }
}