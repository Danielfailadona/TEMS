<?php

namespace App\Models;

use App\Enums\CitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Citation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'citation_number',
        'violation_type_id',
        'issued_by',
        'penalty_amount',
        'status',
        'location',
        'notes',
        'issued_at',
        'due_date',
        'vehicle_plate',
        'vehicle_make',
        'vehicle_model',
        'vehicle_type',
        'vehicle_color',
        'driver_name',
        'driver_license',
    ];



    protected function casts(): array
    {
        return [
            'penalty_amount' => 'decimal:2',
            'status' => CitationStatus::class,
            'issued_at' => 'datetime',
            'due_date' => 'date',
        ];
    }

    public function violationType(): BelongsTo
    {
        return $this->belongsTo(ViolationType::class);
    }

    public function enforcer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(CitationEvidence::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function clampingRecords(): HasMany
    {
        return $this->hasMany(ClampingRecord::class);
    }

    public function appeal(): HasOne
    {
        return $this->hasOne(Appeal::class)->latest('submitted_at');
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [
            CitationStatus::Issued,
            CitationStatus::Overdue,
            CitationStatus::Clamped,
        ], true);
    }

    public function isPaid(): bool
    {
        return $this->status === CitationStatus::Paid
            || $this->status === CitationStatus::Released;
    }

    public function getQRCodeUrl(): string
    {
        $data = "Citation: {$this->citation_number} | Vehicle: {$this->vehicle_plate} | Amount: ₱{$this->penalty_amount}";
        $encoded = urlencode($data);

        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$encoded}";
    }

    public function getQRCode(): string
    {
        return '<img src="' . e($this->getQRCodeUrl()) . '" alt="QR Code" class="img-fluid" style="max-width:120px">';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
