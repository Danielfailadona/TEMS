<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Cashier);
    }

    public function create(User $user): bool
    {
        return $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Cashier);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->isRole(Role::SuperAdmin, Role::Administrator, Role::Cashier);
    }
}
