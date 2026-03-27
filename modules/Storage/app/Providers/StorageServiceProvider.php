<?php

namespace Modules\Storage\Providers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
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
        Route::middleware(['web', 'auth', 'settings'])
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
     * Decrypt a stored credential, falling back to the raw value for legacy unencrypted entries.
     */
    private function decryptCredential(string $value, string $diskName, string $field): string
    {
        if ($value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            Log::warning('Storage disk has unencrypted credential, please re-save the configuration.', [
                'disk' => $diskName,
                'field' => $field,
            ]);

            return $value;
        }
    }

    /**
     * Load custom storage disks from database and register them with Laravel's filesystem config
     */
    private function loadStorageConfig(): void
    {
        try {
            $customDisksJson = Setting::get('system.custom_storage_disks', '[]');
            $customDisks = json_decode($customDisksJson, true) ?: [];

            foreach ($customDisks as $disk) {
                $diskName = $disk['name'];
                $driver = $disk['driver'];

                $diskConfig = ['driver' => $driver];

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
                        $diskConfig['password'] = $this->decryptCredential($disk['password'] ?? '', $diskName, 'password');
                        $diskConfig['port'] = (int) ($disk['port'] ?? 21);
                        $diskConfig['root'] = $disk['root'] ?? '/';
                        $diskConfig['passive'] = true;
                        $diskConfig['ssl'] = false;
                        $diskConfig['timeout'] = 30;
                        break;

                    case 'sftp':
                        $diskConfig['host'] = $disk['host'] ?? '';
                        $diskConfig['username'] = $disk['username'] ?? '';
                        $diskConfig['password'] = $this->decryptCredential($disk['password'] ?? '', $diskName, 'password');
                        $diskConfig['port'] = (int) ($disk['port'] ?? 22);
                        $diskConfig['root'] = $disk['root'] ?? '/';
                        $diskConfig['timeout'] = 30;
                        break;

                    case 's3':
                        $diskConfig['key'] = $disk['key'] ?? '';
                        $diskConfig['secret'] = $this->decryptCredential($disk['secret'] ?? '', $diskName, 'secret');
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

                config(["filesystems.disks.{$diskName}" => $diskConfig]);
            }
        } catch (\Exception $e) {
            Log::debug('Storage configuration could not be loaded', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
