<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Appeal;
use App\Models\User;

class AppealPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appeal $appeal): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $appeal->submitted_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isRole(Role::VehicleOwner);
    }

    public function update(User $user, Appeal $appeal): bool
    {
        if (! $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Enforcer)) {
            return false;
        }

        if ($appeal->submitted_by === $user->id) {
            return false;
        }

        return true;
    }
}
