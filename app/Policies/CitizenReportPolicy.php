<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CitizenReport;
use App\Models\User;

class CitizenReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Enforcer);
    }

    public function view(User $user, CitizenReport $citizenReport): bool
    {
        if ($user->isRole(Role::SuperAdmin, Role::Administrator)) {
            return true;
        }

        if (! $user->isRole(Role::Enforcer)) {
            return false;
        }

        // Enforcers work only from their assigned reports — the pending
        // pool is admin-side.
        return $citizenReport->assigned_to === $user->id;
    }

    public function update(User $user, CitizenReport $citizenReport): bool
    {
        if ($user->isRole(Role::SuperAdmin, Role::Administrator)) {
            return true;
        }

        // Enforcers may act only on reports assigned to them.
        return $user->isRole(Role::Enforcer)
            && $citizenReport->assigned_to === $user->id;
    }

    public function claim(User $user, CitizenReport $citizenReport): bool
    {
        // Pool claiming is admin-side only; enforcers work from assignments.
        return $user->isRole(Role::SuperAdmin, Role::Administrator)
            && $citizenReport->status === \App\Enums\ReportStatus::Pending->value;
    }

    public function reject(User $user, CitizenReport $citizenReport): bool
    {
        // Rejecting a report is an admin decision.
        return $user->isRole(Role::SuperAdmin, Role::Administrator);
    }

    public function delete(User $user, CitizenReport $citizenReport): bool
    {
        return $user->isSuperAdmin();
    }
}
