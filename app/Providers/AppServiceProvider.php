<?php

namespace App\Providers;

use App\Mcp\ActorResolver;
use App\Models\MovementDetail;
use App\Models\Report;
use App\Observers\MovementDetailObserver;
use App\Policies\ReportPolicy;
use App\Support\ApiValidator;
use App\Support\ValidationMessages;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Passport::ignoreRoutes();
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

        // La llave de IA solo la acepta el asistente (ActorResolver); la API normal la trata como inexistente
        Sanctum::authenticateAccessTokensUsing(
            fn ($access_token, bool $is_valid) => $is_valid && $access_token->name !== ActorResolver::KEY_NAME
        );

        Passport::authorizationView('oauth.authorize');
        Passport::tokensCan(['mcp:use' => 'Usar el asistente de Kanri']);
        Passport::tokensExpireIn(now()->addYears(config('asistente.mcp_token_anios')));
        Passport::refreshTokensExpireIn(now()->addYears(config('asistente.mcp_token_anios')));

        RateLimiter::for('mcp', fn (Request $request) => Limit::perMinute(config('asistente.peticiones_por_minuto'))
            ->by($request->user('mcp')?->id ?: $request->ip()));
    }
}
