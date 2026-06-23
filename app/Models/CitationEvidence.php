<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitationEvidence extends Model
{
    protected $table = 'citation_evidence';

    protected $fillable = [
        'citation_id',
        'file_path',
        'original_name',
    ];

    public function citation(): BelongsTo
    {
        return $this->belongsTo(Citation::class);
    }
}
