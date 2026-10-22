<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnforcerSchedule extends Model
{
    protected $fillable = [
        'enforcer_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_active' => 'boolean',
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
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
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
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }
}