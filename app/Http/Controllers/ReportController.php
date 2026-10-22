<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\PaymentMethod;
use App\Models\Citation;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin,administrator')->except(['overdueCitations']);
        $this->middleware('role:super_admin,administrator,cashier')->only(['overdueCitations']);
    }

    public function index(): View
    {
        return view('reports.index');
    }

    public function revenue(Request $request): View
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->input('date_from')) : now()->startOfMonth();
        $to = $request->filled('date_to') ? Carbon::parse($request->input('date_to')) : now()->endOfMonth();

        $payments = Payment::whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->with('citation.violationType')
            ->latest('paid_at')
            ->get();

        $totalRevenue = $payments->sum('amount');
        $totalCount = $payments->count();

        $byMethod = $payments->groupBy(fn ($p) => $p->payment_method === PaymentMethod::Other && $p->isOnlinePayment()
            ? ($p->online_payment_method ?? 'online')
            : $p->payment_method->value)
            ->map(fn ($group) => ['count' => $group->count(), 'amount' => $group->sum('amount')]);

        $dailyTotals = $payments->groupBy(fn ($p) => $p->paid_at->format('Y-m-d'))
            ->map(fn ($group) => $group->sum('amount'))
            ->sortKeys();

        return view('reports.revenue', compact('payments', 'totalRevenue', 'totalCount', 'byMethod', 'dailyTotals', 'from', 'to'));
    }

    public function citations(Request $request): View
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->input('date_from')) : now()->startOfMonth();
        $to = $request->filled('date_to') ? Carbon::parse($request->input('date_to')) : now()->endOfMonth();

        $citations = Citation::whereBetween('issued_at', [$from, $to])
            ->with(['violationType', 'enforcer'])
            ->latest('issued_at')
            ->get();

        $totalCount = $citations->count();

        $byStatus = $citations->groupBy(fn ($c) => $c->status->label())
            ->map(fn ($group) => $group->count());

        $byViolationType = $citations->groupBy(fn ($c) => $c->violationType->name)
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $byEnforcer = $citations->groupBy(fn ($c) => $c->enforcer->name)
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        return view('reports.citations', compact('citations', 'totalCount', 'byStatus', 'byViolationType', 'byEnforcer', 'from', 'to'));
    }

    public function enforcerPerformance(Request $request): View
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->input('date_from')) : now()->startOfMonth();
        $to = $request->filled('date_to') ? Carbon::parse($request->input('date_to')) : now()->endOfMonth();

        $enforcers = User::whereIn('role', ['enforcer', 'super_admin', 'administrator'])
            ->withCount(['issuedCitations' => fn ($q) => $q->whereBetween('issued_at', [$from, $to])])
            ->get()
            ->filter(fn ($u) => $u->issued_citations_count > 0)
            ->sortByDesc('issued_citations_count');

        return view('reports.enforcer-performance', compact('enforcers', 'from', 'to'));
    }

    /**
     * Unpaid citations grouped by how far past their due date they are. Built
     * for cashiers to prioritise collection follow-up.
     */
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
