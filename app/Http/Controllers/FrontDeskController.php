<?php

namespace App\Http\Controllers;

use App\Models\Citation;
use App\Models\ClampingRecord;
use App\Models\ClampingRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontDeskController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $plateNumber = $request->query('plate_number');
        $citationNumber = $request->query('citation_number');
        $noticeNumber = $request->query('notice_number');

        $clamping = null;
        if ($noticeNumber) {
            $clamping = ClampingRecord::with(['officer', 'citation', 'impoundingRecord'])
                ->where('notice_number', 'like', "%{$noticeNumber}%")
                ->latest('clamped_at')
                ->first();
        }

        // If searching by citation number or plate, return single result
        if ($citationNumber || $plateNumber) {
            $citation = null;

            if ($citationNumber) {
                $citation = Citation::with(['violationType', 'payment'])
                    ->where('citation_number', 'like', "%{$citationNumber}%")
                    ->latest('issued_at')
                    ->first();
            } elseif ($plateNumber) {
                $citation = Citation::with(['violationType', 'payment'])
                    ->where('vehicle_plate', 'like', "%{$plateNumber}%")
                    ->latest('issued_at')
                    ->first();
            }

            return view('frontdesk.index', compact('citation', 'clamping', 'plateNumber', 'citationNumber', 'noticeNumber', 'status'));
        }

        // Default: paginated list with status filter
        $citations = Citation::with(['violationType', 'payment'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('issued_at')
            ->paginate(6)
            ->withQueryString();

        return view('frontdesk.index', compact('citations', 'clamping', 'status'));
    }
}
