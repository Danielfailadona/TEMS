<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Archive;
use App\Models\Citation;
use App\Models\Payment;
use App\Models\NumberSeries;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::with(['citation', 'cashier'])->whereNotNull('paid_at');

        $payments = $query->latest('paid_at')->paginate(10);

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Payment::class);

        $citation = null;
        if ($request->filled('citation_number')) {
            $citation = Citation::with(['violationType', 'payment'])
                ->where('citation_number', $request->citation_number)
                ->first();
        } elseif ($request->filled('citation_id')) {
            $citation = Citation::with(['violationType', 'payment'])->find($request->citation_id);
        }

        return view('payments.create', [
            'citation' => $citation,
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function store(StorePaymentRequest $request, CitationNumberService $numberService): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $citation = Citation::with('payment')->findOrFail($request->citation_id);

        if ($citation->payment) {
            return back()->withErrors(['citation_id' => 'This citation has already been paid.']);
        }

        if (! $citation->isPayable()) {
            return back()->withErrors(['citation_id' => 'This citation is not eligible for payment.']);
        }

        $payment = DB::transaction(function () use ($request, $citation, $numberService) {
            $payment = Payment::create([
                'receipt_number' => $numberService->receiptNumber(),
                'citation_id' => $citation->id,
                'cashier_id' => auth()->id(),
                'amount' => $citation->penalty_amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'paid_at' => now(),
            ]);

            $citation->update(['status' => CitationStatus::Paid]);

            Archive::create([
                'archivable_type' => Citation::class,
                'archivable_id' => $citation->id,
                'archived_by' => auth()->id(),
                'archived_at' => now(),
                'reason' => 'Citation paid - Receipt: '.($payment->receipt_number ?? 'N/A'),
                'snapshot' => $citation->refresh()->toArray(),
            ]);

            if ($citation->issued_by) {
                $enforcer = User::find($citation->issued_by);
                if ($enforcer) {
                    SystemNotification::notify(
                        $enforcer,
                        'payment_received',
                        'Citation Payment Received',
                        "Citation {$citation->citation_number} has been paid (₱".number_format($payment->amount, 2).").",
                        ['citation_number' => $citation->citation_number, 'payment_id' => $payment->id]
                    );
                }
            }

            return $payment;
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment recorded successfully.');
    }

    public function edit(Payment $payment): View
    {
        $this->authorize('update', $payment);

        $payment->load(['citation.violationType', 'cashier']);

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

        $payment->load(['citation.violationType', 'cashier']);

        return view('payments.show', compact('payment'));
    }
}
