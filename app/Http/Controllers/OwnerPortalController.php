<?php

namespace App\Http\Controllers;

use App\Models\Citation;
use App\Models\ClampingRecord;
use Illuminate\View\View;

class OwnerPortalController extends Controller
{
    public function citations(): View
    {
        $citations = Citation::with(['violationType', 'payment'])
            ->where('issued_by', auth()->id())
            ->latest('issued_at')
            ->paginate(10);

        return view('owner.citations', compact('citations'));
    }

    public function clamping(): View
    {
        $records = ClampingRecord::with(['citation'])
            ->where('clamped_by', auth()->id())
            ->latest('clamped_at')
            ->paginate(10);

        return view('owner.clamping', compact('records'));
    }
}
