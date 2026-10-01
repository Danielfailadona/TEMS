<?php

namespace App\Policies;

use App\Enums\ImpoundingStatus;
use App\Enums\Role;
use App\Models\ImpoundingRecord;
use App\Models\User;

class ImpoundingRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, ImpoundingRecord $impoundingRecord): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isRole(
            Role::SuperAdmin,
            Role::Administrator,
            Role::Enforcer
        );
    }

    public function markPaid(User $user, ImpoundingRecord $impoundingRecord): bool
    {
        if (! $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Cashier)) {
            return false;
        }
        return $impoundingRecord->status === ImpoundingStatus::Impounded || $impoundingRecord->status === ImpoundingStatus::AwaitingPayment;
    }

    public function markWaitingRelease(User $user, ImpoundingRecord $impoundingRecord): bool
    {
        if (! $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Cashier)) {
            return false;
        }
        return $impoundingRecord->status === ImpoundingStatus::Paid;
    }

    public function processRelease(User $user, ImpoundingRecord $impoundingRecord): bool
    {
        if (! $user->isRole(Role::SuperAdmin, Role::Administrator, Role::FrontDesk)) {
            return false;
        }
        return $impoundingRecord->status === ImpoundingStatus::WaitingRelease;
    }
}