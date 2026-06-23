<?php

namespace App\Models;

use App\Enums\ClampingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
    ];

    protected function casts(): array
    {
        return [
            'status' => ClampingStatus::class,
            'clamped_at' => 'datetime',
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

    public function isActive(): bool
    {
        return $this->status === ClampingStatus::AwaitingPayment;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
