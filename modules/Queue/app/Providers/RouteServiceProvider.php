<?php

namespace Modules\Queue\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Queue';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', 'auth', 'settings'])
            ->prefix('panel/settings/queue')
            ->name('settings.queue.')
            ->group(module_path($this->name, 'routes/web.php'));
    }
}
