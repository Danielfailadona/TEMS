<?php

namespace App\Http\Controllers;

use App\Models\Citation;
use App\Models\ClampingRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontDeskController extends Controller
{
    public function index(): View
    {
        return view('frontdesk.index');
    }

    public function search(Request $request): View
    {
        $plateNumber = $request->input('plate_number');
        $citationNumber = $request->input('citation_number');

        $citation = null;

        if ($citationNumber) {
            $citation = Citation::with(['violationType', 'payment'])
                ->where('citation_number', $citationNumber)
                ->first();
        } elseif ($plateNumber) {
            $citation = Citation::with(['violationType', 'payment'])
                ->where('vehicle_plate', $plateNumber)
                ->latest('issued_at')
                ->first();
        }

        return view('frontdesk.index', compact('citation', 'plateNumber', 'citationNumber'));
    }
}
