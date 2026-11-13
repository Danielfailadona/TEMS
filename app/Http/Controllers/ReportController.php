<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Models\Citation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin,administrator,cashier')->only(['overdueCitations']);
    }

    public function overdueCitations(Request $request): View
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : null;

        $query = Citation::with(['violationType', 'enforcer'])
            ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue])
            ->whereDate('due_date', '<', today())
            ->when($from, fn ($q) => $q->whereDate('due_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('due_date', '<=', $to));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($w) use ($search) {
                $w->where('citation_number', 'like', "%{$search}%")
                  ->orWhere('vehicle_plate', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%");
            });
        }

        $citations = $query->orderBy('due_date')->get();

        $totalAmount = (float) $citations->sum('penalty_amount');

        $byBucket = $citations
            ->groupBy(fn (Citation $c) => $this->overdueBucket($c->due_date))
            ->map(fn ($group) => [
                'count' => $group->count(),
                'amount' => (float) $group->sum('penalty_amount'),
            ])
            ->sortKeys();

        return view('reports.overdue-citations', compact('citations', 'totalAmount', 'byBucket', 'from', 'to'));
    }

    private function overdueBucket(?Carbon $dueDate): string
    {
        if ($dueDate === null) {
            return 'No due date';
        }

        $days = $dueDate->diffInDays(today());

        return match (true) {
            $days <= 7 => '1-7 days overdue',
            $days <= 30 => '8-30 days overdue',
            $days <= 60 => '31-60 days overdue',
            default => '60+ days overdue',
        };
    }
}
