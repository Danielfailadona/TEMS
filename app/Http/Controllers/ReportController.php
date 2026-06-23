<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Models\Citation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin,administrator');
    }

    public function index(): View
    {
        return view('reports.index');
    }

    public function revenue(Request $request): View
    {
        $from = $request->date('date_from', now()->startOfMonth());
        $to = $request->date('date_to', now()->endOfMonth());

        $payments = Payment::whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->with('citation.violationType')
            ->latest('paid_at')
            ->get();

        $totalRevenue = $payments->sum('amount');
        $totalCount = $payments->count();

        $byMethod = $payments->groupBy(fn ($p) => $p->online_payment_method ?? $p->payment_method->value)
            ->map(fn ($group) => ['count' => $group->count(), 'amount' => $group->sum('amount')]);

        $dailyTotals = $payments->groupBy(fn ($p) => $p->paid_at->format('Y-m-d'))
            ->map(fn ($group) => $group->sum('amount'))
            ->sortKeys();

        return view('reports.revenue', compact('payments', 'totalRevenue', 'totalCount', 'byMethod', 'dailyTotals', 'from', 'to'));
    }

    public function citations(Request $request): View
    {
        $from = $request->date('date_from', now()->startOfMonth());
        $to = $request->date('date_to', now()->endOfMonth());

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
        $from = $request->date('date_from', now()->startOfMonth());
        $to = $request->date('date_to', now()->endOfMonth());

        $enforcers = User::whereIn('role', ['enforcer', 'super_admin', 'administrator'])
            ->withCount(['issuedCitations' => fn ($q) => $q->whereBetween('issued_at', [$from, $to])])
            ->get()
            ->filter(fn ($u) => $u->issued_citations_count > 0)
            ->sortByDesc('issued_citations_count');

        return view('reports.enforcer-performance', compact('enforcers', 'from', 'to'));
    }
}
