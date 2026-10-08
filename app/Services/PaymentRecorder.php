<?php

namespace App\Services;

use App\Enums\CitationStatus;
use App\Enums\ClampingStatus;
use App\Enums\ImpoundingStatus;
use App\Models\Archive;
use App\Models\Citation;
use App\Models\ClampingRecord;
use App\Models\ImpoundingRecord;
use App\Models\Payment;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentRecorder
{
    public function __construct(private CitationNumberService $numbers) {}

    public function recordCitation(Citation $citation, array $data, ?int $cashierId = null): Payment
    {
        return DB::transaction(function () use ($citation, $data, $cashierId) {
            $payment = Payment::create([
                'receipt_number' => $this->numbers->receiptNumber(),
                'citation_id' => $citation->id,
                'cashier_id' => $cashierId,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
            ]);

            $citation->update(['status' => CitationStatus::Paid]);

            Archive::create([
                'archivable_type' => Citation::class,
                'archivable_id' => $citation->id,
                'archived_by' => $cashierId,
                'archived_at' => now(),
                'reason' => 'Citation paid - Receipt: '.$payment->receipt_number,
                'snapshot' => $citation->refresh()->toArray(),
            ]);

            if ($citation->issued_by) {
                $enforcer = User::find($citation->issued_by);

                if ($enforcer) {
                    SystemNotification::notify(
                        $enforcer,
                        'payment_received',
                        'Citation Payment Received',
                        "Citation {$citation->citation_number} has been paid (₱".number_format((float) $payment->amount, 2).").",
                        ['citation_number' => $citation->citation_number, 'payment_id' => $payment->id]
                    );
                }
            }

            return $payment;
        });
    }

    public function recordClamping(ClampingRecord $clamping, array $data, ?int $cashierId = null): Payment
    {
        return DB::transaction(function () use ($clamping, $data, $cashierId) {
            if ($existing = $clamping->payments()->whereNotNull('paid_at')->first()) {
                return $existing;
            }

            $citation = $clamping->citation;

            $payment = Payment::create([
                'receipt_number' => $this->numbers->receiptNumber(),
                'citation_id' => ($citation && ! $citation->payment) ? $citation->id : null,
                'payable_type' => ClampingRecord::class,
                'payable_id' => $clamping->id,
                'cashier_id' => $cashierId,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
            ]);

            if ($citation && ! $citation->payment) {
                $citation->update(['status' => CitationStatus::Paid]);
            }

            $clamping->update([
                'status' => ClampingStatus::Paid,
                'clamping_fee' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'paid_at' => now(),
            ]);

            Archive::create([
                'archivable_type' => ClampingRecord::class,
                'archivable_id' => $clamping->id,
                'archived_by' => $cashierId,
                'archived_at' => now(),
                'reason' => 'Clamp payment recorded (Notice: '.$clamping->notice_number.')',
                'snapshot' => $clamping->fresh()->toArray(),
            ]);

            return $payment;
        });
    }

    public function recordImpounding(ImpoundingRecord $impounding, array $data, ?int $cashierId = null): Payment
    {
        return DB::transaction(function () use ($impounding, $data, $cashierId) {
            if ($existing = $impounding->payments()->whereNotNull('paid_at')->first()) {
                return $existing;
            }

            $citation = $impounding->citation;
            $amount = $data['amount'] ?? $impounding->getTotalFees();

            $payment = Payment::create([
                'receipt_number' => $this->numbers->receiptNumber(),
                'citation_id' => ($citation && ! $citation->payment) ? $citation->id : null,
                'payable_type' => ImpoundingRecord::class,
                'payable_id' => $impounding->id,
                'cashier_id' => $cashierId,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
            ]);

            if ($citation && ! $citation->payment) {
                $citation->update(['status' => CitationStatus::Paid]);
            }

            $impounding->update([
                'status' => ImpoundingStatus::Paid,
                'paid_at' => now(),
            ]);

            Archive::create([
                'archivable_type' => ImpoundingRecord::class,
                'archivable_id' => $impounding->id,
                'archived_by' => $cashierId,
                'archived_at' => now(),
                'reason' => 'Impound payment recorded (Notice: '.$impounding->notice_number.')',
                'snapshot' => $impounding->fresh()->toArray(),
            ]);

            return $payment;
        });
    }
}