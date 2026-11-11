<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\EnforcerSchedule;
use App\Models\User;

class AutoAssignService
{
    /**
     * Pick an enforcer who is eligible for auto-assignment.
     *
     * Precedence (Task Management overrides are authoritative):
     *  1. `force_available` override — always assignable, even off-schedule,
     *     offline, or self-toggled unavailable.
     *  2. `auto` mode — self-toggled available AND online AND within their
     *     scheduled duty hours (random pick).
     *  3. `auto` mode + self-toggled available — load-balancing fallback
     *     (fewest open reports), regardless of schedule or GPS status.
     *  4. `force_unavailable` or self-toggled unavailable — never picked.
     *  5. Nobody qualifies — null is returned so the report is accepted but
     *     left unassigned (the caller informs the admin).
     */
    public function pickEnforcer(): ?User
    {
        $base = User::query()
            ->where('role', Role::Enforcer->value)
            ->where('account_status', 'approved')
            ->where('is_active', true);

        // 1. Task Management force_available override is authoritative.
        $forced = (clone $base)
            ->where('availability_override', 'force_available')
            ->inRandomOrder()
            ->first();

        if ($forced) {
            return $forced;
        }

        // 2. Normal path: auto + available + online + within schedule.
        $available = (clone $base)->scheduleAssignable()
            ->inRandomOrder()
            ->first();

        if ($available) {
            return $available;
        }

        // 3. Load-balancing fallback: auto + self-toggled available,
        //    regardless of schedule or online status. Unavailable enforcers
        //    (force_unavailable or self-toggled) are never picked here.
        return (clone $base)
            ->where('availability_override', 'auto')
            ->where('is_available', true)
            ->withCount(['assignedCitizenReports as open_reports_count' => function ($query) {
                $query->whereNotIn('status', ['resolved', 'rejected']);
            }])
            ->orderBy('open_reports_count')
            ->orderByRaw('RANDOM()')
            ->first();
    }

    /**
     * Check if an enforcer is currently within their scheduled hours.
     */
    public static function isEnforcerCurrentlyScheduled(User $enforcer): bool
    {
        $dayOfWeek = now()->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday
        $time = now()->format('H:i:s');

        return EnforcerSchedule::where('enforcer_id', $enforcer->id)
            ->whereJsonContains('days', $dayOfWeek)
            ->whereJsonContains('is_active_days', [(string) $dayOfWeek => true])
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->exists();
    }
}