<?php

namespace Modules\Database\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DatabaseServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Database';

    protected string $nameLower = 'database';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        // Register routes directly (Laravel 12 compatible)
        $this->registerRoutes();

        // Register menus
        $this->registerMenus();

        // Load dynamic database configuration
        $this->loadDynamicDatabaseConfig();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        $webPath = module_path($this->name, 'routes/web.php');

        // Database backups and cleanup routes
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('settings/database')
            ->name('settings.database.')
            ->group(function () use ($webPath) {
                require $webPath;
            });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
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

        config([$key => array_replace_recursive($existing, $module_config)]);
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
     * Registrar menús del módulo Database
     */
    protected function registerMenus(): void
    {

        NavService::registerSidebar('settings', [
            'title' => 'Gestión de base de datos',
            'items' => [
                ['label' => 'Configuración', 'route' => 'settings.database.index'],
                ['label' => 'Limpieza', 'route' => 'settings.database.cleanup.index'],
            ],
        ]);
    }

    /**
     * Load dynamic database configuration from settings
     */
    private function loadDynamicDatabaseConfig(): void
    {
        // Only load if database is available
        if ($this->app->runningInConsole() && ! $this->isDatabaseReady()) {
            return;
        }

        try {
            $this->loadDatabaseConfig();
        } catch (\Exception $e) {
            // Database config not available yet - silently fail
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
     * Load database configuration from database settings
     */
    private function loadDatabaseConfig(): void
    {
        try {
            $dbSettings = Setting::getDatabaseSettings();

            config([
                'database.default' => $dbSettings['db_connection'],
                'database.connections.mysql' => [
                    'driver' => 'mysql',
                    'host' => $dbSettings['db_host'],
                    'port' => (int) $dbSettings['db_port'],
                    'database' => $dbSettings['db_database'],
                    'username' => $dbSettings['db_username'],
                    'password' => $dbSettings['db_password'],
                    'unix_socket' => env('DB_SOCKET', ''),
                    'charset' => $dbSettings['db_charset'],
                    'collation' => $dbSettings['db_collation'],
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => null,
                    'options' => extension_loaded('pdo_mysql') ? array_filter([
                        \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                    ]) : [],
                ],
            ]);
        } catch (\Exception $e) {
            // Database config not available yet
        }
    }
}
