<?php

namespace App\Models;

use App\Enums\ClampingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ClampingRecord extends Model
{
    use LogsActivity;
    protected $fillable = [
        'notice_number',
        'vehicle_plate',
        'citation_id',
        'clamped_by',
        'status',
        'location',
        'notes',
        'evidence_path',
        'clamped_at',
        'paid_at',
        'released_at',
        'clamping_fee',
        'payment_method',
        'reference_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClampingStatus::class,
            'clamped_at' => 'datetime',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
            'clamping_fee' => 'decimal:2',
        ];
    }

    public function citation(): BelongsTo
    {
        return $this->belongsTo(Citation::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clamped_by');
    }

    public function release(): HasOne
    {
        return $this->hasOne(VehicleRelease::class, 'clamping_record_id');
    }

    public function impoundingRecord(): HasOne
    {
        return $this->hasOne(ImpoundingRecord::class, 'clamping_record_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function isActive(): bool
    {
        return $this->status === ClampingStatus::AwaitingPayment;
    }

    public function getValidationToken(): string
    {
        return substr(
            hash_hmac('sha256', $this->id.'|'.$this->notice_number, config('app.key')),
            0,
            32
        );
    }

    public function getPublicPaymentUrl(): string
    {
        return route('public.clamping.ticket', [
            'id' => $this->id,
            'token' => $this->getValidationToken(),
        ]);
    }

    public function getQRCodeSvg(int $size = 280): string
    {
        return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size($size)
            ->errorCorrection('M')
            ->margin(1)
            ->generate($this->getPublicPaymentUrl());
    }

    public function getQRCodeUrl(): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->getQRCodeSvg(160));
    }

    public function getQRCode(): string
    {
        return $this->getQRCodeSvg(160);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
