<?php

namespace Modules\Storage\Providers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class StorageServiceProvider extends ServiceProvider
{
    protected string $name = 'Storage';

    protected string $nameLower = 'storage';

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        if (Module::find('Storage')?->isDisabled()) {
            return;
        }

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
            ->prefix('panel/settings')
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
        NavService::addItemsToSection('settings', 'Configuraciones', [
            ['label' => 'Almacenamiento', 'route' => 'settings.storage.index'],
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
            $customDisks = Cache::remember('storage.custom_disks', 3600, function (): array {
                $raw = Setting::get('system.custom_storage_disks', '[]');
                $decoded = json_decode($raw, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Custom storage disks JSON is malformed', ['error' => json_last_error_msg()]);

                    return [];
                }

                return $decoded ?: [];
            });

            foreach ($customDisks as $disk) {
                $diskName = $disk['name'];
                $driver = $disk['driver'];

                $extra = match ($driver) {
                    'local' => array_filter([
                        'root' => $disk['root'] ?? storage_path('app'),
                        'url' => $disk['url'] ?? null,
                        'throw' => false,
                    ], fn ($v) => $v !== null),

                    'ftp' => [
                        'host' => $disk['host'] ?? '',
                        'username' => $disk['username'] ?? '',
                        'password' => $this->decryptCredential($disk['password'] ?? '', $diskName, 'password'),
                        'port' => (int) ($disk['port'] ?? 21),
                        'root' => $disk['root'] ?? '/',
                        'passive' => true,
                        'ssl' => false,
                        'timeout' => 30,
                    ],

                    'sftp' => [
                        'host' => $disk['host'] ?? '',
                        'username' => $disk['username'] ?? '',
                        'password' => $this->decryptCredential($disk['password'] ?? '', $diskName, 'password'),
                        'port' => (int) ($disk['port'] ?? 22),
                        'root' => $disk['root'] ?? '/',
                        'timeout' => 30,
                    ],

                    's3' => array_filter([
                        'key' => $this->decryptCredential($disk['key'] ?? '', $diskName, 'key'),
                        'secret' => $this->decryptCredential($disk['secret'] ?? '', $diskName, 'secret'),
                        'region' => $disk['region'] ?? '',
                        'bucket' => $disk['bucket'] ?? '',
                        'url' => $disk['url'] ?? null,
                        'endpoint' => $disk['endpoint'] ?? null,
                        'use_path_style_endpoint' => false,
                        'throw' => false,
                    ], fn ($v) => $v !== null),

                    default => [],
                };

                $existingConfig = config("filesystems.disks.{$diskName}");
                if ($existingConfig !== null && ! in_array($diskName, ['local', 'public'])) {
                    Log::warning('Custom storage disk overrides existing config disk', ['disk' => $diskName]);
                }

                config(["filesystems.disks.{$diskName}" => array_merge(['driver' => $driver], $extra)]);
            }
        } catch (\Exception $e) {
            Log::error('Storage configuration could not be loaded', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
