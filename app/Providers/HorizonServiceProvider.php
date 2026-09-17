<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');

        // Sin esto el dashboard (gráficas de Metrics) se queda sin datos:
        // horizon:snapshot es quien guarda cada muestra que trim_snapshots
        // luego recorta. No viene programado por defecto al publicar el
        // paquete — mismo patrón que el resto del proyecto (cada provider
        // agenda lo suyo con Schedule::, ver HelpdeskTicketsServiceProvider
        // ::boot() para 'ticket:autooverdue'), en vez de un Kernel central.
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('horizon:snapshot')->everyFiveMinutes();
        });
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     * Mismo criterio que Modules\Telescope\Providers\TelescopeServiceProvider
     * ::configureAuthorization() — abierto en local/development, denegado
     * por defecto en el resto, en vez de una lista de emails vacía (que
     * Horizon trae por defecto y dejaría el panel inaccesible para todos,
     * incluso en local, hasta rellenarla a mano).
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if ($this->app->environment('local', 'development')) {
                return true;
            }

            return false;
        });
    }
}
