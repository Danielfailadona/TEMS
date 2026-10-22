<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;

class AutoAssignService
{
    /**
     * Pick a random enforcer who is eligible for assignment.
     *
     * Eligibility = an approved, active account, Enforcer role,
     * within their scheduled duty hours AND currently online (an active GPS ping).
     * When no enforcer matches, an enforcer with the fewest open assignments
     * is returned as a fallback so work is never left unassigned.
     */
    public function pickEnforcer(): ?User
    {
        $base = User::query()
            ->where('role', Role::Enforcer->value)
            ->where('account_status', 'approved')
            ->where('is_active', true);

        // First try: enforcers who are schedule-assignable (within schedule AND online)
        $available = (clone $base)->scheduleAssignable()
            ->inRandomOrder()
            ->first();

        if ($available) {
            return $available;
        }

        // Fallback: enforcer with fewest open assignments (regardless of schedule)
        return (clone $base)
            ->withCount(['assignedCitizenReports as open_reports_count' => function ($query) {
                $query->whereNotIn('status', ['resolved', 'rejected']);
            }])
            ->orderBy('open_reports_count')
            ->orderByRaw('RANDOM()')
            ->first();
    }
}