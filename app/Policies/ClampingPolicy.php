<?php

namespace App\Policies;

use App\Enums\ClampingStatus;
use App\Enums\Role;
use App\Models\ClampingRecord;
use App\Models\User;

class ClampingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, ClampingRecord $clampingRecord): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isRole(
            Role::SuperAdmin,
            Role::Administrator,
            Role::ClampingOfficer
        );
    }

    public function markPaid(User $user, ClampingRecord $clampingRecord): bool
    {
        if (! $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Cashier)) {
            return false;
        }
        return $clampingRecord->status === ClampingStatus::AwaitingPayment;
    }

    public function markWaitingRelease(User $user, ClampingRecord $clampingRecord): bool
    {
        if (! $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Cashier)) {
            return false;
        }
        return $clampingRecord->status === ClampingStatus::Paid;
    }

    public function processRelease(User $user, ClampingRecord $clampingRecord): bool
    {
        if (! $user->isRole(Role::SuperAdmin, Role::Administrator, Role::FrontDesk)) {
            return false;
        }
        return $clampingRecord->status === ClampingStatus::WaitingRelease;
    }

    public function referToImpounding(User $user): bool
    {
        return $user->isRole(
            Role::SuperAdmin,
            Role::Administrator,
            Role::Enforcer
        );
    }
}
