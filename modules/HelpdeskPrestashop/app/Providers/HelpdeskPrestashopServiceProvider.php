<?php

namespace Modules\HelpdeskPrestashop\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskPrestashop\Console\Commands\TestConnectionCommand;
use Modules\HelpdeskPrestashop\Console\Commands\WarmPsCacheCommand;
use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\HelpdeskPrestashop\Events\PsCartUpdated;
use Modules\HelpdeskPrestashop\Events\PsCustomerUpdated;
use Modules\HelpdeskPrestashop\Events\PsPriceDropped;
use Modules\HelpdeskPrestashop\Listeners\BroadcastCartUpdated;
use Modules\HelpdeskPrestashop\Listeners\InvalidateCatalogCache;
use Modules\HelpdeskPrestashop\Listeners\InvalidateCustomerContextCache;
use Modules\HelpdeskPrestashop\Services\Ext\SettingsOverrides;
use Modules\Theme\Services\NavService;
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

        $this->mergeExtensionConfig();
    }

    /**
     * Extensiones del módulo: cada config/ext/<nombre>.php devuelve
     *   'write_actions'    => acciones del bridge que mutan (idempotencia),
     *   'permissions'      => [nombre => descripción],
     *   'role_permissions' => [rol => [permisos]],
     *   'listeners'        => [Evento::class => [Listener::class, …]],
     *   + cualquier ajuste propio, accesible en config('helpdeskprestashop.ext.<nombre>.*').
     */
    protected function mergeExtensionConfig(): void
    {
        $writeActions = (array) config('helpdeskprestashop.ext_write_actions', []);

        foreach (glob(module_path($this->moduleName, 'config/ext/*.php')) ?: [] as $file) {
            $ext = require $file;
            if (! is_array($ext)) {
                continue;
            }
            config(['helpdeskprestashop.ext.'.basename($file, '.php') => $ext]);
            $writeActions = array_merge($writeActions, (array) ($ext['write_actions'] ?? []));
        }

        config(['helpdeskprestashop.ext_write_actions' => array_values(array_unique($writeActions))]);
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), 'helpdeskprestashop');
        $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), 'helpdeskprestashop');
        $this->registerRoutes();

        // Ajustes guardados en BD (pantalla de Ajustes del módulo) que
        // sobrescriben config/config.php y config/ext/*.php en tiempo de
        // ejecución. La clase la aporta la extensión "settings".
        if (class_exists(SettingsOverrides::class)) {
            rescue(fn () => SettingsOverrides::apply(), report: false);
        }
        $this->registerNav();

        if ($this->app->runningInConsole()) {
            $this->commands([
                TestConnectionCommand::class,
                WarmPsCacheCommand::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('helpdeskprestashop:warm-cache')
                ->everyThirtyMinutes()
                ->withoutOverlapping()
                ->when(fn () => helpdesk_prestashop_enabled())
                ->runInBackground();
        });

        // A price drop or restock makes the cached catalog stale.
        Event::listen(PsPriceDropped::class, InvalidateCatalogCache::class);
        Event::listen(PsBackInStock::class, InvalidateCatalogCache::class);

        // A profile update in PrestaShop makes the cached helpdesk context stale.
        Event::listen(PsCustomerUpdated::class, InvalidateCustomerContextCache::class);

        // A cart change in PrestaShop invalidates the cached context and, if the
        // customer has an open conversation right now, refreshes the panel live.
        Event::listen(PsCartUpdated::class, BroadcastCartUpdated::class);

        // Listeners declarados por las extensiones (config/ext/*.php).
        foreach ((array) config('helpdeskprestashop.ext', []) as $ext) {
            foreach ((array) ($ext['listeners'] ?? []) as $event => $listeners) {
                foreach ((array) $listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    /**
     * Pantallas de operación de la integración (registro del puente, eventos
     * recibidos, mapeo de estados y auditoría de acciones) en Ajustes.
     */
    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk · PrestaShop',
            'order' => 285,
            'items' => [
                ['label' => 'Registro del puente', 'route' => 'manager.helpdesk.ps.ext.opslog.bridge-log', 'permission' => 'helpdeskprestashop.ops.view'],
                ['label' => 'Eventos recibidos', 'route' => 'manager.helpdesk.ps.ext.opslog.events', 'permission' => 'helpdeskprestashop.ops.view'],
                ['label' => 'Mapeo de estados', 'route' => 'manager.helpdesk.ps.ext.opsmap.state-map', 'permission' => 'helpdeskprestashop.statemap.manage'],
                ['label' => 'Avisos de cambio de estado', 'route' => 'manager.helpdesk.ps.ext.opsmap.state-notices', 'permission' => 'helpdeskprestashop.statemap.manage'],
                ['label' => 'Auditoría de acciones', 'route' => 'manager.helpdesk.ps.ext.opsmap.audit', 'permission' => 'helpdeskprestashop.ops.view'],
                ['label' => 'Métricas del chat', 'route' => 'manager.helpdesk.ps.ext.metrics.index', 'permission' => 'helpdeskprestashop.metrics.view'],
                ['label' => 'Ajustes del chat', 'route' => 'manager.helpdesk.ps.ext.settings.index', 'permission' => 'helpdeskprestashop.settings.manage'],
            ],
        ]);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path('helpdeskprestashop.php'),
        ], 'config');
    }

    protected function registerRoutes(): void
    {
        // Gate de un solo punto por grupo: helpdesk_prestashop_enabled()
        // (modulo instalado + toggle admin) — sin esto, un admin que apaga la
        // integracion en Settings solo ocultaba el menu, pero change_status/
        // set_tracking/start_return, la API y el webhook seguian alcanzables.
        $managers = module_path($this->moduleName, 'routes/managers.php');
        if (file_exists($managers)) {
            Route::middleware(['web', 'auth', 'integration.enabled:prestashop'])
                ->prefix('panel/helpdesk')
                ->group($managers);
        }

        // Rutas de las extensiones: mismo grupo y gate que managers.php.
        foreach (glob(module_path($this->moduleName, 'routes/managers.d/*.php')) ?: [] as $extRoutes) {
            Route::middleware(['web', 'auth', 'integration.enabled:prestashop'])
                ->prefix('panel/helpdesk')
                ->group($extRoutes);
        }

        Route::middleware(['api', 'auth:sanctum', 'throttle:60,1', 'integration.enabled:prestashop'])
            ->prefix('api/helpdeskprestashop')
            ->name('api.helpdeskprestashop.')
            ->group(module_path($this->moduleName, 'routes/api.php'));

        // Webhook receiver: authenticated via HMAC, no Sanctum. Sin la puerta
        // integration.enabled: con la integración apagada respondía 404 y
        // PrestaShop reintentaba sin fin; el controlador ya contesta 204
        // ("recibido, no se procesa") sin despachar nada.
        Route::middleware(['api', 'throttle:120,1'])
            ->prefix('api/helpdeskprestashop/webhooks')
            ->name('api.helpdeskprestashop.webhooks.')
            ->group(module_path($this->moduleName, 'routes/webhooks.php'));
    }
}
