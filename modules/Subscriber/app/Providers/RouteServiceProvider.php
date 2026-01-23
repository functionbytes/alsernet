<?php

namespace Modules\Subscriber\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Subscriber';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapManagerRoutes();
        $this->mapApiRoutes();
        $this->mapShopRoutes();
    }

    protected function mapManagerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('subscribers')
            ->name('subscribers.')
            ->group(module_path($this->name, 'routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware(['api', 'throttle:60,1'])
            ->prefix('api/subscribers')
            ->name('api.subscribers.')
            ->group(module_path($this->name, 'routes/api.php'));
    }

    protected function mapShopRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('shop/subscribers')
            ->name('shop.subscribers.')
            ->group(module_path($this->name, 'routes/shops.php'));
    }
}
