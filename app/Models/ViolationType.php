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
    ];

    protected function casts(): array
    {
        return [
            'penalty_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'is_impoundable' => 'boolean',
        ];
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }
}
