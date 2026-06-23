<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Enums\PaymentMethod;
use App\Models\Archive;
use App\Models\ClampingRecord;
use App\Models\Payment;
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
        $this->authorize('viewAny', ClampingRecord::class);

        $query = ClampingRecord::with(['officer', 'citation.violationType']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', [
                ClampingStatus::AwaitingPayment,
                ClampingStatus::Paid,
                ClampingStatus::WaitingRelease,
            ]);
        }

        $records = $query->latest('clamped_at')->paginate(10);

        return view('impounding.index', compact('records'));
    }

    public function show(ClampingRecord $clamping): View
    {
        $this->authorize('view', $clamping);

        $clamping->load([
            'officer',
            'citation.violationType',
            'citation.payment.cashier',
            'release.releasedBy',
        ]);

        return view('impounding.show', compact('clamping'));
    }

    public function markPaid(Request $request, ClampingRecord $clamping): RedirectResponse
    {
        $this->authorize('markPaid', $clamping);

        $validated = $request->validate([
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($clamping, $validated) {
            $citation = $clamping->citation;

            if ($citation && !$citation->payment) {
                $numberService = app(CitationNumberService::class);

                Payment::create([
                    'receipt_number' => $numberService->receiptNumber(),
                    'citation_id' => $citation->id,
                    'cashier_id' => auth()->id(),
                    'amount' => $citation->penalty_amount ?? 0,
                    'payment_method' => $validated['payment_method'],
                    'reference_number' => $validated['reference_number'],
                    'paid_at' => now(),
                ]);

                $citation->update(['status' => CitationStatus::Paid]);
            }

            $clamping->update(['status' => ClampingStatus::Paid]);
        });

        return redirect()->route('impounding.show', $clamping)
            ->with('success', 'Payment recorded. Vehicle marked as paid.');
    }

    public function markWaitingRelease(ClampingRecord $clamping): RedirectResponse
    {
        $this->authorize('markWaitingRelease', $clamping);

        $clamping->update(['status' => ClampingStatus::WaitingRelease]);

        return redirect()->route('impounding.show', $clamping)
            ->with('success', 'Vehicle marked as waiting for release.');
    }

    public function printRelease(ClampingRecord $clamping): View
    {
        $this->authorize('view', $clamping);

        abort_if($clamping->status !== ClampingStatus::Released, 404);

        $clamping->load([
            'officer',
            'citation.violationType',
            'citation.payment.cashier',
            'release.releasedBy',
        ]);

        return view('impounding.print-release', compact('clamping'));
    }

    public function processRelease(Request $request, ClampingRecord $clamping): RedirectResponse
    {
        $this->authorize('processRelease', $clamping);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($clamping, $validated) {
            $releaseNumber = 'REL-' . str_pad((VehicleRelease::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);

            VehicleRelease::create([
                'release_number' => $releaseNumber,
                'clamping_record_id' => $clamping->id,
                'released_by' => auth()->id(),
                'notes' => $validated['notes'],
                'released_at' => now(),
            ]);

            $clamping->update(['status' => ClampingStatus::Released]);

            if ($clamping->citation) {
                $clamping->citation->update(['status' => CitationStatus::Released]);
            }

            Archive::create([
                'archivable_type' => ClampingRecord::class,
                'archivable_id' => $clamping->id,
                'archived_by' => auth()->id(),
                'archived_at' => now(),
                'reason' => 'Vehicle released (Notice: ' . $clamping->notice_number . ')',
                'snapshot' => $clamping->toArray(),
            ]);
        });

        return redirect()->route('impounding.index')
            ->with('success', 'Vehicle released successfully. Record archived.');
    }
}
