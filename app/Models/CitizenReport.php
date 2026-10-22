<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitizenReport extends Model
{
    protected $fillable = [
        'report_number',
        'requester_name',
        'requester_phone',
        'requester_email',
        'location_address',
        'latitude',
        'longitude',
        'vehicle_plate',
        'vehicle_make',
        'vehicle_model',
        'vehicle_color',
        'evidence_photo',
        'additional_notes',
        'status',
        'rejection_reason',
        'resolution_type',
        'resolution_notes',
        'resolved_at',
        'assigned_to',
        'processed_by',
        'processed_at',
        'citation_id',
        'clamping_record_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'processed_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function citation(): BelongsTo
    {
        return $this->belongsTo(Citation::class);
    }

    /**
     * Human-readable vehicle description assembled from the structured
     * make/model/color fields (kept for display and archive output).
     */
    public function getVehicleDescriptionAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->vehicle_color,
            $this->vehicle_make,
            $this->vehicle_model,
        ])));
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            ReportStatus::Pending->value => 'tag-amber',
            ReportStatus::Accepted->value, ReportStatus::Resolved->value => 'tag-green',
            ReportStatus::Rejected->value => 'tag-red',
            default => 'tag-gray',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            ReportStatus::Pending->value => '#f59e0b',
            ReportStatus::Accepted->value, ReportStatus::Resolved->value => '#10B981',
            ReportStatus::Rejected->value => '#EF4444',
            default => '#69757F',
        };
    }

    public function getStatusSubtleColor(): string
    {
        return match ($this->status) {
            ReportStatus::Pending->value => 'rgba(245, 158, 11, 0.15)',
            ReportStatus::Accepted->value, ReportStatus::Resolved->value => 'rgba(16, 185, 129, 0.15)',
            ReportStatus::Rejected->value => 'rgba(239, 68, 68, 0.15)',
            default => 'rgba(105, 117, 127, 0.15)',
        };
    }

    public function getStatusLabel(): string
    {
        return ReportStatus::tryFrom((string) $this->status)?->label() ?? 'Unknown';
    }
}
