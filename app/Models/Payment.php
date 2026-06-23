<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'receipt_number',
        'citation_id',
        'cashier_id',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'paid_at',
        'paymongo_checkout_id',
        'paymongo_payment_intent_id',
        'paymongo_status',
        'online_payment_method',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'paid_at' => 'datetime',
        ];
    }

    public function isOnlinePayment(): bool
    {
        return ! is_null($this->paymongo_checkout_id);
    }

    public function isPendingOnline(): bool
    {
        return $this->isOnlinePayment() && is_null($this->paid_at);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function citation(): BelongsTo
    {
        return $this->belongsTo(Citation::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
