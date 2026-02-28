<?php

namespace Modules\Page\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Page\Models\Page;
use Modules\Page\Policies\PagePolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PageServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Page';

    protected string $nameLower = 'page';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerPolicies();
        $this->registerMenus();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register services
        $this->app->singleton(\Modules\Page\Services\PageService::class);
        $this->app->singleton(\Modules\Page\Services\PageCacheService::class);
        $this->app->singleton(\Modules\Page\Services\PageAutoSaveService::class);
        $this->app->singleton(\Modules\Page\Services\PageLockService::class);

        // Register factories
        $this->registerFactories();
    }

    /**
     * Register factories.
     *
     * Note: Laravel 8+ uses class-based factories that are auto-discovered.
     * No manual registration needed.
     */
    protected function registerFactories(): void
    {
        // Laravel 8+ factories are auto-discovered from database/factories
        // No manual registration required
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Page\Console\InstallPageCommand::class,
            \Modules\Page\Console\InstallCmsCommand::class,
            \Modules\Page\Console\PublishScheduledPagesCommand::class,
            \Modules\Page\Console\Commands\CleanupPreviewTokensCommand::class,
            \Modules\Page\Console\ReindexPagesCommand::class,
            \Modules\Page\Console\Commands\PageCacheWarmCommand::class,
            \Modules\Page\Console\Commands\PageCacheClearCommand::class,
            \Modules\Page\Console\Commands\PageCacheStatsCommand::class,
            \Modules\Page\Console\Commands\CleanPageAutoSavesCommand::class,
            \Modules\Page\Console\Commands\CleanPageLocksCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

            // Run scheduled page publishing every hour
            $schedule->command('page:publish-scheduled --type=all')
                ->hourly()
                ->withoutOverlapping()
                ->onOneServer();

            // Cleanup expired preview tokens daily
            $schedule->command('page:cleanup-preview-tokens --days=7 --force')
                ->daily()
                ->at('02:00')
                ->withoutOverlapping()
                ->onOneServer();

            // Warm page cache every 6 hours
            $schedule->command('page:cache-warm --all')
                ->everyFourHours()
                ->between('00:00', '23:59')
                ->withoutOverlapping()
                ->onOneServer();

            // Clean expired auto-saves daily
            $schedule->command('page:clean-auto-saves')
                ->daily()
                ->at('03:00')
                ->withoutOverlapping()
                ->onOneServer();

            // Clean expired page locks every 10 minutes
            $schedule->command('page:clean-locks')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->onOneServer();
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
     * Register the module policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Page::class, PagePolicy::class);
    }

    /**
     * Registrar menus del modulo Page
     */
    protected function registerMenus(): void
    {
        NavService::registerMiniItem('pages', [
            'icon' => 'fa-duotone fa-file-lines',
            'tooltip' => 'Paginas',
            'sidebar_id' => 'pages',
            'order' => 50,
        ]);

        NavService::registerSidebar('pages', [
            'title' => 'Paginas',
            'items' => [
                ['label' => 'Listado de paginas', 'route' => 'pages.index'],
                ['label' => 'Crear pagina', 'route' => 'pages.create'],
                ['label' => 'Configuracion', 'route' => 'settings.pages'],
            ],
        ]);
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
}
