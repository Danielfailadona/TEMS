<?php

namespace App\Models;

use App\Enums\AppealStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Appeal extends Model
{
    use LogsActivity;

    public const APPROVED_DISCOUNT_PERCENT = 15;

    protected $fillable = [
        'citation_id',
        'submitted_by',
        'guest_name',
        'guest_contact',
        'reviewed_by',
        'reason',
        'description',
        'status',
        'decision_notes',
        'original_amount',
        'adjusted_amount',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
            'original_amount' => 'decimal:2',
            'adjusted_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function citation(): BelongsTo
    {
        return $this->belongsTo(Citation::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isGuestSubmission(): bool
    {
        return is_null($this->submitted_by);
    }

    public function submitterDisplay(): string
    {
        if ($this->submitted_by !== null) {
            return $this->submitter?->name ?? 'N/A';
        }

        return $this->guest_name ? "Guest ({$this->guest_name})" : 'Guest';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
