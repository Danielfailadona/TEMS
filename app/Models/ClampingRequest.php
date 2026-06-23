<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClampingRequest extends Model
{
    protected $fillable = [
        'requester_name',
        'requester_phone',
        'requester_email',
        'location_address',
        'latitude',
        'longitude',
        'vehicle_plate',
        'vehicle_description',
        'evidence_photo',
        'additional_notes',
        'status',
        'rejection_reason',
        'processed_by',
        'processed_at',
        'clamping_record_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function clampingRecord(): BelongsTo
    {
        return $this->belongsTo(ClampingRecord::class);
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'resolved' => 'info',
            default => 'secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'resolved' => 'Resolved',
            default => 'Unknown',
        };
    }
}
