<?php

namespace Modules\Social\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Social\Console\Commands\FetchRssFeedsCommand;
use Modules\Social\Console\Commands\PublishScheduledPosts;
use Modules\Social\Console\Commands\ScanListeningKeywords;
use Modules\Social\Console\Commands\SyncPostStats;
use Modules\Social\Console\Commands\VerifySystemCommand;
use Modules\Social\Models\AbTest;
use Modules\Social\Models\BulkImport;
use Modules\Social\Models\Campaign;
use Modules\Social\Models\HashtagGroup;
use Modules\Social\Models\Label;
use Modules\Social\Models\Post;
use Modules\Social\Models\RssFeed;
use Modules\Social\Models\ShortUrl;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Models\Template;
use Modules\Social\Policies\AbTestPolicy;
use Modules\Social\Policies\BulkImportPolicy;
use Modules\Social\Policies\CampaignPolicy;
use Modules\Social\Policies\HashtagGroupPolicy;
use Modules\Social\Policies\LabelPolicy;
use Modules\Social\Policies\PostPolicy;
use Modules\Social\Policies\RssFeedPolicy;
use Modules\Social\Policies\ShortUrlPolicy;
use Modules\Social\Policies\SocialAccountPolicy;
use Modules\Social\Policies\TemplatePolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SocialServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Social';

    protected string $nameLower = 'social';

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
        $this->registerPolicies();
        $this->registerMenuItems();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            FetchRssFeedsCommand::class,
            PublishScheduledPosts::class,
            SyncPostStats::class,
            ScanListeningKeywords::class,
            VerifySystemCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('social:publish-scheduled')->everyMinute()->withoutOverlapping()->runInBackground();
            $schedule->command('social:sync-stats')->hourly()->withoutOverlapping()->runInBackground();
            $schedule->command('social:scan-listening')->everyFifteenMinutes()->withoutOverlapping()->runInBackground();
            $schedule->command('social:fetch-rss-feeds')->hourly()->withoutOverlapping()->runInBackground();
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
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower.'.'.str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
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

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
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
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(SocialAccount::class, SocialAccountPolicy::class);
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Label::class, LabelPolicy::class);
        Gate::policy(Template::class, TemplatePolicy::class);
        Gate::policy(HashtagGroup::class, HashtagGroupPolicy::class);
        Gate::policy(ShortUrl::class, ShortUrlPolicy::class);
        Gate::policy(BulkImport::class, BulkImportPolicy::class);
        Gate::policy(RssFeed::class, RssFeedPolicy::class);
        Gate::policy(AbTest::class, AbTestPolicy::class);
    }

    /**
     * Register menu items for Social module
     */
    protected function registerMenuItems(): void
    {
        NavService::registerMiniItem('social', [
            'icon' => 'fas fa-share-nodes',
            'tooltip' => 'Social Media',
            'sidebar_id' => 'social',
            'order' => 50,
        ]);

        NavService::registerSidebar('social', [
            'title' => 'Social Media',
            'items' => [
                ['label' => 'Publicaciones', 'route' => 'admin.social.publishing.index'],
                ['label' => 'Nueva publicación', 'route' => 'admin.social.publishing.create'],
                ['label' => 'Calendario', 'route' => 'admin.social.publishing.calendar'],
                ['label' => 'Cuentas sociales', 'route' => 'admin.social.accounts.index'],
                ['label' => 'Campañas', 'route' => 'admin.social.campaigns.index'],
                ['label' => 'Etiquetas', 'route' => 'admin.social.labels.index'],
                ['label' => 'Biblioteca de medios', 'route' => 'admin.social.media.index'],
                ['label' => 'Plantillas', 'route' => 'admin.social.templates.index'],
                ['label' => 'Grupos de hashtags', 'route' => 'admin.social.hashtags.index'],
                ['label' => 'URLs acortadas', 'route' => 'admin.social.short-urls.index'],
                ['label' => 'Importación masiva', 'route' => 'admin.social.bulk-import.index'],
                ['label' => 'Feeds RSS', 'route' => 'admin.social.rss-feeds.index'],
                ['label' => 'Dashboard analytics', 'route' => 'admin.social.analytics.index'],
                ['label' => 'Tests A/B', 'route' => 'admin.social.ab-tests.index'],
                ['label' => 'Social listening', 'route' => 'admin.social.listening.index'],
                ['label' => 'Best time to post', 'route' => 'admin.social.best-time.index'],
                ['label' => 'Unified inbox', 'route' => 'admin.social.inbox.index'],
            ],
        ]);
    }
}
