<?php

namespace Modules\HelpdeskPrestashop\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskPrestashop\Console\Commands\TestConnectionCommand;
use Modules\HelpdeskPrestashop\Console\Commands\WarmPsCacheCommand;
use Nwidart\Modules\Facades\Module;

class HelpdeskPrestashopServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskPrestashop';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            'helpdeskprestashop'
        );
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                TestConnectionCommand::class,
                WarmPsCacheCommand::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('helpdeskprestashop:warm-cache')
                ->everyThirtyMinutes()
                ->withoutOverlapping();
        });
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path('helpdeskprestashop.php'),
        ], 'config');
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
            ->prefix('api/helpdeskprestashop')
            ->name('api.helpdeskprestashop.')
            ->group(module_path($this->moduleName, 'routes/api.php'));

        // Webhook receiver: authenticated via HMAC, no Sanctum
        Route::middleware(['api', 'throttle:120,1'])
            ->prefix('api/helpdeskprestashop/webhooks')
            ->name('api.helpdeskprestashop.webhooks.')
            ->group(module_path($this->moduleName, 'routes/webhooks.php'));
    }
}
