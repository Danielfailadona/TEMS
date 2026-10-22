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

        return $citizenReport->assigned_to === $user->id
            || $citizenReport->status === \App\Enums\ReportStatus::Pending->value;
    }

    public function update(User $user, CitizenReport $citizenReport): bool
    {
        if ($user->isRole(Role::SuperAdmin, Role::Administrator)) {
            return true;
        }

        // Enforcers may act on reports assigned to them, or claim fresh
        // pending reports from the shared pool.
        return $user->isRole(Role::Enforcer)
            && ($citizenReport->assigned_to === $user->id
                || $citizenReport->status === \App\Enums\ReportStatus::Pending->value);
    }

    public function claim(User $user, CitizenReport $citizenReport): bool
    {
        return $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Enforcer)
            && $citizenReport->status === \App\Enums\ReportStatus::Pending->value;
    }

    public function delete(User $user, CitizenReport $citizenReport): bool
    {
        return $user->isSuperAdmin();
    }
}
