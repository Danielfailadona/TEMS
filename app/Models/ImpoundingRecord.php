<?php

namespace App\Models;

use App\Enums\ImpoundingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ImpoundingRecord extends Model
{
    use LogsActivity;

    protected $fillable = [
        'notice_number',
        'vehicle_plate',
        'citation_id',
        'clamping_record_id',
        'impounded_by',
        'status',
        'location',
        'notes',
        'evidence_path',
        'towing_fee',
        'storage_fee_per_day',
        'admin_fee',
        'grace_until',
        'impounded_at',
        'paid_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImpoundingStatus::class,
            'impounded_at' => 'datetime',
            'grace_until' => 'datetime',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
            'towing_fee' => 'decimal:2',
            'storage_fee_per_day' => 'decimal:2',
            'admin_fee' => 'decimal:2',
        ];
    }

    public function citation(): BelongsTo
    {
        return $this->belongsTo(Citation::class);
    }

    public function clampingRecord(): BelongsTo
    {
        return $this->belongsTo(ClampingRecord::class, 'clamping_record_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impounded_by');
    }

    public function impoundingOfficer(): BelongsTo
    {
        return $this->officer();
    }

    public function release(): HasOne
    {
        return $this->hasOne(VehicleRelease::class, 'impounding_record_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function getTotalFees(): float
    {
        if (!$this->grace_until || !$this->impounded_at) {
            return $this->towing_fee + $this->admin_fee;
        }

        $days = max(0, $this->impounded_at->diffInDays($this->grace_until, false));
        if ($days < 0) $days = 0;

        return $this->towing_fee + ($days * $this->storage_fee_per_day) + $this->admin_fee;
    }

    public function getStorageDays(): int
    {
        if (!$this->grace_until || !$this->impounded_at) {
            return 0;
        }

        $days = max(0, $this->impounded_at->diffInDays($this->grace_until, false));
        return max(0, $days);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}