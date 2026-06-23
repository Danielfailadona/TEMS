<?php

namespace App\Http\Controllers;

use App\Enums\AppealStatus;
use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Enums\Role;
use App\Models\Appeal;
use App\Models\Citation;
use App\Models\ClampingRecord;
use App\Models\ClampingRequest;
use App\Models\Payment;
use App\Models\ViolationType;
use App\Models\Zone;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->isRole(Role::VehicleOwner)) {
            return $this->ownerDashboard($user);
        }

        // KPIs
        $stats = [
            'total_citations' => Citation::count(),
            'unpaid_citations' => Citation::whereIn('status', [
                CitationStatus::Issued,
                CitationStatus::Overdue,
                CitationStatus::Clamped,
            ])->count(),
            'revenue_today' => Payment::whereDate('paid_at', today())->sum('amount'),
            'active_clamps' => ClampingRecord::where('status', ClampingStatus::AwaitingPayment)->count(),
            'pending_appeals' => Appeal::whereIn('status', [AppealStatus::Submitted, AppealStatus::UnderReview])->count(),
        ];

        // Week-over-week trends
        $trends = [
            'total_citations' => $this->trend(
                fn () => Citation::whereBetween('created_at', [now()->subWeek(), now()])->count(),
                fn () => Citation::whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count(),
            ),
            'unpaid_citations' => $this->trend(
                fn () => Citation::whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped])
                    ->whereBetween('created_at', [now()->subWeek(), now()])->count(),
                fn () => Citation::whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped])
                    ->whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count(),
            ),
            'revenue_today' => $this->trend(
                fn () => Payment::whereBetween('paid_at', [now()->subWeek(), now()])->sum('amount'),
                fn () => Payment::whereBetween('paid_at', [now()->subWeeks(2), now()->subWeek()])->sum('amount'),
            ),
            'active_clamps' => $this->trend(
                fn () => ClampingRecord::where('status', ClampingStatus::AwaitingPayment)
                    ->whereBetween('created_at', [now()->subWeek(), now()])->count(),
                fn () => ClampingRecord::where('status', ClampingStatus::AwaitingPayment)
                    ->whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count(),
            ),
            'pending_appeals' => $this->trend(
                fn () => Appeal::whereIn('status', [AppealStatus::Submitted, AppealStatus::UnderReview])
                    ->whereBetween('created_at', [now()->subWeek(), now()])->count(),
                fn () => Appeal::whereIn('status', [AppealStatus::Submitted, AppealStatus::UnderReview])
                    ->whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count(),
            ),
        ];

        // Analytics: Citations by Month
        $citationsByMonth = Citation::query()
            ->where('issued_at', '>=', now()->subMonths(6))
            ->get()
            ->groupBy(fn (Citation $c) => $c->issued_at->format('Y-m'))
            ->map->count()
            ->sortKeys();

        // Analytics: Revenue by Month
        $revenueByMonth = Payment::query()
            ->where('paid_at', '>=', now()->subMonths(6))
            ->get()
            ->groupBy(fn (Payment $p) => $p->paid_at->format('Y-m'))
            ->map(fn ($group) => $group->sum('amount'))
            ->sortKeys();

        // Analytics: Appeals by Month
        $appealsByMonth = Appeal::query()
            ->where('submitted_at', '>=', now()->subMonths(6))
            ->get()
            ->groupBy(fn (Appeal $a) => $a->submitted_at->format('Y-m'))
            ->map->count()
            ->sortKeys();

        // Analytics: Top Violation Types
        $topViolations = ViolationType::withCount(['citations' => fn ($q) => $q->where('issued_at', '>=', now()->subMonths(3))])
            ->orderByDesc('citations_count')
            ->take(10)
            ->get()
            ->filter(fn ($v) => $v->citations_count > 0)
            ->take(5)
            ->map(fn ($v) => ['name' => $v->name, 'count' => (int) $v->citations_count])
            ->values();

        // Recent activity
        $recentCitations = Citation::with(['violationType', 'enforcer'])
            ->latest('issued_at')->take(5)->get();

        $recentPayments = Payment::with('citation')
            ->latest('paid_at')->take(5)->get();

        $pendingAppeals = Appeal::with('citation')
            ->latest('submitted_at')->take(5)->get();

        $activeClampRecords = ClampingRecord::with('citation')
            ->where('status', ClampingStatus::AwaitingPayment)
            ->latest('clamped_at')->take(5)->get();

        $recentActivity = collect([
            ...$recentCitations->map(fn (Citation $citation) => [
                'type' => 'citation', 'icon' => 'bi-receipt',
                'title' => 'Citation Issued',
                'description' => $citation->driver_name,
                'meta' => 'Vehicle: '.($citation->vehicle_plate ?? 'N/A').' · Officer: '.($citation->enforcer?->name ?? 'N/A'),
                'timestamp' => $citation->issued_at,
                'timestamp_label' => $citation->issued_at?->diffForHumans(),
            ]),
            ...$recentPayments->map(fn (Payment $payment) => [
                'type' => 'payment', 'icon' => 'bi-cash-stack',
                'title' => 'Payment Received',
                'description' => 'Receipt '.($payment->receipt_number ?? 'N/A'),
                'meta' => '₱'.number_format($payment->amount, 2).' · '.($payment->citation?->citation_number ?? 'N/A'),
                'timestamp' => $payment->paid_at,
                'timestamp_label' => $payment->paid_at?->diffForHumans(),
            ]),
            ...$pendingAppeals->map(fn (Appeal $appeal) => [
                'type' => 'appeal', 'icon' => 'bi-chat-square-text',
                'title' => 'Appeal Submitted',
                'description' => $appeal->citation?->citation_number ?? 'N/A',
                'meta' => $appeal->reason ? 'Reason: '.$appeal->reason : '',
                'timestamp' => $appeal->submitted_at,
                'timestamp_label' => $appeal->submitted_at?->diffForHumans(),
            ]),
            ...$activeClampRecords->map(fn (ClampingRecord $clamp) => [
                'type' => 'clamp', 'icon' => 'bi-lock',
                'title' => 'Vehicle Clamped',
                'description' => $clamp->vehicle_plate ?? 'N/A',
                'meta' => 'Officer: '.($clamp->officer?->name ?? 'N/A').' · '.($clamp->location ?? ''),
                'timestamp' => $clamp->clamped_at,
                'timestamp_label' => $clamp->clamped_at?->diffForHumans(),
            ]),
        ])->sortByDesc('timestamp')->take(8)->values();

        // Pending Work Queue
        $pendingQueue = [
            'appeals' => Appeal::whereIn('status', [AppealStatus::Submitted, AppealStatus::UnderReview])->count(),
            'clamping_requests' => ClampingRequest::where('status', 'pending')->count(),
            'account_approvals' => User::where('account_status', 'pending')->count(),
        ];

        // Zone map data
        $zoneMapData = Zone::with('team')->where('is_active', true)->get()->map(fn ($z) => [
            'id' => $z->id,
            'name' => $z->name,
            'lat' => $z->center_latitude,
            'lng' => $z->center_longitude,
            'radius' => (int) $z->radius_m,
            'team_name' => $z->team?->name,
            'is_active' => $z->is_active,
        ]);

        return view('dashboard.index', compact(
            'stats',
            'trends',
            'citationsByMonth',
            'revenueByMonth',
            'appealsByMonth',
            'topViolations',
            'recentActivity',
            'pendingQueue',
            'zoneMapData',
        ));
    }

    protected function ownerDashboard($user): View
    {
        $stats = [
            'my_citations' => Citation::where('issued_by', $user->id)->count(),
            'unpaid' => Citation::where('issued_by', $user->id)
                ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped])
                ->count(),
            'active_clamps' => ClampingRecord::where('clamped_by', $user->id)
                ->where('status', ClampingStatus::AwaitingPayment)
                ->count(),
        ];

        $recentCitations = Citation::with(['violationType'])
            ->where('issued_by', $user->id)
            ->latest('issued_at')
            ->limit(5)
            ->get();

        return view('owner.dashboard', compact('stats', 'recentCitations'));
    }

    private function trend(callable $current, callable $previous): array
    {
        $cur = $current();
        $prev = $previous();
        $diff = $cur - $prev;
        $percent = $prev > 0 ? round(($diff / $prev) * 100) : ($cur > 0 ? 100 : 0);

        return [
            'value' => $cur,
            'previous' => $prev,
            'diff' => $diff,
            'percent' => $percent,
            'direction' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat'),
        ];
    }
}
