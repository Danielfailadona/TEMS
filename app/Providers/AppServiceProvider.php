<?php

namespace App\Providers;

use App\Models\Appeal;
use App\Models\ClampingRecord;
use App\Policies\AppealPolicy;
use App\Policies\ClampingPolicy;
use App\View\Composers\NavigationComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Gate::policy(Appeal::class, AppealPolicy::class);
        Gate::policy(ClampingRecord::class, ClampingPolicy::class);
        View::composer('layouts.app', NavigationComposer::class);

        if (empty(env('APP_URL'))) {
            Vite::createAssetPathsUsing(fn ($path, $secure) => '/' . $path);
            URL::forceRootUrl('/');
        }
    }
}
