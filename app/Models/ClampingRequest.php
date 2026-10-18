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
        'assigned_to',
        'processed_by',
        'processed_at',
        'clamping_record_id',
        'reference_number',
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
            'pending' => 'tag-amber',
            'approved' => 'tag-green',
            'rejected' => 'tag-red',
            'resolved' => 'tag-green',
            default => 'tag-gray',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => '#f59e0b',
            'approved', 'resolved' => '#10B981',
            'rejected' => '#EF4444',
            default => '#69757F',
        };
    }

    public function getStatusSubtleColor(): string
    {
        return match ($this->status) {
            'pending' => 'rgba(245, 158, 11, 0.15)',
            'approved', 'resolved' => 'rgba(16, 185, 129, 0.15)',
            'rejected' => 'rgba(239, 68, 68, 0.15)',
            default => 'rgba(105, 117, 127, 0.15)',
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
