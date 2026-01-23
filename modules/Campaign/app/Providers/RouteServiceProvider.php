<?php

namespace Modules\Campaign\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions that use the traditonal queue syntax.
     *
     * @var string
     */
    protected $namespace = 'modules\Campaign\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
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
        $this->mapManagerRoutes();
    }

    /**
     * Define the API routes for the application.
     *
     * These routes are stateless, use the "api" middleware group, and are protected by Sanctum authentication.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api')
            ->name('api.')
            ->group(base_path('modules/Campaign/routes/api.php'));
    }

    /**
     * Define the manager routes for the application.
     */
    protected function mapManagerRoutes(): void
    {
        // Manager routes already include middleware configuration in the route file
        require base_path('modules/Campaign/routes/managers.php');
    }
}
