<?php

namespace Modules\Pulse\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as LaravelEventServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Modules\Theme\Services\NavService;

class EventServiceProvider extends LaravelEventServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/pulse.php',
            'pulse'
        );
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerMenus();
        $this->registerGates();
        $this->loadMigrationsFrom(module_path('Pulse', 'database/migrations'));
    }

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}

    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/pulse');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'pulse');
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path('Pulse', 'lang'), 'pulse');
            $this->loadJsonTranslationsFrom(module_path('Pulse', 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path('Pulse', config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', 'pulse.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? 'pulse' : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/pulse');
        $sourcePath = module_path('Pulse', 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', 'pulse-module-views']);

        // Publish vendor views
        $this->publishes([
            __DIR__.'/../../resources/views/vendor/pulse' => resource_path('views/vendor/pulse'),
        ], 'pulse-views');

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), 'pulse');

        Blade::componentNamespace(config('modules.namespace').'\\Pulse\\View\\Components', 'pulse');
    }

    protected function registerGates(): void
    {
        Gate::define('view-pulse', function ($user) {
            return $user->hasAnyRole(['super-admin', 'manager']);
        });

        Gate::define('manage-pulse-backups', function ($user) {
            return $user->hasRole('super-admin');
        });
    }

    protected function registerMenus(): void
    {

        // Sidebar con los items del módulo
        NavService::registerSidebar('settings', [
            'title' => 'Monitoreo del sistema',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'pulse.dashboard'],
            ],
        ]);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/pulse')) {
                $paths[] = $path.'/modules/pulse';
            }
        }

        return $paths;
    }

    public function provides(): array
    {
        return [];
    }
}
