<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

        $this->configureApplicationDefaults();
    }

    private function configureApplicationDefaults(): void
    {
        // Seguridad 29-sep-2026: antes memory_limit=-1 también en peticiones web
        // (DoS por memoria). En consola (colas, comandos) se respeta el php.ini
        // del CLI (hoy -1); en web se sube a un límite acotado para no romper
        // exportaciones/PDF pesados que antes contaban con memoria ilimitada.
        if (! $this->app->runningInConsole()) {
            ini_set('memory_limit', (string) env('WEB_MEMORY_LIMIT', '1024M'));
        }
        ini_set('pcre.backtrack_limit', '1000000000');

        // Force HTTPS if request is secure or header indicates it
        if (request()->isSecure() || (! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0)) {
            URL::forceScheme('https');
        }
    }
}
