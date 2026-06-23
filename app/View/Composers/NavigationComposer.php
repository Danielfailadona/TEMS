<?php

namespace App\View\Composers;

use App\Enums\Role;
use Illuminate\View\View;

class NavigationComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        $groups = [];

        if (! $user) {
            $view->with('navGroups', $groups);

            return;
        }

        if ($user->isRole(Role::VehicleOwner)) {
            $groups['operations'] = [
                'label' => 'OPERATIONS',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'speedometer2'],
                    ['label' => 'My Citations', 'route' => 'owner.citations', 'icon' => 'file-earmark-text'],
                    ['label' => 'My Vehicles', 'route' => 'owner.vehicles', 'icon' => 'car-front'],
                    ['label' => 'Clamping Status', 'route' => 'owner.clamping', 'icon' => 'lock'],
                    ['label' => 'Appeals', 'route' => 'appeals.index', 'icon' => 'exclamation-circle'],
                ],
            ];
        } else {
            $operations = [];
            $monitoring = [];
            $administration = [];

            $operations[] = ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'speedometer2'];

            if ($user->isRole(Role::SuperAdmin, Role::Administrator, Role::Enforcer)) {
                $operations[] = ['label' => 'Citations', 'route' => 'citations.index', 'icon' => 'file-earmark-text'];
            }

            if ($user->isRole(Role::SuperAdmin, Role::Administrator, Role::Enforcer)) {
                $operations[] = ['label' => 'Appeals', 'route' => 'appeals.index', 'icon' => 'exclamation-circle'];
            }

            if ($user->isRole(Role::SuperAdmin, Role::Administrator, Role::Cashier)) {
                $operations[] = ['label' => 'Payments', 'route' => 'payments.index', 'icon' => 'cash-coin'];
            }

            if ($user->isRole(Role::SuperAdmin, Role::Administrator, Role::ClampingOfficer)) {
                $operations[] = ['label' => 'Clamping', 'route' => 'clamping.index', 'icon' => 'lock'];
            }

            if ($user->isRole(Role::SuperAdmin, Role::Administrator, Role::ClampingOfficer, Role::Cashier, Role::FrontDesk)) {
                $operations[] = ['label' => 'Impounding', 'route' => 'impounding.index', 'icon' => 'truck'];
            }

            if ($user->isRole(Role::SuperAdmin, Role::Administrator, Role::ClampingOfficer, Role::Enforcer)) {
                $operations[] = ['label' => 'Clamping Requests', 'route' => 'clamping-requests.index', 'icon' => 'inbox'];
            }

            if ($user->isRole(Role::SuperAdmin, Role::Administrator)) {
                $monitoring[] = ['label' => 'Tracking', 'route' => 'tracking.index', 'icon' => 'broadcast'];
                $monitoring[] = ['label' => 'Reports', 'route' => 'reports.index', 'icon' => 'graph-up'];

                $administration[] = ['label' => 'Users', 'route' => 'users.index', 'icon' => 'person-gear'];
                $administration[] = ['label' => 'Teams', 'route' => 'teams.index', 'icon' => 'people'];
                $administration[] = ['label' => 'Zones', 'route' => 'zones.index', 'icon' => 'geo-alt'];
                $administration[] = ['label' => 'Archives', 'route' => 'archives.index', 'icon' => 'archive'];
                $administration[] = ['label' => 'Audit Logs', 'route' => 'audit-logs.index', 'icon' => 'journal-text'];
            }

            if ($user->isRole(Role::Enforcer, Role::ClampingOfficer)) {
                $operations[] = ['label' => 'My Archives', 'route' => 'archives.index', 'icon' => 'archive'];
                $operations[] = ['label' => 'My Zone', 'route' => 'enforcer.zone', 'icon' => 'geo-alt-fill'];
            }

            if ($user->isRole(Role::FrontDesk)) {
                $operations[] = ['label' => 'Front Desk', 'route' => 'frontdesk.index', 'icon' => 'building'];
            }

            if ($operations) {
                $groups['operations'] = ['label' => 'OPERATIONS', 'items' => $operations];
            }
            if ($monitoring) {
                $groups['monitoring'] = ['label' => 'MONITORING', 'items' => $monitoring];
            }
            if ($administration) {
                $groups['administration'] = ['label' => 'ADMINISTRATION', 'items' => $administration];
            }
        }

        $view->with('navGroups', $groups);
    }
}
