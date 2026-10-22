<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnforcerSchedule extends Model
{
    protected $fillable = [
        'enforcer_id',
        'days',
        'start_time',
        'end_time',
        'is_active_days',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'is_active_days' => 'array',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function enforcer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enforcer_id');
    }

    /**
     * Check if the enforcer is scheduled at a given datetime.
     */
    public static function isEnforcerScheduledAt(int $enforcerId, \Illuminate\Support\Carbon $dateTime): bool
    {
        $dayOfWeek = $dateTime->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday
        $time = $dateTime->format('H:i:s');

        return static::where('enforcer_id', $enforcerId)
            ->whereJsonContains('days', $dayOfWeek)
            ->where('is_active', true) // Legacy compatibility - keep for now
            ->where(function ($q) use ($dayOfWeek) {
                // Check per-day active toggle
                $q->whereJsonContains('is_active_days->' . $dayOfWeek, true);
            })
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->exists();
    }

    /**
     * Get all active schedules for an enforcer.
     */
    public static function getActiveSchedulesForEnforcer(int $enforcerId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('enforcer_id', $enforcerId)
            ->where('is_active', true) // Legacy
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Check if a specific day is active in this schedule.
     */
    public function isDayActive(int $dayOfWeek): bool
    {
        return $this->is_active_days[$dayOfWeek] ?? false;
    }

    /**
     * Get the active status for all days as an array of booleans.
     */
    public function getActiveDaysArray(): array
    {
        $result = [];
        for ($i = 0; $i <= 6; $i++) {
            $result[$i] = $this->is_active_days[$i] ?? false;
        }
        return $result;
    }
}