<?php

namespace Modules\Modules\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Modules';

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
        $this->mapManagerSettingsRoutes();
        $this->mapApiRoutes();
    }

    /**
     * Define the manager backups routes (web + API).
     *
     * These routes are protected with manager/super-admin role requirement.
     */
    protected function mapManagerSettingsRoutes(): void
    {
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('settings/modules')
            ->name('settings.modules.')
            ->group(function (): void {
                // Load view routes (GET)
                require module_path($this->name, 'routes/web.php');

            });
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless and public.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(function (): void {
            require module_path($this->name, 'routes/api.php');
        });
    }
}
