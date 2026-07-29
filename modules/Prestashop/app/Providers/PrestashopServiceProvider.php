<?php

namespace Modules\Prestashop\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PrestashopServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Prestashop';

    protected string $nameLower = 'prestashop';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerMenus();
        $this->loadDynamicPrestashopConfig();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Register services as singletons
        // TODO: Uncomment when service classes are properly implemented
        // $this->app->singleton(
        //     \modules\Prestashop\Services\CategorySyncService::class,
        //     fn ($app) => new \modules\Prestashop\Services\CategorySyncService
        // );

        // $this->app->singleton(
        //     \modules\Prestashop\Services\SupplierSyncService::class,
        //     fn ($app) => new \modules\Prestashop\Services\SupplierSyncService
        // );
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // Register artisan commands here
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        // Only merge if the config file returned an array
        if (is_array($module_config)) {
            config([$key => array_replace_recursive($existing, $module_config)]);
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    /**
     * Registrar menús del módulo Prestashop
     */
    protected function registerMenus(): void
    {


        NavService::registerSidebar('settings', [
            'title' => 'PrestaShop',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'settings.prestashop.index'],
                ['label' => 'Configuración', 'route' => 'settings.prestashop.edit'],
            ],
        ]);
    }

    /**
     * Load dynamic PrestaShop configuration from settings
     */
    private function loadDynamicPrestashopConfig(): void
    {
        // Only load if database is available
        if ($this->app->runningInConsole() && ! $this->isDatabaseReady()) {
            return;
        }

        try {
            $this->loadPrestashopConfig();
        } catch (\Exception $e) {
            // PrestaShop config not available yet - silently fail
        }
    }

    /**
     * Check if database is ready and configured
     */
    private function isDatabaseReady(): bool
    {
        try {
            \DB::connection()->getPDO();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Load PrestaShop configuration from database settings
     */
    private function loadPrestashopConfig(): void
    {
        try {
            $psSettings = Setting::getPrestashopSettings();

            config([
                'prestashop' => [
                    'enabled' => $psSettings['prestashop_enabled'] === 'yes',
                    'db_host' => $psSettings['prestashop_db_host'],
                    'db_port' => (int) $psSettings['prestashop_db_port'],
                    'db_database' => $psSettings['prestashop_db_database'],
                    'db_username' => $psSettings['prestashop_db_username'],
                    'db_password' => $psSettings['prestashop_db_password'],
                    'url' => $psSettings['prestashop_url'],
                    'api_key' => $psSettings['prestashop_api_key'],
                    'timeout' => (int) $psSettings['prestashop_timeout'],
                    'connect_timeout' => (int) $psSettings['prestashop_connect_timeout'],
                    'sync_enabled' => $psSettings['prestashop_sync_enabled'] === 'yes',
                    'sync_products' => $psSettings['prestashop_sync_products'] === 'yes',
                    'sync_orders' => $psSettings['prestashop_sync_orders'] === 'yes',
                    'sync_customers' => $psSettings['prestashop_sync_customers'] === 'yes',
                    'documents_portal_url' => $psSettings['prestashop_documents_portal_url'],
                    'documents_paid_status_ids' => $psSettings['prestashop_documents_paid_status_ids'],
                ],
            ]);
        } catch (\Exception $e) {
            // PrestaShop config not available yet
        }
    }
}
