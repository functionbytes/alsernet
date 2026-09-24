<?php

namespace Modules\HelpdeskErp\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\CustomerExternalId;
use Modules\HelpdeskErp\Broadcasting\LinkErpCustomerChannel;
use Modules\HelpdeskErp\Console\Commands\BackfillErpLinksCommand;
use Modules\HelpdeskErp\Console\Commands\WarmErpCacheCommand;
use Modules\HelpdeskErp\Events\ErpOrdersReady;
use Modules\HelpdeskErp\Http\Controllers\Api\WebhookController;
use Modules\HelpdeskErp\Listeners\DispatchErpLinkJob;
use Modules\HelpdeskErp\Listeners\DispatchErpLinkOnPrestashopLink;
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
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), 'helpdeskerp');
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
        $managers = module_path($this->name, 'routes/managers.php');
        if (file_exists($managers)) {
            Route::middleware(['web', 'auth'])
                ->prefix('panel/helpdesk')
                ->group($managers);
        }

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
                ->withoutOverlapping()
                ->when(fn () => helpdesk_erp_enabled());
        });
    }

    protected function registerEventListeners(): void
    {
        Event::listen(ConversationCreated::class, DispatchErpLinkJob::class);

        // Al vincular un contacto con PrestaShop se intenta vincular también
        // con Gestión (CODIGO_INTERNET = id de PrestaShop). No hay evento de
        // dominio para eso: se observa el 'created' del external id.
        CustomerExternalId::observe(DispatchErpLinkOnPrestashopLink::class);

        $this->registerBroadcastChannels();
    }

    /**
     * Canal privado por contacto del helpdesk para "pedidos listos"
     * (ErpOrdersReady). El canal por hash de email depende de que el email de
     * Gestión y el del helpdesk coincidan; este no. Se autoriza igual que las
     * rutas de Gestión del chat (ErpChatController::resolve): permiso
     * helpdeskerp.view y el contacto en alguna bandeja del agente.
     */
    protected function registerBroadcastChannels(): void
    {
        Broadcast::channel(ErpOrdersReady::CUSTOMER_CHANNEL_PREFIX.'{customerId}', LinkErpCustomerChannel::class);
    }
}
