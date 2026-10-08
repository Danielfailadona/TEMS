<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Http\Requests\StoreClampingRequest;
use App\Models\Archive;
use App\Models\Citation;
use App\Models\ClampingRecord;
use App\Models\ClampingRequest as CitizenClampingRequest;
use App\Models\User;
use App\Models\VehicleRelease;
use App\Services\CitationNumberService;
use App\Services\PaymentRecorder;
use App\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClampingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClampingRecord::class);

        $query = ClampingRecord::with(['officer', 'citation']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('notice_number', 'like', "%{$search}%")
                  ->orWhere('vehicle_plate', 'like', "%{$search}%")
                  ->orWhereHas('officer', fn ($o) => $o->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay()->setTimezone('UTC');
            $dateTo   = Carbon::parse($request->date_to)->endOfDay()->setTimezone('UTC');
            $query->whereBetween('clamped_at', [$dateFrom, $dateTo]);
        }

        $records = $query->latest('clamped_at')->paginate(12)->withQueryString();

        $pendingRequests = CitizenClampingRequest::where('status', 'pending')
            ->latest()
            ->get();

        $overdueCitations = collect();
        if (auth()->user()->isRole(\App\Enums\Role::SuperAdmin, \App\Enums\Role::Administrator, \App\Enums\Role::Enforcer)) {
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
            try {
                $evidencePath = \App\Services\SupabaseStorage::put('clamping/'.$request->file('evidence')->hashName(), $request->file('evidence'));
            } catch (\Throwable $e) {
                report($e);
                return back()->with('error', 'Failed to upload clamp evidence. Please try again.');
            }
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

        Archive::create([
            'archivable_type' => ClampingRecord::class,
            'archivable_id' => $record->id,
            'archived_by' => auth()->id(),
            'archived_at' => now(),
            'reason' => 'Vehicle clamped',
            'snapshot' => $record->toArray(),
        ]);

        if ($citation) {
            $citation->update(['status' => CitationStatus::Clamped]);
        }

        return redirect()->route('clamping.show', $record)->with('success', 'Vehicle clamp recorded successfully.');
    }

    public function show(ClampingRecord $clamping): View
    {
        $this->authorize('view', $clamping);

        $clamping->load(['officer', 'citation', 'payments.cashier', 'release.releasedBy']);

        return view('clamping.show', compact('clamping'));
    }

    public function markPaid(Request $request, ClampingRecord $clamping): RedirectResponse
    {
        $this->authorize('markPaid', $clamping);

        $validated = $request->validate([
            'clamping_fee' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:255',
        ]);

        app(PaymentRecorder::class)->recordClamping($clamping, [
            'amount' => $validated['clamping_fee'],
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
        ], auth()->id());

        return redirect()->route('clamping.show', $clamping)
            ->with('success', 'Payment recorded. Total: ₱'.number_format($clamping->fresh()->clamping_fee, 2));
    }

    public function markWaitingRelease(ClampingRecord $clamping): RedirectResponse
    {
        $this->authorize('markWaitingRelease', $clamping);

        $clamping->update([
            'status' => ClampingStatus::WaitingRelease,
        ]);

        Archive::create([
            'archivable_type' => ClampingRecord::class,
            'archivable_id' => $clamping->id,
            'archived_by' => auth()->id(),
            'archived_at' => now(),
            'reason' => 'Vehicle marked waiting for release (Notice: '.$clamping->notice_number.')',
            'snapshot' => $clamping->fresh()->toArray(),
        ]);

        return redirect()->route('clamping.show', $clamping)
            ->with('success', 'Vehicle marked as waiting for release.');
    }

    public function processRelease(Request $request, ClampingRecord $clamping): RedirectResponse
    {
        $this->authorize('processRelease', $clamping);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($clamping, $validated) {
            VehicleRelease::create([
                'release_number' => app(CitationNumberService::class)->releaseNumber(),
                'clamping_record_id' => $clamping->id,
                'released_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
                'released_at' => now(),
            ]);

            $clamping->update([
                'status' => ClampingStatus::Released,
                'released_at' => now(),
            ]);

            if ($clamping->citation) {
                $clamping->citation->update(['status' => CitationStatus::Released]);
            }

            Archive::create([
                'archivable_type' => ClampingRecord::class,
                'archivable_id' => $clamping->id,
                'archived_by' => auth()->id(),
                'archived_at' => now(),
                'reason' => 'Vehicle released (Notice: '.$clamping->notice_number.')',
                'snapshot' => $clamping->fresh()->toArray(),
            ]);
        });

        $admins = User::whereIn('role', [Role::SuperAdmin, Role::Administrator])->get();
        foreach ($admins as $admin) {
            \App\Models\SystemNotification::notify(
                $admin,
                'clamping_action',
                'Clamp Released',
                'Notice '.$clamping->notice_number.' for plate '.$clamping->vehicle_plate.' has been released.',
                ['clamping_record_id' => $clamping->id]
            );
        }

        return redirect()->route('clamping.show', $clamping)
            ->with('success', 'Vehicle released successfully.');
    }
}
