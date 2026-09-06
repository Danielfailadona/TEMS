<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Citation;
use App\Models\User;

class CitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Citation $citation): bool
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

    public function update(User $user, Citation $citation): bool
    {
        return $user->isRole(Role::SuperAdmin, Role::Administrator);
    }

    public function handoff(User $user, Citation $citation): bool
    {
        if ($user->isRole(Role::SuperAdmin, Role::Administrator)) {
            return true;
        }

        return $citation->issued_by === $user->id
            && $user->isRole(Role::Enforcer, Role::ClampingOfficer);
    }

    public function printCitation(User $user, Citation $citation): bool
    {
        return $user->isStaff();
    }
}
