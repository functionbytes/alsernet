<?php

namespace Modules\HelpdeskErp\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminChatMiddleware;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminMetricsRecorder;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminPurgeMetricsCommand;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminSettingsOverrides;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

/**
 * Extensiones de HelpdeskErp (Gestión en el chat).
 *
 *  - config/ext/<nombre>.php  → config('helpdeskErp.ext.<nombre>'), con sus
 *    'permissions' / 'role_permissions' (los recoge HelpdeskErpPermissionsSeeder)
 *    y 'listeners'.
 *  - routes/managers.d/<nombre>.php → mismo grupo que routes/managers.php
 *    (web + auth, prefijo panel/helpdesk; el nombre y el sub-prefijo los
 *    pone cada fichero: manager.helpdesk.erp.<nombre>.*).
 *  - Ajustes guardados en helpdesk_erp_settings aplicados sobre config().
 *  - Métricas de uso de las rutas manager.helpdesk.erp.chat.*.
 *  - Menú «Helpdesk · Gestión (ERP)».
 *
 * Se registra DESPUÉS de HelpdeskErpServiceProvider (module.json), así que su
 * config ya está fusionada cuando corre register() aquí.
 */
class ErpChatExtServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskErp';

    public function register(): void
    {
        $this->mergeExtensionConfig();

        $this->app->singleton(ErpAdminMetricsRecorder::class);
    }

    /**
     * Cada config/ext/<nombre>.php → config('helpdeskErp.ext.<nombre>').
     * Además deja en config('helpdeskErp.ext_permissions') y
     * config('helpdeskErp.ext_role_permissions') el agregado de todas.
     */
    protected function mergeExtensionConfig(): void
    {
        $permissions = [];
        $rolePermissions = [];

        foreach ($this->extConfigFiles() as $file) {
            $ext = require $file;
            if (! is_array($ext)) {
                continue;
            }

            $name = basename($file, '.php');
            // Lo que ya hubiera (p. ej. publicado en config/helpdeskErp.php) gana.
            $existing = config('helpdeskErp.ext.'.$name);
            config(['helpdeskErp.ext.'.$name => is_array($existing) ? array_replace_recursive($ext, $existing) : $ext]);

            foreach ((array) ($ext['permissions'] ?? []) as $perm => $description) {
                $permissions[(string) $perm] = (string) $description;
            }
            foreach ((array) ($ext['role_permissions'] ?? []) as $role => $perms) {
                $rolePermissions[$role] = array_values(array_unique(array_merge($rolePermissions[$role] ?? [], (array) $perms)));
            }
        }

        config([
            'helpdeskErp.ext_permissions' => $permissions,
            'helpdeskErp.ext_role_permissions' => $rolePermissions,
        ]);

        // Claves de primer nivel que usan los ajustes de Gestión. Si el
        // config del módulo aún no las trae, se toman las de serie de la
        // extensión "admin".
        $admin = (array) config('helpdeskErp.ext.admin', []);
        config(['helpdeskErp.chat_alerts' => array_replace(
            (array) ($admin['chat_alerts'] ?? []),
            (array) config('helpdeskErp.chat_alerts', []),
        )]);
        if (config('helpdeskErp.auto_link') === null) {
            config(['helpdeskErp.auto_link' => (bool) ($admin['auto_link'] ?? true)]);
        }
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerExtRoutes();

        // Ajustes de «Ajustes de Gestión» sobre config(). Tiene que ir en boot
        // (después del boot de HelpdeskErpServiceProvider, que es quien
        // pone el resto de la config del módulo).
        rescue(fn () => ErpAdminSettingsOverrides::apply(), report: false);

        $this->registerExtListeners();
        $this->registerMetrics();
        $this->registerNav();

        if ($this->app->runningInConsole()) {
            $this->commands([ErpAdminPurgeMetricsCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('helpdeskerp:purge-metrics')
                ->dailyAt('03:40')
                ->withoutOverlapping()
                ->runInBackground();
        });
    }

    /**
     * routes/managers.d/*.php con el mismo grupo que routes/managers.php
     * (ver HelpdeskErpServiceProvider::registerRoutes). Con las rutas en
     * caché no hace falta: ya vienen dentro.
     */
    protected function registerExtRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        foreach ($this->extRouteFiles() as $file) {
            Route::middleware(['web', 'auth'])
                ->prefix('panel/helpdesk')
                ->group($file);
        }
    }

    protected function registerExtListeners(): void
    {
        foreach ((array) config('helpdeskErp.ext', []) as $ext) {
            if (! is_array($ext)) {
                continue;
            }
            foreach ((array) ($ext['listeners'] ?? []) as $event => $listeners) {
                foreach ((array) $listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    /**
     * Métricas de las rutas manager.helpdesk.erp.chat.* sin tocar el
     * controlador: un middleware en el grupo 'web' que deja pasar al instante
     * todo lo que no sea una de esas rutas, y escucha de las llamadas HTTP
     * al manager mientras dura una de ellas. Todo se escribe tras enviar la
     * respuesta (terminating).
     */
    protected function registerMetrics(): void
    {
        /** @var Router $router */
        $router = $this->app->make('router');
        $router->pushMiddlewareToGroup('web', ErpAdminChatMiddleware::class);

        Event::listen(ResponseReceived::class, function (ResponseReceived $event): void {
            $this->app->make(ErpAdminMetricsRecorder::class)->managerResponse($event);
        });
        Event::listen(ConnectionFailed::class, function (ConnectionFailed $event): void {
            $this->app->make(ErpAdminMetricsRecorder::class)->managerFailure($event);
        });
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk · Gestión (ERP)',
            'order' => 286,
            'items' => [
                ['label' => 'Ajustes de Gestión', 'route' => 'manager.helpdesk.erp.admin.settings.index', 'permission' => 'helpdeskerp.settings.manage'],
                ['label' => 'Métricas de Gestión', 'route' => 'manager.helpdesk.erp.admin.metrics.index', 'permission' => 'helpdeskerp.metrics.view'],
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    protected function extConfigFiles(): array
    {
        $files = glob(module_path($this->moduleName, 'config/ext/*.php')) ?: [];
        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    protected function extRouteFiles(): array
    {
        $files = glob(module_path($this->moduleName, 'routes/managers.d/*.php')) ?: [];
        sort($files);

        return $files;
    }
}
