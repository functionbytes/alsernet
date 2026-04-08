<?php

namespace Modules\Pulse\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Pulse';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
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
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapSettingsRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', 'auth', 'can:view-pulse'])
            ->prefix('panel/pulse')
            ->name('pulse.')
            ->group(module_path($this->name, 'routes/web.php'));
    }

    /**
     * Define the settings routes for the application.
     */
    protected function mapSettingsRoutes(): void
    {
        Route::middleware(['web', 'auth', 'settings'])
            ->prefix('panel/setting/pulse')
            ->name('settings.pulse.')
            ->group(module_path($this->name, 'routes/settings.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, 'routes/api.php'));
    }
}
