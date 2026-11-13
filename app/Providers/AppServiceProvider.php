<?php

namespace App\Providers;

use App\Models\MovementDetail;
use App\Models\Report;
use App\Observers\MovementDetailObserver;
use App\Policies\ReportPolicy;
use App\Support\ApiValidator;
use App\Support\ValidationMessages;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
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
        // Mensajes de validación en español (lang/es). Se fija en código para no depender de APP_LOCALE ni de la caché de configuración
        $this->app->setLocale('es');

        // Un solo texto por regla estándar en todos los formularios (ver App\Support\ValidationMessages)
        Validator::resolver(function ($translator, $data, $rules, $messages, $attributes) {
            return new ApiValidator($translator, $data, $rules, ValidationMessages::clean($messages), $attributes);
        });

        Gate::policy(Report::class, ReportPolicy::class);
        MovementDetail::observe(MovementDetailObserver::class);
    }
}
