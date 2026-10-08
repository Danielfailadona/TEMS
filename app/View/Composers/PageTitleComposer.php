<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Route;

class PageTitleComposer
{
    public function compose(View $view)
    {
        $routeName = Route::currentRouteName() ?? '';
        $title = $this->resolveTitle($routeName);
        $view->with('pageTitle', $title);
    }

    protected function resolveTitle(string $routeName): string
    {
        $map = [
            'citations.*'          => 'Citations',
            'appeals.*'            => 'Appeals',
            'clamping.*'           => 'Clamping',
            'impounding.*'         => 'Impounding',
            'clamping-requests.*'  => 'Clamping Requests',
            'dashboard'            => 'Dashboard',
            'dashboard.*'          => 'Dashboard',
            'users.*'              => 'Users',
            'settings.*'           => 'Settings',
            'archives.*'           => 'Archives',
            'audit-logs.*'         => 'Audit Logs',
            'payments.*'           => 'Payments',
            'tracking.*'           => 'Tracking',
            'reports.*'            => 'Reports',
            'zones.*'              => 'Zones',
            'teams.*'              => 'Teams',
            'frontdesk.*'          => 'Front Desk',
        ];

        foreach ($map as $pattern => $title) {
            if ($this->routeMatches($routeName, $pattern)) {
                return $title;
            }
        }

        return 'Dashboard';
    }

    protected function routeMatches(string $routeName, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = '^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$';
        return (bool) preg_match('/' . $regex . '/', $routeName);
    }
}