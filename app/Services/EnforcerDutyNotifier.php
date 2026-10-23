<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\SystemNotification;
use App\Models\User;

class EnforcerDutyNotifier
{
    /**
     * Sync an enforcer's duty state and notify admins on transitions.
     *
     * Only notifies for on-duty transitions (online or offline). The last
     * observed state is always persisted so repeated pings are deduplicated.
     */
    public static function sync(User $enforcer): void
    {
        if (! $enforcer->isRole(Role::Enforcer)) {
            return;
        }

        $state = $enforcer->dutyState();
        $previous = $enforcer->last_duty_state;

        if ($previous === $state) {
            return;
        }

        $enforcer->forceFill(['last_duty_state' => $state])->saveQuietly();

        // Do not notify on the first observation of an enforcer's state.
        if ($previous === null) {
            return;
        }

        [$type, $title, $message] = match ($state) {
            'on_duty_online' => [
                'enforcer_on_duty_online',
                'Enforcer On Duty (Online)',
                "{$enforcer->name} is now on duty and online.",
            ],
            'on_duty_offline' => [
                'enforcer_on_duty_offline',
                'Enforcer On Duty (Offline)',
                "{$enforcer->name} is scheduled on duty but is currently offline.",
            ],
            default => [null, null, null],
        };

        if ($type === null) {
            return;
        }

        $admins = User::whereIn('role', [Role::SuperAdmin, Role::Administrator])->get();

        foreach ($admins as $admin) {
            SystemNotification::notify($admin, $type, $title, $message, [
                'enforcer_id' => $enforcer->id,
                'duty_state' => $state,
            ]);
        }
    }
}
