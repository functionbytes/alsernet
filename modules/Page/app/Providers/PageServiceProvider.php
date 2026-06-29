<?php

namespace Modules\Page\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Page\Console\Commands\CleanOldPageViewsCommand;
use Modules\Page\Console\Commands\CleanPageAutoSavesCommand;
use Modules\Page\Console\Commands\CleanPageLocksCommand;
use Modules\Page\Console\Commands\CleanupPreviewTokensCommand;
use Modules\Page\Console\Commands\PageAuditCommand;
use Modules\Page\Console\Commands\PageCacheClearCommand;
use Modules\Page\Console\Commands\PageCacheStatsCommand;
use Modules\Page\Console\Commands\PageCacheWarmCommand;
use Modules\Page\Console\Commands\SeedErrorPages;
use Modules\Page\Console\Commands\WarmPageCacheCommand;
use Modules\Page\Console\InstallCmsCommand;
use Modules\Page\Console\InstallPageCommand;
use Modules\Page\Console\PublishScheduledPagesCommand;
use Modules\Page\Console\ReindexPagesCommand;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageApproval;
use Modules\Page\Models\PageAutoSave;
use Modules\Page\Models\PageCategory;
use Modules\Page\Models\PageLock;
use Modules\Page\Models\PageTag;
use Modules\Page\Models\PageVersion;
use Modules\Page\Models\PageWebhook;
use Modules\Page\Policies\PageApprovalPolicy;
use Modules\Page\Policies\PageAutoSavePolicy;
use Modules\Page\Policies\PageCategoryPolicy;
use Modules\Page\Policies\PageLockPolicy;
use Modules\Page\Policies\PagePolicy;
use Modules\Page\Policies\PageTagPolicy;
use Modules\Page\Policies\PageVersionPolicy;
use Modules\Page\Policies\PageWebhookPolicy;
use Modules\Page\Services\PageAutoSaveService;
use Modules\Page\Services\PageCacheService;
use Modules\Page\Services\PageLockService;
use Modules\Page\Services\PageService;
use Modules\Page\Services\PageWebhookService;
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
        $this->app->singleton(PageService::class);
        $this->app->singleton(PageCacheService::class);
        $this->app->singleton(PageAutoSaveService::class);
        $this->app->singleton(PageLockService::class);
        $this->app->singleton(PageWebhookService::class);

    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            InstallPageCommand::class,
            InstallCmsCommand::class,
            PublishScheduledPagesCommand::class,
            CleanupPreviewTokensCommand::class,
            ReindexPagesCommand::class,
            PageCacheWarmCommand::class,
            PageCacheClearCommand::class,
            PageCacheStatsCommand::class,
            CleanPageAutoSavesCommand::class,
            CleanPageLocksCommand::class,
            PageAuditCommand::class,
            CleanOldPageViewsCommand::class,
            WarmPageCacheCommand::class,
            SeedErrorPages::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

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

            // Clean old page_views records weekly
            $schedule->command('page:clean-views')
                ->weekly()
                ->at('04:00')
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
        Gate::policy(PageWebhook::class, PageWebhookPolicy::class);
        Gate::policy(PageVersion::class, PageVersionPolicy::class);
        Gate::policy(PageCategory::class, PageCategoryPolicy::class);
        Gate::policy(PageTag::class, PageTagPolicy::class);
        Gate::policy(PageApproval::class, PageApprovalPolicy::class);
        Gate::policy(PageAutoSave::class, PageAutoSavePolicy::class);
        Gate::policy(PageLock::class, PageLockPolicy::class);
    }

    /**
     * Registrar menus del modulo Page
     */
    protected function registerMenus(): void
    {
        NavService::registerMiniItem('pages', [
            'icon' => 'fa-duotone fa-thin fa-file-dashed-line',
            'tooltip' => 'Paginas',
            'sidebar_id' => 'pages',
            'order' => 50,
        ]);

        NavService::registerSidebar('pages', [
            'title' => 'Paginas',
            'items' => [
                ['label' => 'Listado de paginas', 'route' => 'pages.index', 'permission' => 'page.view'],
                ['label' => 'Aprobaciones', 'route' => 'pages.approvals.index', 'permission' => 'page.approve'],
                ['label' => 'Categorias', 'route' => 'pages.categories.index', 'permission' => 'page.manage-categories'],
                ['label' => 'Webhooks', 'route' => 'pages.webhooks.index', 'permission' => 'page.manage-webhooks'],
                ['label' => 'Importar', 'route' => 'pages.import', 'permission' => 'page.import'],
                ['label' => 'Exportar', 'route' => 'pages.export', 'permission' => 'page.export'],
            ],
        ]);

        NavService::addItemsToSection('settings', 'Plantillas', [
            ['label' => 'Configuracion de paginas', 'route' => 'settings.pages'],
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
