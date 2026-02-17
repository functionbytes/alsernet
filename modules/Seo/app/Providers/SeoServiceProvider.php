<?php

namespace Modules\Seo\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Seo\Console\Commands\GenerateSchemasCommand;
use Modules\Seo\Http\Middleware\RedirectMiddleware;
use Modules\Seo\Services\SchemaOrgService;
use Modules\Seo\Services\SeoService;
use Modules\Theme\Services\NavService;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * The module namespace.
     */
    protected string $namespace = 'Modules\Seo';

    /**
     * Boot the application services.
     */
    public function boot(): void
    {
        $this->loadMigrations();
        $this->loadViews();
        $this->loadConfig();
        $this->loadRoutes();
        $this->loadTranslations();
        $this->registerBladeComponents();
        $this->registerMiddleware();
        $this->registerCommands();
        $this->publishResources();
        $this->loadSitemapConfig();
        $this->registerSitemapMiddleware();
        $this->registerSitemapCommands();
        $this->registerSitemapScheduler();
        $this->registerMenus();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('seo', function ($app) {
            return new SeoService;
        });

        $this->app->alias('seo', SeoService::class);

        $this->app->singleton(SchemaOrgService::class, function ($app) {
            return new SchemaOrgService;
        });

        $this->app->singleton('sitemap', function ($app) {
            return new \Modules\Seo\Builder\SitemapBuilder;
        });

        $this->app->alias('sitemap', \Modules\Seo\Builder\SitemapBuilder::class);
    }

    /**
     * Load migrations.
     */
    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    /**
     * Load views.
     */
    protected function loadViews(): void
    {
        $viewPath = __DIR__.'/../../resources/views';

        $this->loadViewsFrom($viewPath, 'Seo');
    }

    /**
     * Load configuration.
     */
    protected function loadConfig(): void
    {
        $configPath = __DIR__.'/../../config/seohelper.php';

        $this->mergeConfigFrom($configPath, 'Seo');
    }

    /**
     * Load routes.
     */
    protected function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
    }

    /**
     * Load translations.
     */
    protected function loadTranslations(): void
    {
        $langPath = __DIR__.'/../../resources/lang';

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'seo');
        }
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Register middleware alias
        $router->aliasMiddleware('seo.redirect', RedirectMiddleware::class);

        // Add to web middleware group
        $router->pushMiddlewareToGroup('web', RedirectMiddleware::class);
    }

    /**
     * Register Blade components.
     */
    protected function registerBladeComponents(): void
    {
        Blade::component('Seo::components.seo-tags', 'seo-tags');

        // Register directive for rendering SEO tags
        Blade::directive('seoTags', function ($expression) {
            return "<?php echo app('seo')->render(); ?>";
        });

        // Register directive for rendering schema tags
        Blade::directive('schemaScript', function ($expression) {
            return "<?php echo app('seo')->renderSchema(); ?>";
        });

        // Register directive for including SEO form
        Blade::directive('seoForm', function ($expression) {
            return "<?php echo view('Seo::partials.seo-form', ['model' => $expression])->render(); ?>";
        });
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSchemasCommand::class,
            ]);
        }
    }

    /**
     * Registrar menus del modulo Seo
     */
    protected function registerMenus(): void
    {
        NavService::registerSidebar('settings', [
            'title' => 'SEO',
            'items' => [
                ['label' => 'Meta SEO', 'route' => 'setting.seo.metas.index'],
                ['label' => 'Redirecciones', 'route' => 'setting.seo.redirects.index'],
                ['label' => 'Robots.txt', 'route' => 'setting.seo.robots.edit'],
                ['label' => 'Sitemap XML', 'route' => 'setting.seo.sitemap.index'],
            ],
        ]);
    }

    protected function loadSitemapConfig(): void
    {
        $configPath = __DIR__.'/../../config/sitemap.php';

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'sitemap');
        }
    }

    protected function registerSitemapMiddleware(): void
    {
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('sitemap.cache', \Modules\Seo\Http\Middleware\CacheSitemapResponse::class);
    }

    protected function registerSitemapCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Seo\Console\Commands\GenerateSitemapCommand::class,
                \Modules\Seo\Console\Commands\PingSitemapCommand::class,
            ]);
        }
    }

    protected function registerSitemapScheduler(): void
    {
        // Registrar scheduler en bootstrap/app.php o aqui si es posible
    }

    /**
     * Publish resources.
     */
    protected function publishResources(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../../config/seohelper.php' => config_path('seohelper.php'),
        ], 'Seo-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'Seo-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/Seo'),
        ], 'Seo-views');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['seo', SeoService::class];
    }
}
