<?php

namespace App\Models;

use App\Enums\Role;
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
}
