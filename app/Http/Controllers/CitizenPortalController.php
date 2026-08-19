<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Citation;
use App\Models\ClampingRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CitizenPortalController extends Controller
{
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

        return view('citizen.citation-detail', compact('citation'));
    }

    public function citationDetail(Citation $citation): View
    {
        $citation->load(['violationType', 'evidence', 'payment']);

        return view('citizen.citation-detail', compact('citation'));
    }

    public function clampingRequest(): View
    {
        return view('citizen.clamping-request');
    }

    public function storeClampingRequest(Request $request)
    {
        $data = $request->validate([
            'requester_name' => 'required|string|max:255',
            'requester_phone' => 'required|string|max:20',
            'requester_email' => 'required|email',
            'location_address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'vehicle_plate' => 'required|string|max:20',
            'vehicle_description' => 'nullable|string',
            'evidence_photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'additional_notes' => 'nullable|string|max:1000',
        ]);

        $photoPath = $request->file('evidence_photo')->store('clamping-requests', 'public');
        $data['evidence_photo'] = $photoPath;
        $data['status'] = 'pending';

        $clampingRequest = ClampingRequest::create($data);

        if (auth()->check()) {
            Archive::create([
                'archivable_type' => ClampingRequest::class,
                'archivable_id' => $clampingRequest->id,
                'archived_by' => auth()->id(),
                'archived_at' => now(),
                'reason' => 'Clamping request created',
                'snapshot' => $clampingRequest->toArray(),
            ]);
        }

        return view('citizen.clamping-success');
    }

    public function clampingSuccess(): View
    {
        return view('citizen.clamping-success');
    }
}
