<?php

namespace Modules\Sitemap\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Sitemap\Builder\SitemapBuilder;
use Modules\Sitemap\Console\GenerateSitemapCommand;
use Modules\Sitemap\Console\PingSitemapCommand;
use Modules\Sitemap\Console\ValidateSitemapCommand;
use Nwidart\Modules\Traits\PathNamespace;

class SitemapServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Sitemap';

    protected string $nameLower = 'sitemap';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton('sitemap', fn () => new SitemapBuilder);
    }

    protected function registerCommands(): void
    {
        $this->commands([
            GenerateSitemapCommand::class,
            PingSitemapCommand::class,
            ValidateSitemapCommand::class,
        ]);
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $this->app->make(Schedule::class)
                ->command('sitemap:generate')
                ->dailyAt('02:00');
        });
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, 'config/config.php');

        $this->publishes([$configPath => config_path('sitemap.php')], 'config');
        $this->mergeConfigFrom($configPath, $this->nameLower);
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]),
            $this->nameLower
        );

        Blade::componentNamespace(
            config('modules.namespace').'\\'.$this->name.'\\View\\Components',
            $this->nameLower
        );
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
