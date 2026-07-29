<?php

namespace Modules\Prestashop\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Called before routes are registered.
     *
     * @param  \Illuminate\Routing\Router  $router
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapManagerRoutes();
        $this->mapApiRoutes();
    }

    /**
     * Define the "theme" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapManagerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'settings'])
            ->prefix('panel/settings/prestashop')
            ->name('settings.prestashop.')
            ->group(module_path('Prestashop', 'routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware(['api', 'throttle:60,1'])
            ->prefix('api/prestashop')
            ->name('api.prestashop.')
            ->group(module_path('Prestashop', 'routes/api.php'));
    }
}
