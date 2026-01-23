<?php

namespace Modules\Returns\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Return';

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
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        // Callcenter returns routes
        Route::middleware(['web', 'auth', 'role:callcenter|super-admin'])
            ->prefix('callcenter/returns')
            ->name('callcenter.returns.')
            ->group(module_path($this->name, 'routes/callcenters.php'));

        // Manager returns routes
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('settings/returns')
            ->name('returns.backups.')
            ->group(module_path($this->name, 'routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware(['api', 'throttle:60,1'])
            ->prefix('api/returns')
            ->name('api.returns.')
            ->group(module_path($this->name, 'routes/api.php'));
    }
}
