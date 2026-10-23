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
     * Start time formatted as h:i A for display.
     */
    public function getStartTimeShortAttribute(): string
    {
        if (! $this->start_time) {
            return '';
        }

        $time = $this->start_time;

        if (is_string($time) && strlen($time) >= 8 && str_contains($time, ':')) {
            $parts = explode(':', $time);
            if (count($parts) >= 2) {
                $carbon = \Illuminate\Support\Carbon::createFromTime((int) $parts[0], (int) $parts[1]);

                return $carbon->format('h:i A');
            }
        }

        if (is_string($time) && strlen($time) >= 5 && str_contains($time, ':')) {
            $parts = explode(':', $time);
            if (count($parts) >= 2) {
                $carbon = \Illuminate\Support\Carbon::createFromTime((int) $parts[0], (int) $parts[1]);

                return $carbon->format('h:i A');
            }
        }

        return (string) $time;
    }

    /**
     * End time formatted as h:i A for display.
     */
    public function getEndTimeShortAttribute(): string
    {
        if (! $this->end_time) {
            return '';
        }

        $time = $this->end_time;

        if (is_string($time) && strlen($time) >= 5 && str_contains($time, ':')) {
            $parts = explode(':', $time);
            if (count($parts) >= 2) {
                $carbon = \Illuminate\Support\Carbon::createFromTime((int) $parts[0], (int) $parts[1]);

                return $carbon->format('h:i A');
            }
        }

        return (string) $time;
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