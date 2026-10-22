<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\EnforcerSchedule;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'supabase_id',
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'is_active',
        'phone',
        'account_status',
        'rejection_reason',
        'profile_photo_path',
        'last_login_at',
        'last_login_ip',
        'is_online',
        'is_available',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'is_online' => 'boolean',
            'is_available' => 'boolean',
            'preferences' => 'array',
        ];
    }

    public function issuedCitations(): HasMany
    {
        return $this->hasMany(Citation::class, 'issued_by');
    }

    public function processedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'cashier_id');
    }

    public function isRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role->isSuperAdmin();
    }

    public function isCashier(): bool
    {
        return $this->role->isCashier();
    }

    public function isFrontDesk(): bool
    {
        return $this->role->isFrontDesk();
    }

    public function canDelete(): bool
    {
        return $this->role->canDelete();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(DeviceManager::class);
    }

    public function assignedCitizenReports(): HasMany
    {
        return $this->hasMany(CitizenReport::class, 'assigned_to');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class, 'user_id');
    }

    public function teams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->where('is_read', false);
    }

    public function scopePending($query)
    {
        return $query->where('account_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('account_status', 'approved');
    }

    public function isPending(): bool
    {
        return $this->account_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->account_status === 'rejected';
    }

    public function isSuspended(): bool
    {
        return $this->account_status === 'suspended';
    }

    public function isApproved(): bool
    {
        return $this->account_status === 'approved';
    }

    public function getBadgeIdAttribute(): string
    {
        return 'TE-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function enforcerLocation(): HasOne
    {
        return $this->hasOne(EnforcerLocation::class);
    }

    public function enforcerSchedules(): HasMany
    {
        return $this->hasMany(EnforcerSchedule::class, 'enforcer_id');
    }

    /**
     * An enforcer is assignable when they have manually marked themselves
     * available AND are currently online (marked by an active GPS ping).
     */
    public function isAssignable(): bool
    {
        return (bool) $this->is_available && (bool) $this->is_online;
    }

    public function scopeAssignable($query)
    {
        return $query->where('is_available', true)->where('is_online', true);
    }

    /**
     * Check if the enforcer is currently within their scheduled duty hours.
     * Considers the availability_override: 'auto' uses schedule, 'force_available'/'force_unavailable' overrides.
     */
    public function isWithinSchedule(): bool
    {
        // Manual override takes precedence
        if ($this->availability_override === 'force_available') {
            return true;
        }
        if ($this->availability_override === 'force_unavailable') {
            return false;
        }

        // 'auto' mode: check schedule
        return EnforcerSchedule::isEnforcerScheduledAt($this->id, now());
    }

    /**
     * Check if enforcer is available for assignment based on schedule AND online status.
     */
    public function isScheduleAssignable(): bool
    {
        return $this->isWithinSchedule() && (bool) $this->is_online;
    }

    /**
     * Scope for enforcers who are currently schedule-assignable (within schedule AND online).
     */
    public function scopeScheduleAssignable($query)
    {
        return $query->where('is_online', true)
            ->where(function ($q) {
                // Force available
                $q->where('availability_override', 'force_available')
                    // Or auto with valid schedule
                    ->orWhere(function ($q2) {
                        $q2->where('availability_override', 'auto')
                            ->whereHas('enforcerSchedules', function ($q3) {
                                $dayOfWeek = now()->dayOfWeek;
                                $time = now()->format('H:i:s');
                                $q3->whereJsonContains('days', $dayOfWeek)
                                    ->where(function ($q4) use ($dayOfWeek) {
                                        $q4->whereJsonContains('is_active_days->' . $dayOfWeek, true);
                                    })
                                    ->where('start_time', '<=', $time)
                                    ->where('end_time', '>', $time);
                            });
                    });
            });
    }

    /**
     * Get the enforcer's active schedules for the current day.
     */
    public function getTodaysSchedulesAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        $dayOfWeek = now()->dayOfWeek;
        $time = now()->format('H:i:s');
        return $this->enforcerSchedules()
            ->whereJsonContains('days', $dayOfWeek)
            ->where(function ($q) use ($dayOfWeek) {
                $q->whereJsonContains('is_active_days->' . $dayOfWeek, true);
            })
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get the enforcer's full weekly schedule.
     */
    public function getWeeklyScheduleAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->enforcerSchedules()
            ->orderBy('start_time')
            ->get();
    }
}
