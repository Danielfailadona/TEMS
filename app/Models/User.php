<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\EnforcerSchedule;
use App\Services\SupabaseStorage;
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
        'availability_override',
        'last_duty_state',
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

    /**
     * Public URL for the user's profile photo (null when none is set).
     * No remote existence check on purpose — pair with an initials fallback in the UI.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        return SupabaseStorage::publicUrl($this->profile_photo_path);
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
     * Whether this enforcer may be given an assignment right now.
     *
     * Task Management force_available beats the enforcer's own Unavailable
     * toggle; force_unavailable and self-toggled unavailable enforcers are
     * never assignable. Schedule and GPS status do not block manual
     * assignment — only the availability signals above do.
     */
    public function canBeAssigned(): bool
    {
        return match ($this->availability_override) {
            'force_available' => true,
            'force_unavailable' => false,
            default => (bool) $this->is_available,
        };
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
     * Check if enforcer is available for assignment based on schedule rules.
     *
     * force_available (Task Management override) is authoritative; auto-mode
     * enforcers must be self-toggled available AND online AND within schedule.
     */
    public function isScheduleAssignable(): bool
    {
        if ($this->availability_override === 'force_available') {
            return true;
        }

        return (bool) $this->is_available && $this->isWithinSchedule() && (bool) $this->is_online;
    }

    /**
     * Current duty state: on duty (online), on duty (offline), or off duty.
     */
    public function dutyState(): string
    {
        if (! $this->isWithinSchedule()) {
            return 'off_duty';
        }

        return $this->is_online ? 'on_duty_online' : 'on_duty_offline';
    }

    /**
     * Whether the enforcer has any schedule configured.
     */
    public function hasSchedule(): bool
    {
        return $this->enforcerSchedules()->exists();
    }

    /**
     * Human-readable label for the current duty state.
     */
    public function getDutyStateLabelAttribute(): string
    {
        return match ($this->dutyState()) {
            'on_duty_online' => 'On Duty',
            'on_duty_offline' => 'On Duty (Offline)',
            default => $this->hasSchedule() ? 'Off Duty' : 'No Schedule',
        };
    }

    /**
     * Bootstrap badge classes for the current duty state.
     */
    public function getDutyStateClassAttribute(): string
    {
        return match ($this->dutyState()) {
            'on_duty_online' => 'bg-success-soft text-success',
            'on_duty_offline' => 'bg-warning-soft text-warning',
            default => $this->hasSchedule() ? 'bg-danger-soft text-danger' : 'bg-gray-soft text-gray',
        };
    }

    /**
     * Scope for enforcers who are currently assignable by schedule rules.
     *
     * force_available (Task Management override) is authoritative: the enforcer
     * is assignable even off-schedule, offline, or self-toggled unavailable.
     * Auto-mode enforcers must be self-toggled available AND online AND within
     * their scheduled duty hours. force_unavailable / self-toggled unavailable
     * enforcers never match this scope.
     */
    public function scopeScheduleAssignable($query)
    {
        return $query->where(function ($q) {
            // Task Management force_available override is authoritative
            $q->where('availability_override', 'force_available')
                // Or auto mode: self-toggled available AND online with valid schedule
                ->orWhere(function ($q2) {
                    $q2->where('availability_override', 'auto')
                        ->where('is_available', true)
                        ->where('is_online', true)
                        ->whereHas('enforcerSchedules', function ($q3) {
                            $dayOfWeek = now()->dayOfWeek;
                            $time = now()->format('H:i:s');
                            $q3->whereJsonContains('days', $dayOfWeek)
                                ->whereJsonContains('is_active_days', [(string) $dayOfWeek => true])
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
            ->whereJsonContains('is_active_days', [(string) $dayOfWeek => true])
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
