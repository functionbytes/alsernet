<?php

namespace Modules\Prestashop\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Called before routes are registered.
     *
     * @param  Router  $router
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
        // 29-sep-2026: 'settings' (CheckSettings) no comprueba nada; el permiso
        // real es prestashop.settings.manage (contraseña de la BD de la tienda).
        Route::middleware(['web', 'auth', 'settings', 'can:prestashop.settings.manage'])
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
