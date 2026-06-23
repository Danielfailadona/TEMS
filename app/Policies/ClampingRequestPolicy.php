<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ClampingRequest;
use App\Models\User;

class ClampingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin, Role::Administrator, Role::ClampingOfficer, Role::Enforcer);
    }

    public function view(User $user, ClampingRequest $clampingRequest): bool
    {
        if ($user->isRole(Role::SuperAdmin, Role::Administrator, Role::ClampingOfficer)) {
            return true;
        }

        return $clampingRequest->assigned_to === $user->id;
    }

    public function update(User $user, ClampingRequest $clampingRequest): bool
    {
        return $user->isRole(Role::SuperAdmin, Role::Administrator);
    }
}
