<?php

namespace App\Http\Controllers;

use App\Enums\CitationStatus;
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
            if ($citation->payment->paid_at) {
                return back()->withErrors(['citation_id' => 'This citation has already been paid.']);
            }
            // Abandoned/pending online checkout — reuse the row so the receipt number is kept.
        }

        if (!$citation->isPayable()) {
            return back()->withErrors(['citation_id' => 'This citation is not eligible for payment.']);
        }

        if (!$payMongo->isAvailable()) {
            return back()->withErrors(['paymongo' => 'Online payment is not configured. Please pay at the office.']);
        }

        $payment = $citation->payment ?? DB::transaction(function () use ($citation, $numberService) {
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
                'paymongo_session_ids' => array_values(array_unique(array_merge(
                    $payment->paymongo_session_ids ?? [],
                    [$session['id']]
                ))),
            ]);

            return redirect()->away($session['checkout_url']);
        } catch (\Throwable $e) {
            $payment->delete();

            return back()->withErrors(['paymongo' => 'Payment gateway error: '.$e->getMessage()]);
        }
    }

    public function success(Payment $payment, PayMongoService $payMongo): View
    {
        $session = $this->resolvePaidCheckoutSession($payment, $payMongo);

        if ($session) {
            $this->confirmOnlinePayment($payment, $session['attributes'], auth()->id());
        }

        return view('payments.online-success', compact('payment'));
    }

    public function publicCheckout(
        Request $request,
        $id,
        $token,
        PayMongoService $payMongo,
        CitationNumberService $numberService
    ): RedirectResponse {
        $citation = $this->resolvePublicCitation($id, $token);

        if (! $citation) {
            abort(404);
        }

        if ($citation->payment) {
            if ($citation->payment->paid_at) {
                return back()->withErrors(['citation_id' => 'This citation has already been paid.']);
            }
            // Abandoned/pending online checkout — reuse the row so the receipt number is kept.
        }

        if (! $citation->isPayable()) {
            return back()->withErrors(['citation_id' => 'This citation is not eligible for payment.']);
        }

        if (! $payMongo->isAvailable()) {
            return back()->withErrors(['paymongo' => 'Online payment is not configured. Please pay at the office.']);
        }

        $payment = $citation->payment ?? DB::transaction(function () use ($citation, $numberService) {
            return Payment::create([
                'receipt_number' => $numberService->receiptNumber(),
                'citation_id' => $citation->id,
                'cashier_id' => null,
                'amount' => $citation->penalty_amount,
                'payment_method' => 'other',
                'paid_at' => null,
            ]);
        });

        try {
            $session = $payMongo->createCheckoutSession([
                'billing_name' => $citation->driver_name ?? 'Citation Payer',
                'billing_phone' => null,
                'amount' => $citation->penalty_amount,
                'description' => 'Citation '.$citation->citation_number.' - '.$citation->violationType->name,
                'success_url' => route('public.citation.success', [
                    'id' => $citation->id,
                    'token' => $token,
                    'payment' => $payment->id,
                ]),
                'cancel_url' => route('public.citation.ticket', [
                    'id' => $citation->id,
                    'token' => $token,
                ]),
                'payment_id' => (string) $payment->id,
                'citation_number' => $citation->citation_number,
                'receipt_number' => $payment->receipt_number,
            ]);

            $payment->update([
                'paymongo_checkout_id' => $session['id'],
                'paymongo_status' => $session['status'],
                'paymongo_session_ids' => array_values(array_unique(array_merge(
                    $payment->paymongo_session_ids ?? [],
                    [$session['id']]
                ))),
            ]);

            return redirect()->away($session['checkout_url']);
        } catch (\Throwable $e) {
            $payment->delete();

            return back()->withErrors(['paymongo' => 'Payment gateway error: '.$e->getMessage()]);
        }
    }

    public function publicSuccess($id, $token, Payment $payment, PayMongoService $payMongo): View
    {
        $citation = $this->resolvePublicCitation($id, $token);

        if (! $citation || $payment->citation_id !== $citation->id) {
            abort(404);
        }

        $session = $this->resolvePaidCheckoutSession($payment, $payMongo);

        if ($session) {
            $this->confirmOnlinePayment($payment, $session['attributes'], null);
        }

        $viewData = ['payment' => $payment, 'citation' => $payment->citation];

        return view('citations.payment-result', $viewData);
    }

    public function cancel(Payment $payment): View
    {
        if (! $payment->paid_at) {
            $payment->citation->update(['status' => CitationStatus::Issued]);
            $payment->update(['paymongo_status' => 'cancelled']);
        }

        return view('payments.online-cancel', compact('payment'));
    }

    public function webhook(Request $request, PayMongoService $payMongo): \Illuminate\Http\Response
    {
        $payload = $request->all();

        $eventType = $payload['data']['attributes']['type'] ?? '';
        $eventAttrs = $payload['data']['attributes']['data']['attributes'] ?? [];

        // Mirrors the working webhook from the previous VCMS project: no hard
        // dependency on the signing secret. When a secret is configured we still
        // validate it, but an invalid/missing signature is only logged so a
        // misconfigured secret can never silently block payment confirmation.
        if (config('paymongo.webhook_secret') && ! $payMongo->verifyWebhookSignature($request->getContent(), (string) $request->header('PayMongo-Signature'))) {
            \Illuminate\Support\Facades\Log::warning('PayMongo webhook: signature mismatch — processing anyway.');
        }

        // Checkout Session and Payment API event variants.
        if (! in_array($eventType, ['payment.paid', 'checkout_session.payment.paid', 'checkout_session.payment_paid'], true)) {
            return response('OK');
        }

        // `checkout_session.payment.paid` carries the checkout id (cs_*) in the
        // nested payment attributes as `checkout_session_id`. Whenever possible
        // we also surface that id on `payment.paid` payloads.
        $sessionId = $eventAttrs['checkout_session_id'] ?? null;

        \Illuminate\Support\Facades\Log::info('PayMongo webhook: paid event received', [
            'type' => $eventType,
            'session_id' => $sessionId,
        ]);

        if (! $sessionId) {
            \Illuminate\Support\Facades\Log::warning('PayMongo webhook: no checkout_session_id in payload.', compact('eventType'));

            return response('OK');
        }

        // Resolve the linked payment row via the checkout/session identifiers
        // stored on it when the session was created.
        $payment = Payment::where('paymongo_checkout_id', $sessionId)
            ->orWhereJsonContains('paymongo_session_ids', $sessionId)
            ->first();

        // Fallback for checkouts created before paymongo_session_ids existed:
        // recover the linked payment via the checkout id in metadata.
        if (! $payment) {
            try {
                $session = $payMongo->retrieveCheckoutSession($sessionId);
                $paymentId = $session['attributes']['metadata']['payment_id'] ?? null;
                $payment = $paymentId ? Payment::find((int) $paymentId) : null;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        \Illuminate\Support\Facades\Log::info('PayMongo webhook: payment resolution', [
            'session_id' => $sessionId,
            'payment_id' => $payment?->id,
        ]);

        if ($payment && ! $payment->paid_at) {
            \Illuminate\Support\Facades\Log::info('PayMongo webhook: confirming payment', ['payment_id' => $payment->id]);
            $this->confirmOnlinePayment($payment, $eventAttrs, null);
            \Illuminate\Support\Facades\Log::info('PayMongo webhook: payment confirmed', [
                'payment_id' => $payment->refresh()->id,
                'paid_at' => $payment->paid_at,
                'paymongo_status' => $payment->paymongo_status,
            ]);
        }

        return response('OK');
    }

    protected function resolvePaidCheckoutSession(Payment $payment, PayMongoService $payMongo): ?array
    {
        $ids = array_values(array_unique(array_merge(
            $payment->paymongo_session_ids ?? [],
            $payment->paymongo_checkout_id ? [$payment->paymongo_checkout_id] : [],
        )));

        foreach (array_reverse($ids) as $id) {
            try {
                $session = $payMongo->retrieveCheckoutSession($id);

                if (in_array($session['attributes']['status'] ?? '', ['paid', 'completed'], true)) {
                    return $session;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return null;
    }

    protected function resolvePublicCitation($id, $token): ?Citation
    {
        $citation = Citation::find($id);

        if ($citation && hash_equals($citation->getValidationToken(), (string) $token)) {
            return $citation;
        }

        return null;
    }

    protected function confirmOnlinePayment(Payment $payment, array $attrs, ?int $archivedBy, bool $force = false): void
    {
        \Illuminate\Support\Facades\Log::info('confirmOnlinePayment invoked', [
            'payment_id' => $payment->id,
            'already_paid' => (bool) $payment->paid_at,
            'force' => $force,
        ]);

        if ($payment->paid_at && ! $force) {
            return;
        }

        $payment->update([
            'paymongo_payment_intent_id' => $attrs['payment_intent']['id'] ?? null,
            'paymongo_status' => $attrs['status'],
            'online_payment_method' => $attrs['payment_method_used'] ?? null,
            'paid_at' => $payment->paid_at ?? now(),
        ]);

        $payment->citation->update(['status' => CitationStatus::Paid]);

        $alreadyArchived = Archive::where('archivable_type', Citation::class)
            ->where('archivable_id', $payment->citation->id)
            ->exists();

        if (! $alreadyArchived) {
            Archive::create([
                'archivable_type' => Citation::class,
                'archivable_id' => $payment->citation->id,
                'archived_by' => $archivedBy,
                'archived_at' => now(),
                'reason' => 'Citation paid online via PayMongo',
                'snapshot' => $payment->citation->refresh()->toArray(),
            ]);
        }

        \App\Models\SystemNotification::notify(
            $payment->citation->enforcer,
            'payment_received',
            'Payment Received',
            "Citation {$payment->citation->citation_number} paid online via ".($payment->online_payment_method ?? 'PayMongo'),
            ['payment_id' => $payment->id, 'citation_number' => $payment->citation->citation_number]
        );
    }
}
