<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Citation;
use App\Models\Payment;
use App\Services\CitationNumberService;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayMongoController extends Controller
{
    public function checkout(Citation $citation, PayMongoService $payMongo, CitationNumberService $numberService): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            $this->authorize('create', Payment::class);
        }

        if ($citation->payment) {
            return back()->withErrors(['citation_id' => 'This citation has already been paid.']);
        }

        if (!$citation->isPayable()) {
            return back()->withErrors(['citation_id' => 'This citation is not eligible for payment.']);
        }

        if (!$payMongo->isAvailable()) {
            return back()->withErrors(['paymongo' => 'Online payment is not configured. Please pay at the office.']);
        }

        $payment = DB::transaction(function () use ($citation, $numberService) {
            return Payment::create([
                'receipt_number' => $numberService->receiptNumber(),
                'citation_id' => $citation->id,
                'cashier_id' => auth()->id(),
                'amount' => $citation->penalty_amount,
                'payment_method' => 'other',
                'paid_at' => null,
            ]);
        });

        try {
            $session = $payMongo->createCheckoutSession([
                'billing_name' => $citation->driver_name ?? auth()->user()->name,
                'billing_email' => auth()->user()->email,
                'billing_phone' => auth()->user()->phone,
                'amount' => $citation->penalty_amount,
                'description' => 'Citation '.$citation->citation_number.' - '.$citation->violationType->name,
                'success_url' => route('payments.online.success', ['payment' => $payment->id]),
                'cancel_url' => route('payments.online.cancel', ['payment' => $payment->id]),
                'payment_id' => (string) $payment->id,
                'citation_number' => $citation->citation_number,
                'receipt_number' => $payment->receipt_number,
            ]);

            $payment->update([
                'paymongo_checkout_id' => $session['id'],
                'paymongo_status' => $session['status'],
            ]);

            return redirect()->away($session['checkout_url']);
        } catch (\Throwable $e) {
            $payment->delete();

            return back()->withErrors(['paymongo' => 'Payment gateway error: '.$e->getMessage()]);
        }
    }

    public function success(Payment $payment, PayMongoService $payMongo): View
    {
        if ($payment->paymongo_checkout_id && !$payment->paymongo_payment_intent_id) {
            try {
                $session = $payMongo->retrieveCheckoutSession($payment->paymongo_checkout_id);
                $attrs = $session['attributes'];

                if (in_array($attrs['status'], ['paid', 'completed'], true)) {
                    $payment->update([
                        'paymongo_payment_intent_id' => $attrs['payment_intent']['id'] ?? null,
                        'paymongo_status' => $attrs['status'],
                        'online_payment_method' => $attrs['payment_method_used'] ?? null,
                        'paid_at' => now(),
                    ]);

                    $payment->citation->update(['status' => \App\Enums\CitationStatus::Paid]);

                    Archive::create([
                        'archivable_type' => Citation::class,
                        'archivable_id' => $payment->citation->id,
                        'archived_by' => auth()->id(),
                        'archived_at' => now(),
                        'reason' => 'Citation paid online via PayMongo',
                        'snapshot' => $payment->citation->refresh()->toArray(),
                    ]);

                    \App\Models\SystemNotification::notify(
                        $payment->citation->enforcer,
                        'payment_received',
                        'Payment Received',
                        "Citation {$payment->citation->citation_number} paid online via ".($payment->online_payment_method ?? 'PayMongo'),
                        ['payment_id' => $payment->id, 'citation_number' => $payment->citation->citation_number]
                    );
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('payments.online-success', compact('payment'));
    }

    public function cancel(Payment $payment): View
    {
        if (!$payment->paid_at) {
            $payment->citation->update(['status' => \App\Enums\CitationStatus::Issued]);
            $payment->delete();
        }

        return view('payments.online-cancel');
    }

    public function webhook(Request $request, PayMongoService $payMongo): \Illuminate\Http\Response
    {
        $payload = $request->all();

        if (($payload['data']['attributes']['type'] ?? '') === 'payment.paid') {
            $checkoutId = $payload['data']['attributes']['data']['id'] ?? null;

            if ($checkoutId) {
                $payment = Payment::where('paymongo_checkout_id', $checkoutId)->first();

                if ($payment && !$payment->paid_at) {
                    try {
                        $session = $payMongo->retrieveCheckoutSession($checkoutId);
                        $attrs = $session['attributes'];

                        $payment->update([
                            'paymongo_payment_intent_id' => $attrs['payment_intent']['id'] ?? null,
                            'paymongo_status' => $attrs['status'],
                            'online_payment_method' => $attrs['payment_method_used'] ?? null,
                            'paid_at' => now(),
                        ]);

                        $payment->citation->update(['status' => \App\Enums\CitationStatus::Paid]);

                        Archive::create([
                            'archivable_type' => Citation::class,
                            'archivable_id' => $payment->citation->id,
                            'archived_by' => null,
                            'archived_at' => now(),
                            'reason' => 'Citation paid online via PayMongo (webhook)',
                            'snapshot' => $payment->citation->refresh()->toArray(),
                        ]);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        }

        return response('OK');
    }
}
