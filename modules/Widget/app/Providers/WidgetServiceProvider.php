<?php

namespace Modules\Widget\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Widget\Factories\WidgetFactory;
use Modules\Widget\Models\Widget;
use Modules\Widget\Repositories\Caches\WidgetCacheDecorator;
use Modules\Widget\Repositories\Eloquent\WidgetRepository;
use Modules\Widget\Repositories\Interfaces\WidgetInterface;
use Modules\Widget\WidgetGroupCollection;
use Nwidart\Modules\Facades\Module;

class WidgetServiceProvider extends ServiceProvider
{
    /**
     * Module namespace
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Widget';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        if (Module::find('Widget')?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->registerMigrations();
        $this->loadTranslations();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerRepositories();
        $this->registerFactories();
        $this->registerFacades();
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path('Widget', 'config/permissions.php') => config_path('widget.php'),
        ], 'widget-config');

        $this->mergeConfigFrom(
            module_path('Widget', 'config/permissions.php'),
            'widget'
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNamespace);
        $sourcePath = module_path('Widget', 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', 'widget-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), 'widget');
    }

    /**
     * Register migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path('Widget', 'database/migrations'));
    }

    /**
     * Register translations.
     */
    public function loadTranslations(): void
    {
        $langPath = module_path('Widget', 'resources/lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'widget');
        }
    }

    /**
     * Register repositories.
     */
    protected function registerRepositories(): void
    {
        $this->app->singleton(WidgetInterface::class, function ($app) {
            $repository = new WidgetRepository($app->make(Widget::class));

            if (config('widget.cache.enabled', true)) {
                return new WidgetCacheDecorator($repository);
            }

            return $repository;
        });
    }

    /**
     * Register factories.
     */
    protected function registerFactories(): void
    {
        $this->app->singleton('widget.factory', function ($app) {
            return new WidgetFactory;
        });
    }

    /**
     * Register facades.
     */
    protected function registerFacades(): void
    {
        $this->app->singleton('widget.groups', function ($app) {
            return new WidgetGroupCollection;
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            WidgetInterface::class,
            'widget.factory',
            'widget.groups',
        ];
    }

    /**
     * Get publishable view paths.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNamespace)) {
                $paths[] = $path.'/modules/'.$this->moduleNamespace;
            }
        }

        return $paths;
    }
}
