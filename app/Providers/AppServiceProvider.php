<?php

namespace App\Providers;

use App\Models\MovementDetail;
use App\Models\Report;
use App\Observers\MovementDetailObserver;
use App\Policies\ReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Report::class, ReportPolicy::class);
        MovementDetail::observe(MovementDetailObserver::class);
    }
}
