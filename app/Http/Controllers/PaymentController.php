<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Enums\ImpoundingStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Citation;
use App\Models\ClampingRecord;
use App\Models\ImpoundingRecord;
use App\Models\Payment;
use App\Services\PaymentRecorder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::with(['citation', 'cashier', 'payable.officer']);

        // Search: receipt #, citation #, plate, driver name (plus clamping/impounding notice #, plate)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($w) use ($search) {
                $w->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('citation', function ($q) use ($search) {
                      $q->where('citation_number', 'like', "%{$search}%")
                        ->orWhere('vehicle_plate', 'like', "%{$search}%")
                        ->orWhere('driver_name', 'like', "%{$search}%");
                  })
                  ->orWhereHasMorph('payable', [ClampingRecord::class, ImpoundingRecord::class], function ($q) use ($search) {
                      $q->where('notice_number', 'like', "%{$search}%")
                        ->orWhere('vehicle_plate', 'like', "%{$search}%");
                  });
            });
        }

        // Payment category filter
        if ($request->filled('category')) {
            $category = $request->category;
            if ($category === 'clamping') {
                $query->where('payable_type', ClampingRecord::class);
            } elseif ($category === 'impounding') {
                $query->where('payable_type', ImpoundingRecord::class);
            } else {
                $query->where(fn ($q) => $q->where('payable_type', Citation::class)->orWhereNull('payable_type'));
            }
        }

        // Payment method filter
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Date range filters - apply only when both from and to are provided
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay()->setTimezone('UTC');
            $dateTo   = Carbon::parse($request->date_to)->endOfDay()->setTimezone('UTC');
            $query->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('paid_at', [$dateFrom, $dateTo])
                  ->orWhere(function ($sub) use ($dateFrom, $dateTo) {
                      $sub->whereNull('paid_at')
                          ->whereBetween('created_at', [$dateFrom, $dateTo]);
                  });
            });
        }

        // Online payments only filter
        if ($request->filled('online')) {
            $query->whereNotNull('paymongo_checkout_id');
        }

        $receipts = $query->latest('paid_at')->get()->map(fn (Payment $payment) => [
            'kind' => 'receipt',
            'category' => $payment->category(),
            'date' => $payment->paid_at ?? $payment->created_at,
            'payment' => $payment,
            'payable' => null,
        ]);

        $pending = $this->pendingRows($request);

        $all = $receipts->concat($pending)->sortByDesc('date')->values();

        $perPage = 9;
        $page = max(1, (int) $request->input('page', 1));
        $items = $all->forPage($page, $perPage)->values();

        $grid = new LengthAwarePaginator(
            $items,
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('payments.index', [
            'grid' => $grid,
        ]);
    }

    /**
     * Outstanding citation, clamping and impounding tickets, normalised into
     * the same row shape as receipts so both can render in one grid.
     */
    protected function pendingRows(Request $request): Collection
    {
        $category = $request->input('category');
        $search = trim((string) $request->input('search', ''));

        $rows = collect();

        if ($category === null || $category === '' || $category === 'citation') {
            $q = $this->awaitingCitationsQuery()->with('violationType');

            if ($search !== '') {
                $term = '%'.mb_strtolower($search).'%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(citation_number) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(driver_name) LIKE ?', [$term]));
            }

            $rows = $rows->concat($q->get()->map(fn ($c) => [
                'kind' => 'pending',
                'category' => 'citation',
                'date' => $c->issued_at ?? $c->created_at,
                'payment' => null,
                'payable' => $c,
            ]));
        }

        foreach ([
            'clamping' => [$this->awaitingClampingQuery(), 'clamped_at'],
            'impounding' => [$this->awaitingImpoundingQuery(), 'impounded_at'],
        ] as $key => [$query, $dateColumn]) {
            if ($category !== null && $category !== '' && $category !== $key) {
                continue;
            }

            $q = $query->with(['officer', 'citation']);

            if ($search !== '') {
                $term = '%'.mb_strtolower($search).'%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(notice_number) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term])
                    ->orWhereHas('officer', fn ($o) => $o->whereRaw('LOWER(name) LIKE ?', [$term])));
            }

            $rows = $rows->concat($q->get()->map(fn ($m) => [
                'kind' => 'pending',
                'category' => $key,
                'date' => $m->{$dateColumn} ?? $m->created_at,
                'payment' => null,
                'payable' => $m,
            ]));
        }

        return $rows;
    }

    protected function awaitingCitationsQuery()
    {
        return Citation::query()
            ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue, CitationStatus::Clamped])
            ->whereDoesntHave('payment', fn ($q) => $q->whereNotNull('paid_at'));
    }

    protected function awaitingClampingQuery()
    {
        return ClampingRecord::query()
            ->where('status', ClampingStatus::AwaitingPayment)
            ->whereDoesntHave('payments', fn ($q) => $q->whereNotNull('paid_at'));
    }

    protected function awaitingImpoundingQuery()
    {
        return ImpoundingRecord::query()
            ->whereIn('status', [ImpoundingStatus::Impounded, ImpoundingStatus::AwaitingPayment])
            ->whereDoesntHave('payments', fn ($q) => $q->whereNotNull('paid_at'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Payment::class);

        $filter = in_array($request->input('category'), ['citation', 'clamping', 'impounding'], true)
            ? $request->input('category')
            : null;
        $lookup = trim((string) $request->input('lookup', ''));

        $record = null;
        $suggestions = collect();
        $category = $filter ?? 'citation'; // default for form when no filter

        // If a specific record ID is provided, it determines the category
        if ($request->filled('citation_id') && ($filter === null || $filter === 'citation')) {
            $record = Citation::with(['violationType', 'payment'])->find($request->citation_id);
            $category = 'citation';
        } elseif ($request->filled('clamping_id') && ($filter === null || $filter === 'clamping')) {
            $record = ClampingRecord::with(['officer', 'citation'])->find($request->clamping_id);
            $category = 'clamping';
        } elseif ($request->filled('impounding_id') && ($filter === null || $filter === 'impounding')) {
            $record = ImpoundingRecord::with(['officer', 'citation'])->find($request->impounding_id);
            $category = 'impounding';
        } elseif ($lookup !== '') {
            // Search across all categories (or filtered) for exact match first, then best match
            $term = '%'.mb_strtolower($lookup).'%';

            $candidates = collect();

            if ($filter === null || $filter === 'citation') {
                $c = Citation::with(['violationType', 'payment'])
                    ->where(fn ($q) => $q->whereRaw('LOWER(citation_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term]))
                    ->orderByRaw('citation_number = ? desc', [$lookup])
                    ->latest('issued_at')
                    ->first();
                if ($c) {
                    $candidates->push(['record' => $c, 'category' => 'citation', 'priority' => 1]);
                }
            }

            if ($filter === null || $filter === 'clamping') {
                $c = ClampingRecord::with(['officer', 'citation'])
                    ->where(fn ($q) => $q->whereRaw('LOWER(notice_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term]))
                    ->orderByRaw('notice_number = ? desc', [$lookup])
                    ->latest('clamped_at')
                    ->first();
                if ($c) {
                    $candidates->push(['record' => $c, 'category' => 'clamping', 'priority' => 1]);
                }
            }

            if ($filter === null || $filter === 'impounding') {
                $c = ImpoundingRecord::with(['officer', 'citation'])
                    ->where(fn ($q) => $q->whereRaw('LOWER(notice_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term]))
                    ->orderByRaw('notice_number = ? desc', [$lookup])
                    ->latest('impounded_at')
                    ->first();
                if ($c) {
                    $candidates->push(['record' => $c, 'category' => 'impounding', 'priority' => 1]);
                }
            }

            if ($candidates->isNotEmpty()) {
                $best = $candidates->sortBy('priority')->first();
                $record = $best['record'];
                $category = $best['category'];
            }
        }

        // Build suggestions when no exact record found
        if (! $record) {
            $suggestions = collect();

            if ($filter === null || $filter === 'citation') {
                $q = $this->awaitingCitationsQuery()->with('violationType');

                if ($lookup !== '') {
                    $term = '%'.mb_strtolower($lookup).'%';
                    $q->where(fn ($w) => $w->whereRaw('LOWER(citation_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(driver_name) LIKE ?', [$term]));
                }

                $suggestions = $suggestions->concat($q->get()->map(fn ($c) => [
                    'category' => 'citation',
                    'record' => $c,
                ]));
            }

            if ($filter === null || $filter === 'clamping') {
                $q = $this->awaitingClampingQuery()->with(['officer', 'citation']);

                if ($lookup !== '') {
                    $term = '%'.mb_strtolower($lookup).'%';
                    $q->where(fn ($w) => $w->whereRaw('LOWER(notice_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term])
                        ->orWhereHas('officer', fn ($o) => $o->whereRaw('LOWER(name) LIKE ?', [$term])));
                }

                $suggestions = $suggestions->concat($q->get()->map(fn ($c) => [
                    'category' => 'clamping',
                    'record' => $c,
                ]));
            }

            if ($filter === null || $filter === 'impounding') {
                $q = $this->awaitingImpoundingQuery()->with(['officer', 'citation']);

                if ($lookup !== '') {
                    $term = '%'.mb_strtolower($lookup).'%';
                    $q->where(fn ($w) => $w->whereRaw('LOWER(notice_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term])
                        ->orWhereHas('officer', fn ($o) => $o->whereRaw('LOWER(name) LIKE ?', [$term])));
                }

                $suggestions = $suggestions->concat($q->get()->map(fn ($c) => [
                    'category' => 'impounding',
                    'record' => $c,
                ]));
            }

            // Sort by date descending (newest first) when filter is null
            if ($filter === null) {
                $suggestions = $suggestions->sortByDesc(fn ($s) => $s['record']->{$s['category'] === 'citation' ? 'issued_at' : ($s['category'] === 'clamping' ? 'clamped_at' : 'impounded_at')} ?? $s['record']->created_at)->values();
            }
        }

        return view('payments.create', [
            'category' => $category,
            'filter' => $filter,
            'record' => $record,
            'paymentMethods' => PaymentMethod::cases(),
            'suggestions' => $suggestions,
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $category = $request->input('category');
        $data = $request->safe()->only(['amount', 'payment_method', 'reference_number', 'notes']);
        $recorder = app(PaymentRecorder::class);
        $cashierId = auth()->id();

        if ($category === 'clamping') {
            $clamping = ClampingRecord::with(['officer', 'citation'])->findOrFail($request->clamping_id);

            if ($clamping->status === ClampingStatus::Released) {
                return back()->withErrors(['clamping_id' => 'This vehicle has already been released.'])->withInput();
            }

            if ($clamping->payments()->whereNotNull('paid_at')->exists()) {
                return back()->withErrors(['clamping_id' => 'This clamping notice has already been paid.'])->withInput();
            }

            $payment = $recorder->recordClamping($clamping, $data->toArray(), $cashierId);
        } elseif ($category === 'impounding') {
            $impounding = ImpoundingRecord::with(['officer', 'citation'])->findOrFail($request->impounding_id);

            if ($impounding->status === ImpoundingStatus::Released) {
                return back()->withErrors(['impounding_id' => 'This vehicle has already been released.'])->withInput();
            }

            if ($impounding->payments()->whereNotNull('paid_at')->exists()) {
                return back()->withErrors(['impounding_id' => 'This impounding notice has already been paid.'])->withInput();
            }

            $payment = $recorder->recordImpounding($impounding, $data->toArray(), $cashierId);
        } else {
            $citation = Citation::with('payment')->findOrFail($request->citation_id);

            if ($citation->payment && $citation->payment->paid_at) {
                return back()->withErrors(['citation_id' => 'This citation has already been paid.'])->withInput();
            }

            // A pending/abandoned online payment may be replaced by this manual payment.
            if ($citation->payment) {
                $citation->payment->delete();
            }

            if (! $citation->isPayable()) {
                return back()->withErrors(['citation_id' => 'This citation is not eligible for payment.'])->withInput();
            }

            $payment = $recorder->recordCitation($citation, [
                ...$data->toArray(),
                'amount' => $citation->penalty_amount,
            ], $cashierId);
        }

        return redirect()->route('payments.show', $payment)->with('success', 'Payment recorded successfully.');
    }

public function edit(Payment $payment): View
    {
        $this->authorize('update', $payment);

        $payment->load(['citation.violationType', 'cashier', 'payable.officer']);

        return view('payments.edit', [
            'payment' => $payment,
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $validated = $request->validate([
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment->update($validated);

        return redirect()->route('payments.show', $payment)->with('success', 'Payment updated successfully.');
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['citation.violationType', 'cashier', 'payable.officer']);

        return view('payments.show', compact('payment'));
    }

    public function printReceipt(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['citation.violationType', 'cashier', 'payable.officer']);

        return view('payments.print', compact('payment'));
    }
}
