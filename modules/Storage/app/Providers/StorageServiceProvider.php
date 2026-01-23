<?php

namespace Modules\Storage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Theme\Services\NavService;

class StorageServiceProvider extends ServiceProvider
{
    protected string $name = 'Storage';

    protected string $nameLower = 'storage';

    /**
     * Register services
     */
    public function register(): void
    {
        // Register storage services if needed
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'storage');

        // Publish config
        $this->publishes([
            __DIR__.'/../../config/storage.php' => config_path('storage.php'),
        ], 'storage-config');

        // Load custom storage disks from database
        $this->loadStorageConfig();

        // Register routes
        $this->registerRoutes();

        // Register menus
        $this->registerMenus();
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        $webPath = module_path($this->name, 'routes/web.php');

        // Storage manager routes
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('settings')
            ->name('settings.')
            ->group(function () use ($webPath) {
                require $webPath;
            });
    }

    /**
     * Register menus for Storage module
     */
    protected function registerMenus(): void
    {

        // Add storage configuration to settings sidebar
        NavService::registerSidebar('settings', [
            'title' => 'Configuraciones',
            'items' => [
                ['label' => 'Almacenamiento', 'route' => 'settings.storage'],
            ],
        ]);
    }

    /**
     * Load custom storage disks from database and register them with Laravel's filesystem config
     */
    private function loadStorageConfig(): void
    {
        try {
            $customDisksJson = Setting::get('system.custom_storage_disks', '[]');
            $customDisks = json_decode($customDisksJson, true) ?: [];

            // Register each custom disk with Laravel's filesystem config
            foreach ($customDisks as $disk) {
                $diskName = $disk['name'];
                $driver = $disk['driver'];

                // Build disk configuration based on driver type
                $diskConfig = [
                    'driver' => $driver,
                ];

                // Add driver-specific configuration
                switch ($driver) {
                    case 'local':
                        $diskConfig['root'] = $disk['root'] ?? storage_path('app');
                        if (isset($disk['url'])) {
                            $diskConfig['url'] = $disk['url'];
                        }
                        $diskConfig['throw'] = false;
                        break;

                    case 'ftp':
                        $diskConfig['host'] = $disk['host'] ?? '';
                        $diskConfig['username'] = $disk['username'] ?? '';
                        $diskConfig['password'] = $disk['password'] ?? '';
                        $diskConfig['port'] = (int) ($disk['port'] ?? 21);
                        $diskConfig['root'] = $disk['root'] ?? '/';
                        $diskConfig['passive'] = true;
                        $diskConfig['ssl'] = false;
                        $diskConfig['timeout'] = 30;
                        break;

                    case 'sftp':
                        $diskConfig['host'] = $disk['host'] ?? '';
                        $diskConfig['username'] = $disk['username'] ?? '';
                        $diskConfig['password'] = $disk['password'] ?? '';
                        $diskConfig['port'] = (int) ($disk['port'] ?? 22);
                        $diskConfig['root'] = $disk['root'] ?? '/';
                        $diskConfig['timeout'] = 30;
                        break;

                    case 's3':
                        $diskConfig['key'] = $disk['key'] ?? '';
                        $diskConfig['secret'] = $disk['secret'] ?? '';
                        $diskConfig['region'] = $disk['region'] ?? '';
                        $diskConfig['bucket'] = $disk['bucket'] ?? '';
                        if (isset($disk['url'])) {
                            $diskConfig['url'] = $disk['url'];
                        }
                        $diskConfig['endpoint'] = $disk['endpoint'] ?? null;
                        $diskConfig['use_path_style_endpoint'] = false;
                        $diskConfig['throw'] = false;
                        break;
                }

                // Register the disk configuration
                config(["filesystems.disks.{$diskName}" => $diskConfig]);
            }
        } catch (\Exception $e) {
            // Storage config not available yet or database not initialized
            \Log::debug('Storage configuration could not be loaded', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
