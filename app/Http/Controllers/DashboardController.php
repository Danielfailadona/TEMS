<?php

namespace App\Http\Controllers;

use App\Enums\AppealStatus;
use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Enums\ImpoundingStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Appeal;
use App\Models\Citation;
use App\Models\ClampingRecord;
use App\Models\CitizenReport;
use App\Models\ImpoundingRecord;
use App\Models\Payment;
use App\Models\ViolationType;
use App\Models\Zone;
use App\Models\User;
use App\Services\UserKpiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private UserKpiService $kpi)
    {
    }

public function index(Request $request): View
    {
        $user = auth()->user();

        if ($user->isRole(Role::VehicleOwner)) {
            return $this->ownerDashboard($user);
        }

        if ($user->isRole(Role::FrontDesk)) {
            return $this->frontDeskDashboard($user);
        }

        if ($user->isCashier()) {
            return $this->cashierDashboard($user);
        }

        // KPIs
        $stats = [
            'total_citations' => Citation::count(),
            'unpaid_citations' => Citation::whereIn('status', [
                CitationStatus::Issued,
                CitationStatus::Overdue,
                CitationStatus::Clamped,
                CitationStatus::Impounded,
            ])->count(),
            'revenue_today' => Payment::whereDate('paid_at', today())->sum('amount'),
            'active_clamps' => ClampingRecord::where('status', ClampingStatus::AwaitingPayment)->count(),
            'pending_appeals' => Appeal::whereIn('status', [AppealStatus::Submitted, AppealStatus::UnderReview])->count(),
            'reports_resolved_today' => ClampingRecord::where('status', ClampingStatus::Released)
                ->whereDate('updated_at', today())->count()
                + ImpoundingRecord::where('status', ImpoundingStatus::Released)
                ->whereDate('updated_at', today())->count()
                + CitizenReport::whereIn('status', [ReportStatus::Resolved, ReportStatus::Accepted])
                ->whereDate('resolved_at', today())->count(),
            'payments_recorded_today' => Payment::whereDate('paid_at', today())->count(),
            'total_revenue_today' => Payment::whereDate('paid_at', today())->sum('amount'),
        ];

        // Week-over-week trends
        $trends = [
            'total_citations' => $this->trend(
                fn () => Citation::whereBetween('created_at', [now()->subWeek(), now()])->count(),
                fn () => Citation::whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count(),
            ),
            'unpaid_citations' => $this->trend(
                fn () => Citation::whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped, CitationStatus::Impounded])
                    ->whereBetween('created_at', [now()->subWeek(), now()])->count(),
                fn () => Citation::whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped, CitationStatus::Impounded])
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
            'reports_resolved_today' => $this->trend(
                fn () => ClampingRecord::where('status', ClampingStatus::Released)
                    ->whereDate('updated_at', '>=', now()->subWeek())->count()
                    + ImpoundingRecord::where('status', ImpoundingStatus::Released)
                    ->whereDate('updated_at', '>=', now()->subWeek())->count()
                    + CitizenReport::whereIn('status', [ReportStatus::Resolved, ReportStatus::Accepted])
                    ->whereDate('resolved_at', '>=', now()->subWeek())->count(),
                fn () => ClampingRecord::where('status', ClampingStatus::Released)
                    ->whereBetween('updated_at', [now()->subWeeks(2), now()->subWeek()])->count()
                    + ImpoundingRecord::where('status', ImpoundingStatus::Released)
                    ->whereBetween('updated_at', [now()->subWeeks(2), now()->subWeek()])->count()
                    + CitizenReport::whereIn('status', [ReportStatus::Resolved, ReportStatus::Accepted])
                    ->whereBetween('resolved_at', [now()->subWeeks(2), now()->subWeek()])->count(),
            ),
            'payments_recorded_today' => $this->trend(
                fn () => Payment::whereBetween('paid_at', [now()->subWeek(), now()])->count(),
                fn () => Payment::whereBetween('paid_at', [now()->subWeeks(2), now()->subWeek()])->count(),
            ),
            'total_revenue_today' => $this->trend(
                fn () => Payment::whereBetween('paid_at', [now()->subWeek(), now()])->sum('amount'),
                fn () => Payment::whereBetween('paid_at', [now()->subWeeks(2), now()->subWeek()])->sum('amount'),
            ),
        ];

        // Analytics: unified categorized bar chart (Tickets / Payments / Appeals)
        // with a selectable range: day | week | month | year.
        $range = in_array($request->input('range'), ['day', 'week', 'month', 'year'], true)
            ? $request->input('range')
            : 'month';

        $bucketSpec = match ($range) {
            'day' => ['count' => 14, 'label' => 'M j', 'unit' => 'day'],
            'week' => ['count' => 12, 'label' => 'M j', 'unit' => 'week'],
            'year' => ['count' => 5, 'label' => 'Y', 'unit' => 'year'],
            default => ['count' => 12, 'label' => 'M y', 'unit' => 'month'],
        };

        $buckets = [];
        $cursor = match ($bucketSpec['unit']) {
            'day' => now()->startOfDay()->subDays($bucketSpec['count'] - 1),
            'week' => now()->startOfWeek()->subWeeks($bucketSpec['count'] - 1),
            'year' => now()->startOfYear()->subYears($bucketSpec['count'] - 1),
            default => now()->startOfMonth()->subMonths($bucketSpec['count'] - 1),
        };
        for ($i = 0; $i < $bucketSpec['count']; $i++) {
            $start = match ($bucketSpec['unit']) {
                'day' => $cursor->copy()->addDays($i)->startOfDay(),
                'week' => $cursor->copy()->addWeeks($i)->startOfWeek(),
                'year' => $cursor->copy()->addYears($i)->startOfYear(),
                default => $cursor->copy()->addMonths($i)->startOfMonth(),
            };
            $end = match ($bucketSpec['unit']) {
                'day' => $start->copy()->endOfDay(),
                'week' => $start->copy()->endOfWeek(),
                'year' => $start->copy()->endOfYear(),
                default => $start->copy()->endOfMonth(),
            };
            $buckets[] = [
                'label' => $start->format($bucketSpec['label']),
                'start' => $start,
                'end' => $end,
            ];
        }

        $chartData = [
            'labels' => array_column($buckets, 'label'),
            'tickets' => [],
            'payments_paid' => [],
            'payments_unpaid' => [],
            'appeals' => [],
        ];

        foreach ($buckets as $bucket) {
            $chartData['tickets'][] = Citation::whereBetween('issued_at', [$bucket['start'], $bucket['end']])->count();

            $chartData['payments_paid'][] = Payment::whereNotNull('paid_at')
                ->whereBetween('paid_at', [$bucket['start'], $bucket['end']])->count();

            // Unpaid = citations issued in this bucket that are still outstanding.
            $chartData['payments_unpaid'][] = Citation::whereBetween('issued_at', [$bucket['start'], $bucket['end']])
                ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped, CitationStatus::Impounded])
                ->count();

            $chartData['appeals'][] = Appeal::whereBetween('submitted_at', [$bucket['start'], $bucket['end']])->count();
        }

        // Analytics: Top Violation Types
        // All violation types are listed in a fixed (definition) order; rows keep their
        // position regardless of count, and zero-count types still show an empty bar.
        $topViolations = ViolationType::withCount(['citations' => fn ($q) => $q->where('issued_at', '>=', now()->subMonths(3))])
            ->orderBy('id')
            ->get()
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
            'citizen_reports' => CitizenReport::where('status', 'pending')->count(),
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

        $contribution = $this->kpi->contribution($user);

        return view('dashboard.index', compact(
            'stats',
            'trends',
            'chartData',
            'range',
            'topViolations',
            'recentActivity',
            'pendingQueue',
            'zoneMapData',
            'contribution',
        ));
    }

    /**
     * Focused cashier workspace: recording payments, printing receipts (via
     * archives) and a personal contribution summary.
     */
    private function cashierDashboard($user): View
    {
        $contribution = $this->kpi->contribution($user);

        $stats = [
            'collected_today' => Payment::where('cashier_id', $user->id)
                ->whereDate('paid_at', today())->sum('amount'),
            'payments_today' => Payment::where('cashier_id', $user->id)
                ->whereDate('paid_at', today())->count(),
            'unpaid_citations' => Citation::whereIn('status', [
                CitationStatus::Issued,
                CitationStatus::Overdue,
                CitationStatus::Clamped,
                CitationStatus::Impounded,
            ])->count(),
            'overdue_citations' => Citation::where('status', CitationStatus::Overdue)->count(),
            'active_clamps' => ClampingRecord::where('status', ClampingStatus::AwaitingPayment)->count(),
        ];

        $recentPayments = Payment::with(['citation', 'payable'])
            ->where('cashier_id', $user->id)
            ->latest('paid_at')
            ->take(8)
            ->get();

        return view('dashboard.cashier', compact('stats', 'contribution', 'recentPayments'));
    }

    protected function ownerDashboard($user): View
    {
        $stats = [
            'my_citations' => Citation::where('issued_by', $user->id)->count(),
            'unpaid' => Citation::where('issued_by', $user->id)
                ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped, CitationStatus::Impounded])
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

    private function frontDeskDashboard($user): View
    {
        // KPIs (same as main dashboard for consistency)
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
                fn () => Citation::whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped, CitationStatus::Impounded])
                    ->whereBetween('created_at', [now()->subWeek(), now()])->count(),
                fn () => Citation::whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped, CitationStatus::Impounded])
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
            'reports_resolved_today' => $this->trend(
                fn () => ClampingRecord::where('status', ClampingStatus::Released)
                    ->whereDate('updated_at', '>=', now()->subWeek())->count()
                    + ImpoundingRecord::where('status', ImpoundingStatus::Released)
                    ->whereDate('updated_at', '>=', now()->subWeek())->count()
                    + CitizenReport::whereIn('status', [ReportStatus::Resolved, ReportStatus::Accepted])
                    ->whereDate('resolved_at', '>=', now()->subWeek())->count(),
fn () => ClampingRecord::where('status', ClampingStatus::Released)
                    ->whereBetween('updated_at', [now()->subWeeks(2), now()->subWeek()])->count()
                    + ImpoundingRecord::where('status', ImpoundingStatus::Released)
                    ->whereBetween('updated_at', [now()->subWeeks(2), now()->subWeek()])->count()
                    + CitizenReport::whereIn('status', [ReportStatus::Resolved, ReportStatus::Accepted])
                    ->whereBetween('resolved_at', [now()->subWeeks(2), now()->subWeek()])->count(),
            ),
            'payments_recorded_today' => $this->trend(
                fn () => Payment::whereBetween('paid_at', [now()->subWeek(), now()])->count(),
                fn () => Payment::whereBetween('paid_at', [now()->subWeeks(2), now()->subWeek()])->count(),
            ),
            'total_revenue_today' => $this->trend(
                fn () => Payment::whereBetween('paid_at', [now()->subWeek(), now()])->sum('amount'),
                fn () => Payment::whereBetween('paid_at', [now()->subWeeks(2), now()->subWeek()])->sum('amount'),
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

        // Recent Activity (citations + payments + clamps - front desk relevant)
        $recentCitations = Citation::with(['violationType', 'enforcer'])
            ->latest('issued_at')->take(5)->get();

        $recentPayments = Payment::with('citation')
            ->latest('paid_at')->take(5)->get();

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
            ...$activeClampRecords->map(fn (ClampingRecord $clamp) => [
                'type' => 'clamp', 'icon' => 'bi-lock',
                'title' => 'Vehicle Clamped',
                'description' => $clamp->vehicle_plate ?? 'N/A',
                'meta' => 'Officer: '.($clamp->officer?->name ?? 'N/A').' · '.($clamp->location ?? ''),
                'timestamp' => $clamp->clamped_at,
                'timestamp_label' => $clamp->clamped_at?->diffForHumans(),
            ]),
        ])->sortByDesc('timestamp')->take(8)->values();

        // Pending Work Queue - front desk relevant (waiting releases)
        $pendingQueue = [
            'waiting_releases' => ClampingRecord::where('status', ClampingStatus::WaitingRelease)->count(),
        ];

        return view('dashboard.frontdesk', compact(
            'stats',
            'trends',
            'citationsByMonth',
            'revenueByMonth',
            'appealsByMonth',
            'topViolations',
            'recentActivity',
            'pendingQueue',
        ));
    }
}
