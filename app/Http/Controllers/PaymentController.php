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
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        if ($request->input('view') === 'payables') {
            return $this->awaitingPayments($request);
        }

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

        // Date range filters - use COALESCE(paid_at, created_at) to include pending payments
        // Convert user input (PHT) to UTC for comparison
        if ($request->filled('date_from')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay()->setTimezone('UTC');
            $query->where(function ($q) use ($dateFrom) {
                $q->where('paid_at', '>=', $dateFrom)
                  ->orWhere(function ($sub) use ($dateFrom) {
                      $sub->whereNull('paid_at')
                          ->where('created_at', '>=', $dateFrom);
                  });
            });
        }
        if ($request->filled('date_to')) {
            $dateTo = Carbon::parse($request->date_to)->endOfDay()->setTimezone('UTC');
            $query->where(function ($q) use ($dateTo) {
                $q->where('paid_at', '<=', $dateTo)
                  ->orWhere(function ($sub) use ($dateTo) {
                      $sub->whereNull('paid_at')
                          ->where('created_at', '<=', $dateTo);
                  });
            });
        }

        // Online payments only filter
        if ($request->filled('online')) {
            $query->whereNotNull('paymongo_checkout_id');
        }

        $payments = $query->latest('paid_at')->paginate(6)->withQueryString();

        return view('payments.index', [
            'viewMode' => 'receipts',
            'payments' => $payments,
        ]);
    }

    /**
     * Tickets (citations, clamping, impounding) that are still awaiting payment.
     */
    protected function awaitingPayments(Request $request): View
    {
        $category = in_array($request->input('category'), ['citation', 'clamping', 'impounding'], true)
            ? $request->input('category')
            : 'citation';

        $counts = [
            'citation' => $this->awaitingCitationsQuery()->count(),
            'clamping' => $this->awaitingClampingQuery()->count(),
            'impounding' => $this->awaitingImpoundingQuery()->count(),
        ];

        $search = trim((string) $request->input('search', ''));

        if ($category === 'clamping') {
            $query = $this->awaitingClampingQuery()->with(['officer', 'citation']);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(notice_number) LIKE ?', ['%'.mb_strtolower($search).'%'])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', ['%'.mb_strtolower($search).'%'])
                        ->orWhereHas('officer', fn ($o) => $o->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%']));
                });
            }

            $payables = $query->latest('clamped_at')->paginate(9)->withQueryString();
        } elseif ($category === 'impounding') {
            $query = $this->awaitingImpoundingQuery()->with(['officer', 'citation']);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(notice_number) LIKE ?', ['%'.mb_strtolower($search).'%'])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', ['%'.mb_strtolower($search).'%'])
                        ->orWhereHas('officer', fn ($o) => $o->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%']));
                });
            }

            $payables = $query->latest('impounded_at')->paginate(9)->withQueryString();
        } else {
            $query = $this->awaitingCitationsQuery()->with('violationType');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(citation_number) LIKE ?', ['%'.mb_strtolower($search).'%'])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', ['%'.mb_strtolower($search).'%'])
                        ->orWhereRaw('LOWER(driver_name) LIKE ?', ['%'.mb_strtolower($search).'%']);
                });
            }

            $payables = $query->latest('issued_at')->paginate(9)->withQueryString();
        }

        return view('payments.index', [
            'viewMode' => 'payables',
            'payableCategory' => $category,
            'payableCounts' => $counts,
            'payables' => $payables,
        ]);
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

        $category = in_array($request->input('category'), ['citation', 'clamping', 'impounding'], true)
            ? $request->input('category')
            : 'citation';
        $lookup = trim((string) $request->input('lookup', ''));

        $record = null;
        $suggestions = collect();

        if ($category === 'clamping') {
            if ($request->filled('clamping_id')) {
                $record = ClampingRecord::with(['officer', 'citation'])->find($request->clamping_id);
            } elseif ($lookup !== '') {
                $record = ClampingRecord::with(['officer', 'citation'])
                    ->where(fn ($q) => $q->whereRaw('LOWER(notice_number) LIKE ?', ['%'.mb_strtolower($lookup).'%'])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', ['%'.mb_strtolower($lookup).'%']))
                    ->orderByRaw('notice_number = ? desc', [$lookup])
                    ->latest('clamped_at')
                    ->first();
            }

            if (! $record) {
                $suggestions = $this->awaitingClampingQuery()->with(['officer'])
                    ->when($lookup !== '', fn ($q) => $q->where(fn ($inner) => $inner
                        ->whereRaw('LOWER(notice_number) LIKE ?', ['%'.mb_strtolower($lookup).'%'])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', ['%'.mb_strtolower($lookup).'%'])))
                    ->latest('clamped_at')
                    ->limit(10)
                    ->get();
            }
        } elseif ($category === 'impounding') {
            if ($request->filled('impounding_id')) {
                $record = ImpoundingRecord::with(['officer', 'citation'])->find($request->impounding_id);
            } elseif ($lookup !== '') {
                $record = ImpoundingRecord::with(['officer', 'citation'])
                    ->where(fn ($q) => $q->whereRaw('LOWER(notice_number) LIKE ?', ['%'.mb_strtolower($lookup).'%'])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', ['%'.mb_strtolower($lookup).'%']))
                    ->orderByRaw('notice_number = ? desc', [$lookup])
                    ->latest('impounded_at')
                    ->first();
            }

            if (! $record) {
                $suggestions = $this->awaitingImpoundingQuery()->with(['officer'])
                    ->when($lookup !== '', fn ($q) => $q->where(fn ($inner) => $inner
                        ->whereRaw('LOWER(notice_number) LIKE ?', ['%'.mb_strtolower($lookup).'%'])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', ['%'.mb_strtolower($lookup).'%'])))
                    ->latest('impounded_at')
                    ->limit(10)
                    ->get();
            }
        } else {
            if ($request->filled('citation_id')) {
                $record = Citation::with(['violationType', 'payment'])->find($request->citation_id);
            } elseif ($lookup !== '') {
                $record = Citation::with(['violationType', 'payment'])
                    ->where(fn ($q) => $q->whereRaw('LOWER(citation_number) LIKE ?', ['%'.mb_strtolower($lookup).'%'])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', ['%'.mb_strtolower($lookup).'%']))
                    ->orderByRaw('citation_number = ? desc', [$lookup])
                    ->latest('issued_at')
                    ->first();
            }

            if (! $record) {
                $suggestions = $this->awaitingCitationsQuery()->with('violationType')
                    ->when($lookup !== '', fn ($q) => $q->where(fn ($inner) => $inner
                        ->whereRaw('LOWER(citation_number) LIKE ?', ['%'.mb_strtolower($lookup).'%'])
                        ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', ['%'.mb_strtolower($lookup).'%'])))
                    ->latest('issued_at')
                    ->limit(10)
                    ->get();
            }
        }

        return view('payments.create', [
            'category' => $category,
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
