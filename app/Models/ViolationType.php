<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'penalty_amount',
        'is_active',
        'is_impoundable',
        'is_clampable',
    ];

    protected function casts(): array
    {
        return [
            'penalty_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'is_impoundable' => 'boolean',
            'is_clampable' => 'boolean',
        ];
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    /**
     * Whether this violation type is the free-text "Others" placeholder.
     */
    public function isCustom(): bool
    {
        return strcasecmp((string) $this->code, 'others') === 0
            || strcasecmp((string) $this->name, 'others') === 0;
    }
}
