<?php

namespace Modules\Webhook\Providers;

use App\Helpers\ModuleStatusHelper;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;
use Modules\Webhook\Models\WebhookIntegration;
use Modules\Webhook\Policies\SettingsPolicy;
use Modules\Webhook\Policies\WebhookPolicy;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class WebhookServiceProvider extends ServiceProvider
{
    protected string $name = 'Webhook';

    protected string $nameLower = 'webhook';

    public function boot(): void
    {
        // ✅ CRITICAL: Only boot module features if Webhook module is enabled
        if (! ModuleStatusHelper::isModuleEnabled('Webhook')) {
            return;
        }

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerMenus();
        $this->registerGates();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        // Register routes directly (Laravel 12 compatible)
        $this->registerRoutes();
    }

    public function register(): void
    {
        // Register policies
        $this->registerPolicies();
    }

    /**
     * Register policies
     */
    protected function registerPolicies(): void
    {
        if (class_exists(WebhookIntegration::class)) {
            Gate::policy(
                WebhookIntegration::class,
                WebhookPolicy::class
            );
        }
    }

    /**
     * Register gates for backups authorization
     */
    protected function registerGates(): void
    {
        $settingsPolicy = new SettingsPolicy;

        // Configure backups gates
        Gate::define('configure-webhooks', fn ($user) => $settingsPolicy->configure($user));
        Gate::define('view-webhook-backups', fn ($user) => $settingsPolicy->viewSettings($user));
        Gate::define('manage-webhook-integrations', fn ($user) => $settingsPolicy->manageIntegrations($user));
        Gate::define('manage-webhook-subscriptions', fn ($user) => $settingsPolicy->manageSubscriptions($user));
        Gate::define('manage-webhook-events', fn ($user) => $settingsPolicy->manageEvents($user));
        Gate::define('test-webhooks', fn ($user) => $settingsPolicy->testWebhooks($user));
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        $moduleName = $this->name;
        $webPath = module_path($moduleName, 'routes/web.php');
        $settingsPath = module_path($moduleName, 'routes/settings.php');
        $apiPath = module_path($moduleName, 'routes/api.php');

        // Webhook operational routes (day-to-day webhook monitoring)
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('webhooks')
            ->name('webhooks.')
            ->group(function () use ($webPath) {
                require $webPath;
            });

        // Webhook configuration routes (system backups)
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->prefix('settings/webhooks')
            ->name('webhooks.backups.')
            ->group(function () use ($settingsPath) {
                require $settingsPath;
            });

        // Public API routes
        Route::middleware(['api'])
            ->prefix('api/webhooks')
            ->name('api.webhooks.')
            ->group(function () use ($apiPath) {
                require $apiPath;
            });
    }

    /**
     * Register module menus
     */
    protected function registerMenus(): void
    {
        // Mini-nav item for Webhooks
        NavService::registerMiniItem('webhooks', [
            'icon' => 'fa-duotone fa-webhook',
            'tooltip' => 'Webhooks',
            'sidebar_id' => 'webhooks',
            'order' => 40,
        ]);

        // Sidebar with module items
        NavService::registerSidebar('webhooks', [
            'title' => 'Webhooks',
            'items' => [
                ['label' => 'Listado de webhooks', 'route' => 'webhooks.index'],
                ['label' => 'Entregas', 'route' => 'webhooks.deliveries'],
                ['label' => 'Eventos', 'route' => 'webhooks.events'],
                ['label' => 'Integraciones', 'route' => 'webhooks.backups.integrations.index'],
                ['label' => 'Suscripciones', 'route' => 'webhooks.backups.subscriptions.index'],
            ],
        ]);
    }

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

    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        if (is_array($module_config)) {
            config([$key => array_replace_recursive($existing, $module_config)]);
        }
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

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
}
