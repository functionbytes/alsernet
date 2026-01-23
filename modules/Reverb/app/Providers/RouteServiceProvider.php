<?php

namespace Modules\Reverb\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/reverb')
            ->name('api.reverb.')
            ->group(module_path('Reverb', 'routes/api.php'));
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('settings/reverb')
            ->name('reverb.backups.')
            ->group(module_path('Reverb', 'routes/web.php'));
    }
}
