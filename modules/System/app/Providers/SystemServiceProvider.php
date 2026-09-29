<?php

namespace Modules\System\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\System\Console\Commands\AuditPermissionsCommand;
use Modules\System\Console\Commands\CleanupTempExportsCommand;
use Modules\System\Services\GlobalSearchRegistrar;
use Modules\System\Services\GlobalSearchService;
use Modules\System\Services\SystemInfoService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class SystemServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register SystemInfoService as singleton
        $this->app->singleton(SystemInfoService::class, function ($app) {
            return new SystemInfoService;
        });

        // GlobalSearch: registrar + servicio compartidos entre módulos.
        $this->app->singleton(GlobalSearchRegistrar::class);
        $this->app->singleton(GlobalSearchService::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        if (Module::find('System')?->isDisabled()) {
            return;
        }

        $this->applyRuntimeSettings();

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'system');

        // Publish config
        $this->publishes([
            __DIR__.'/../../config/system.php' => config_path('system.php'),
        ], 'system-config');

        // Register menus
        $this->registerMenus();

        if ($this->app->runningInConsole()) {
            $this->commands([
                AuditPermissionsCommand::class,
                CleanupTempExportsCommand::class,
            ]);
        }
    }

    /**
     * Registrar menús del módulo System
     */
    /**
     * Claves de settings que el panel de Sistema aplica en runtime en lugar de
     * reescribir el .env (29-sep-2026). Redis queda fuera a propósito: los
     * settings se leen de la caché Redis.
     *
     * @var array<string, string>
     */
    private const RUNTIME_SETTINGS = [
        'queue_connection' => 'queue.default',
        'broadcast_driver' => 'broadcasting.default',
        'pusher_app_id' => 'broadcasting.connections.pusher.app_id',
        'pusher_key' => 'broadcasting.connections.pusher.key',
        'pusher_secret' => 'broadcasting.connections.pusher.secret',
        'pusher_cluster' => 'broadcasting.connections.pusher.options.cluster',
        'reverb_host' => 'broadcasting.connections.reverb.options.host',
        'reverb_port' => 'broadcasting.connections.reverb.options.port',
        'reverb_scheme' => 'broadcasting.connections.reverb.options.scheme',
    ];

    private const RUNTIME_CACHE_KEY = 'settings_system_runtime';

    public static function clearRuntimeSettingsCache(): void
    {
        cache()->forget(self::RUNTIME_CACHE_KEY);
    }

    /**
     * Aplica sobre la config los ajustes guardados desde el panel de Sistema.
     * Una sola lectura cacheada; si no hay filas (lo normal) no cambia nada.
     */
    private function applyRuntimeSettings(): void
    {
        try {
            $stored = cache()->remember(self::RUNTIME_CACHE_KEY, now()->addMinutes(10), function () {
                return Setting::whereIn('key', array_keys(self::RUNTIME_SETTINGS))
                    ->pluck('value', 'key')
                    ->all();
            });

            foreach ((array) $stored as $key => $value) {
                if (! isset(self::RUNTIME_SETTINGS[$key]) || $value === null || $value === '') {
                    continue;
                }

                if ($key === 'queue_connection' && ! array_key_exists($value, (array) config('queue.connections', []))) {
                    continue;
                }
                if ($key === 'broadcast_driver' && ! array_key_exists($value, (array) config('broadcasting.connections', []))) {
                    continue;
                }
                if ($key === 'pusher_secret') {
                    $value = Setting::getDecrypted('pusher_secret');
                }
                if ($key === 'reverb_port') {
                    $value = (int) $value;
                }
                if ($key === 'reverb_scheme') {
                    config(['broadcasting.connections.reverb.options.useTLS' => $value === 'https']);
                }

                config([self::RUNTIME_SETTINGS[$key] => $value]);
            }
        } catch (\Throwable $e) {
            // Sin BD/caché (instalación, tests): se queda la config del .env.
        }
    }

    protected function registerMenus(): void
    {
        // Mini-nav item para Settings (configuraciones)
        NavService::registerMiniItem('settings', [
            'icon' => 'sliders',
            'tooltip' => 'Configuraciones',
            'sidebar_id' => 'settings',
            'order' => 100,
        ]);

        // Agregar configuraciones de sistema al sidebar genérico 'settings'
        NavService::registerSidebar('settings', [
            'title' => 'Configuraciones',
            'items' => [
                ['label' => 'Configuración', 'route' => 'settings.system.index'],
                ['label' => 'Información del sistema', 'route' => 'settings.system.info.index'],
                ['label' => 'Supervisor', 'route' => 'settings.system.supervisor.index'],
                ['label' => 'Logs del servidor', 'route' => 'settings.system.access.index'],
                ['label' => 'Carga de archivos', 'route' => 'settings.system.uploading.index'],
                ['label' => 'Cache y mantenimiento', 'route' => 'settings.system.maintenance.index'],
            ],
        ]);
    }
}
