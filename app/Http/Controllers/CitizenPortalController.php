<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Models\Archive;
use App\Models\Citation;
use App\Models\CitizenReport;
use App\Models\ClampingRecord;
use App\Services\CitationNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CitizenPortalController extends Controller
{
    public function citationTicket(Request $request, $id, $token): View
    {
        $citation = Citation::with(['violationType', 'enforcer', 'evidence', 'payment'])
            ->find($id);

        if (! $citation || ! hash_equals($citation->getValidationToken(), (string) $token)) {
            abort(404);
        }

        return view('citations.ticket', compact('citation'));
    }

    public function clampingTicket(Request $request, $id, $token): View
    {
        $clamping = ClampingRecord::with(['officer', 'citation', 'release.releasedBy', 'payments.cashier', 'payments.citation'])
            ->find($id);

        if (! $clamping || ! hash_equals($clamping->getValidationToken(), (string) $token)) {
            abort(404);
        }

        return view('clamping.ticket', compact('clamping'));
    }

    public function clampingPrint(Request $request, $id, $token): View
    {
        $clamping = ClampingRecord::with(['officer', 'citation', 'release.releasedBy', 'payments.cashier', 'payments.citation'])
            ->find($id);

        if (! $clamping || ! hash_equals($clamping->getValidationToken(), (string) $token)) {
            abort(404);
        }

        return view('clamping.ticket-print', compact('clamping'));
    }

    public function citationPrint(Request $request, $id, $token): View
    {
        $citation = Citation::with(['violationType', 'enforcer', 'evidence', 'payment'])
            ->find($id);

        if (! $citation || ! hash_equals($citation->getValidationToken(), (string) $token)) {
            abort(404);
        }

        $isEnforcerCopy = false;

        return view('citations.print', compact('citation', 'isEnforcerCopy'));
    }

    /**
     * Payment form reached from the citizen ticket lookup. Every field the
     * gateway needs is pre-filled from the looked-up citation (autofill), so
     * the citizen only confirms before being sent to PayMongo.
     */
    public function citationPay(Request $request, $id, $token): View
    {
        $citation = Citation::with(['violationType', 'enforcer', 'payment'])->find($id);

        if (! $citation || ! hash_equals($citation->getValidationToken(), (string) $token)) {
            abort(404);
        }

        $payerName = old('payer_name', $citation->driver_name ?? auth()->user()?->name);
        $payerPhone = old('payer_phone', auth()->user()?->phone);

        return view('citizen.citation-pay', compact('citation', 'token', 'payerName', 'payerPhone'));
    }

    public function citationLookup(Request $request): View
    {
        return view('citizen.citation-lookup');
    }

    public function citationSearch(Request $request)
    {
        $advanced = $request->filled('citation_number')
            || $request->filled('plate_number')
            || $request->filled('issued_date')
            || $request->filled('status');

        if ($advanced) {
            $request->validate([
                'citation_number' => 'nullable|string|max:50',
                'plate_number' => 'nullable|string|max:20',
                'issued_date' => 'nullable|date',
                'status' => ['nullable', Rule::in(array_column(CitationStatus::cases(), 'value'))],
            ]);

            $query = Citation::with(['violationType', 'evidence']);

            $query->where(function ($q) use ($request) {
                if ($request->filled('citation_number')) {
                    $term = '%'.mb_strtolower(trim($request->input('citation_number'))).'%';
                    $q->whereRaw('LOWER(citation_number) LIKE ?', [$term]);
                }
                if ($request->filled('plate_number')) {
                    $term = '%'.mb_strtolower(trim($request->input('plate_number'))).'%';
                    $q->whereRaw('LOWER(vehicle_plate) LIKE ?', [$term]);
                }
                if ($request->filled('issued_date')) {
                    $q->whereDate('issued_at', $request->input('issued_date'));
                }
                if ($request->filled('status')) {
                    $q->where('status', $request->input('status'));
                }
            });

            $citation = $query->orderByDesc('issued_at')->first();

            if (! $citation) {
                return back()->with('error', 'No citation found matching your search.');
            }

            return view('citizen.citation-lookup', ['citationResult' => $citation]);
        }

        $request->validate([
            'search' => 'required|string|min:3',
        ]);

        $search = trim($request->input('search'));
        $term = '%'.mb_strtolower($search).'%';

        // Case-insensitive matching: Postgres LIKE is case-sensitive and plates
        // are stored uppercase, so a lowercase plate would never match otherwise.
        $citation = Citation::with(['violationType', 'evidence'])
            ->where(function ($query) use ($term) {
                $query->whereRaw('LOWER(citation_number) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term]);
            })
            ->orderByRaw('citation_number = ? desc', [$search])
            ->orderByDesc('issued_at')
            ->first();

        if (! $citation) {
            return back()->with('error', 'No citation found matching your search.');
        }

        return view('citizen.citation-lookup', ['citationResult' => $citation]);
    }

    public function clampingLookup(Request $request): View
    {
        return view('citizen.citation-lookup', ['clampingTab' => true]);
    }

    public function clampingSearch(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:3',
        ]);

        $search = trim($request->input('search'));
        $term = '%'.mb_strtolower($search).'%';

        // Case-insensitive; the plate lives on the linked citation (the
        // clamping_records.vehicle_plate column was dropped).
        $clamping = \App\Models\ClampingRecord::with(['officer', 'citation', 'impoundingRecord', 'release'])
            ->where(function ($query) use ($term) {
                $query->whereRaw('LOWER(notice_number) LIKE ?', [$term])
                    ->orWhereHas('citation', fn ($c) => $c->whereRaw('LOWER(vehicle_plate) LIKE ?', [$term]));
            })
            ->orderByRaw('notice_number = ? desc', [$search])
            ->orderByDesc('clamped_at')
            ->first();

        if (! $clamping) {
            return back()->with('error', 'No clamping notice found matching your search.');
        }

        return view('citizen.citation-lookup', ['clampingTab' => true, 'clampingResult' => $clamping]);
    }

    public function citationDetail(Citation $citation): View
    {
        $citation->load(['violationType', 'evidence', 'payment', 'appeal.submitter', 'enforcer']);

        return view('citizen.citation-detail', compact('citation'));
    }

    public function clampingLanding(Request $request): View
    {
        $requestInfo = $this->findClampingRequest($request->query('reference'));

        return view('citizen.clamping-request', compact('requestInfo'));
    }

    public function clampingForm(Request $request): View
    {
        $requestInfo = $this->findClampingRequest($request->query('reference'));

        return view('citizen.clamping-request', compact('requestInfo'));
    }

    public function storeClampingRequest(Request $request, CitationNumberService $numberService)
    {
        $data = $request->validate([
            'requester_name' => 'required|string|max:255',
            'requester_phone' => 'required|string|max:20',
            'requester_email' => 'required|email',
            'location_address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'vehicle_plate' => 'required|string|max:20',
            'vehicle_make' => 'nullable|string|max:100',
            'vehicle_model' => 'nullable|string|max:100',
            'vehicle_color' => 'nullable|string|max:50',
            'evidence_photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'additional_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $photoPath = \App\Services\SupabaseStorage::put('citizen-reports/'.$request->file('evidence_photo')->hashName(), $request->file('evidence_photo'));
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'We could not upload your evidence photo. Please try again.');
        }
        $data['evidence_photo'] = $photoPath;
        $data['status'] = 'pending';
        $data['report_number'] = $numberService->reportNumber();

        $citizenReport = CitizenReport::create($data);

        if (auth()->check()) {
            Archive::log($citizenReport, 'Citizen report created', auth()->id());
        }

        return redirect()->route('citizen.report-parking.success', ['reference' => $citizenReport->report_number]);
    }

    public function clampingSuccess(Request $request): View
    {
        $reference = $request->query('reference');
        return view('citizen.clamping-success', compact('reference'));
    }

    public function clampingTrack(): View
    {
        return view('citizen.clamping-track');
    }

    public function clampingTrackSearch(Request $request)
    {
        $request->validate([
            'reference' => 'required|string|max:50',
        ]);

        $reference = $request->input('reference');
        $requestInfo = CitizenReport::where('report_number', $reference)
            ->with(['processedBy'])
            ->first();

        return view('citizen.clamping-track', compact('requestInfo', 'reference'));
    }

    private function findClampingRequest(?string $reference): ?CitizenReport
    {
        if (blank($reference)) {
            return null;
        }

        return CitizenReport::where('report_number', $reference)
            ->with(['processedBy'])
            ->first();
    }
}
