<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Citation;
use App\Models\CitizenReport;
use App\Models\ClampingRecord;
use App\Services\CitationNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $request->validate([
            'search' => 'required|string|min:3',
        ]);

        $search = $request->input('search');

        $citation = Citation::with(['violationType', 'evidence'])
            ->where(function ($query) use ($search) {
                $query->where('citation_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_plate', 'like', "%{$search}%");
            })
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

        $search = $request->input('search');

        $clamping = \App\Models\ClampingRecord::with(['officer', 'citation', 'impoundingRecord', 'release'])
            ->where(function ($query) use ($search) {
                $query->where('notice_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_plate', 'like', "%{$search}%");
            })
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

        return redirect()->route('citizen.clamping.success', ['reference' => $citizenReport->report_number]);
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
