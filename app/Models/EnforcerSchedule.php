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
        ];
    }

    /**
     * Start time formatted as H:i for display.
     */
    public function getStartTimeShortAttribute(): string
    {
        return $this->start_time ? substr($this->start_time, 0, 5) : '';
    }

    /**
     * End time formatted as H:i for display.
     */
    public function getEndTimeShortAttribute(): string
    {
        return $this->end_time ? substr($this->end_time, 0, 5) : '';
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
            ->whereJsonContains('is_active_days', [(string) $dayOfWeek => true])
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