<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleRelease extends Model
{
    protected $fillable = [
        'release_number',
        'clamping_record_id',
        'released_by',
        'notes',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
        ];
    }

    public function clampingRecord(): BelongsTo
    {
        return $this->belongsTo(ClampingRecord::class, 'clamping_record_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
