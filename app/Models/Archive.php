<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Archive extends Model
{
    protected $fillable = [
        'archivable_type',
        'archivable_id',
        'archived_by',
        'archived_at',
        'reason',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function archivable(): MorphTo
    {
        return $this->morphTo();
    }

    public function archivedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}
