<?php

namespace Modules\HelpdeskErp\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\HelpdeskErp\Console\Commands\BackfillErpLinksCommand;
use Modules\HelpdeskErp\Console\Commands\WarmErpCacheCommand;
use Modules\HelpdeskErp\Http\Controllers\Api\WebhookController;
use Modules\HelpdeskErp\Listeners\DispatchErpLinkJob;
use Nwidart\Modules\Facades\Module;

class HelpdeskErpServiceProvider extends ServiceProvider
{
    protected string $name = 'HelpdeskErp';

    protected string $nameLower = 'helpdeskErp';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->name, 'config/config.php'),
            $this->nameLower
        );
    }

    public function boot(): void
    {
        if (Module::find($this->name)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerRoutes();
        $this->registerCommands();
        $this->registerSchedule();
        $this->registerEventListeners();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->name, 'config/config.php') => config_path($this->nameLower.'.php'),
        ], 'config');
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])
            ->prefix('api/helpdeskErp')
            ->name('api.helpdeskErp.')
            ->group(module_path($this->name, 'routes/api.php'));

        // Webhooks use HMAC auth in the controller — rate limited to prevent abuse
        Route::middleware(['api', 'throttle:30,1'])
            ->prefix('api/helpdeskErp/webhooks')
            ->name('api.helpdeskErp.webhooks.')
            ->group(function () {
                Route::post('/orders-ready', [
                    WebhookController::class,
                    'ordersReady',
                ])->name('orders-ready');
            });
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                WarmErpCacheCommand::class,
                BackfillErpLinksCommand::class,
            ]);
        }
    }

    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('helpdeskerp:warm-cache')
                ->everyThirtyMinutes()
                ->withoutOverlapping();
        });
    }

    protected function registerEventListeners(): void
    {
        Event::listen(ConversationCreated::class, DispatchErpLinkJob::class);
    }
}
