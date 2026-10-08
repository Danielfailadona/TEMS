<?php

namespace App\Providers;

use App\Console\Commands\GeocodeMissingZones;
use App\Models\Appeal;
use App\Models\ClampingRecord;
use App\Models\ImpoundingRecord;
use App\Policies\AppealPolicy;
use App\Policies\ClampingPolicy;
use App\Policies\ImpoundingRecordPolicy;
use App\View\Composers\NavigationComposer;
use App\View\Composers\PageTitleComposer;
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
        $this->commands([
            GeocodeMissingZones::class,
        ]);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Gate::policy(Appeal::class, AppealPolicy::class);
        Gate::policy(ClampingRecord::class, ClampingPolicy::class);
        Gate::policy(ImpoundingRecord::class, ImpoundingRecordPolicy::class);
        View::composer('layouts.app', NavigationComposer::class);
        View::composer('*', PageTitleComposer::class);

        if (empty(env('APP_URL'))) {
            Vite::createAssetPathsUsing(fn ($path, $secure) => '/' . $path);
            URL::forceRootUrl('/');
        }
    }
}
