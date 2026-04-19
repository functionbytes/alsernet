<?php

namespace Modules\Seo\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Modules\Seo\Builder\SitemapBuilder;
use Modules\Seo\Console\Commands\CheckBrokenLinksCommand;
use Modules\Seo\Console\Commands\GenerateSchemasCommand;
use Modules\Seo\Console\Commands\GenerateSitemapCommand;
use Modules\Seo\Console\Commands\IndexNowSubmitCommand;
use Modules\Seo\Console\Commands\PingSitemapCommand;
use Modules\Seo\Console\Commands\PurgeWebVitalsCommand;
use Modules\Seo\Console\Commands\SeoCleanupCommand;
use Modules\Seo\Console\Commands\SeoHealthCommand;
use Modules\Seo\Console\Commands\SeoPageSpeedCommand;
use Modules\Seo\Console\Commands\SeoUpdateRankingsCommand;
use Modules\Seo\Console\Commands\SeoWeeklyReportCommand;
use Modules\Seo\Http\Middleware\AutoPaginationMiddleware;
use Modules\Seo\Http\Middleware\CacheSitemapResponse;
use Modules\Seo\Http\Middleware\PerformanceHintsMiddleware;
use Modules\Seo\Http\Middleware\RedirectMiddleware;
use Modules\Seo\Http\Middleware\Track404Middleware;
use Modules\Seo\Http\Middleware\XRobotsTagMiddleware;
use Modules\Seo\Jobs\BulkSeoAuditJob;
use Modules\Seo\Jobs\CleanupOld404Logs;
use Modules\Seo\Jobs\CleanupOldAuditLogs;
use Modules\Seo\Jobs\DetectContentDecayJob;
use Modules\Seo\Jobs\SendSeoWeeklyReport;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Observers\SeoMetaObserver;
use Modules\Seo\Services\SchemaOrgService;
use Modules\Seo\Services\SeoService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

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
        if (Module::find('Seo')?->isDisabled()) {
            return;
        }

        SeoMeta::observe(SeoMetaObserver::class);

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
        $this->registerTrack404Middleware();
        $this->registerXRobotsTagMiddleware();
        $this->registerAutoPaginationMiddleware();
        $this->registerSitemapCommands();
        $this->registerSitemapScheduler();
        $this->registerCleanupScheduler();
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

        $this->app->alias(SchemaOrgService::class, 'schema-org');

        $this->app->singleton('sitemap', function ($app) {
            return new SitemapBuilder;
        });

        $this->app->alias('sitemap', SitemapBuilder::class);
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

        // Register middleware alias for explicit route usage
        $router->aliasMiddleware('seo.redirect', RedirectMiddleware::class);

        // Add to web middleware group as a baseline
        $router->pushMiddlewareToGroup('web', RedirectMiddleware::class);

        // Also prepend to the HTTP kernel so redirects run BEFORE route matching,
        // which lets the middleware catch URLs that would otherwise 404. Only do
        // this when the HTTP kernel is bound (regular web traffic and feature
        // tests) — skip plain artisan runs to avoid booting the HTTP kernel and
        // triggering unrelated work during artisan commands / migrations.
        if (
            $this->app->bound(Kernel::class)
            && (! $this->app->runningInConsole() || $this->app->runningUnitTests())
        ) {
            $kernel = $this->app->make(Kernel::class);
            if (method_exists($kernel, 'prependMiddleware')) {
                $kernel->prependMiddleware(RedirectMiddleware::class);
            }
        }
    }

    /**
     * Register Blade components.
     */
    protected function registerBladeComponents(): void
    {
        Blade::component('Seo::components.seo-tags', 'seo-tags');
        Blade::component('Seo::components.seo-badge', 'seo-badge');
        Blade::component('Seo::components.seo-stats-widget', 'seo-stats-widget');
        Blade::component('Seo::components.seo-image', 'seo-image');
        Blade::component('Seo::components.seo-hero-image', 'seo-hero-image');
        Blade::component('Seo::components.serp-preview', 'serp-preview');
        Blade::component('Seo::components.seo-health-widget', 'seo-health-widget');

        Blade::directive('seoBadge', function (string $expression) {
            return "<?php echo view('Seo::components.seo-badge', ['model' => {$expression}])->render(); ?>";
        });

        // Register directive for rendering SEO tags
        Blade::directive('seoTags', function ($expression) {
            return "<?php echo app('seo')->render(); ?>";
        });

        // Register directive for rendering schema tags
        Blade::directive('schemaScript', function ($expression) {
            return "<?php echo app('seo')->renderSchema(); ?>";
        });

        // Register directive for rendering Schema.org JSON-LD from the SeoService schemas
        Blade::directive('schemaOrg', function () {
            return "<?php echo app('seo')->renderSchema(); ?>";
        });

        // Register directive for including SEO form
        Blade::directive('seoForm', function ($expression) {
            return "<?php echo view('Seo::partials.seo-form', ['model' => $expression])->render(); ?>";
        });

        // Emite <link rel="alternate" hreflang="..."> para la URL indicada
        // Uso: @hreflang('https://site.com/en/about')
        Blade::directive('hreflang', function ($expression) {
            return "<?php echo app(\\Modules\\Seo\\Services\\HreflangService::class)->render({$expression}); ?>";
        });

        // Inyecta el beacon de Core Web Vitals (LCP/INP/CLS) en <head>
        // Uso: @seoWebVitalsBeacon
        Blade::directive('seoWebVitalsBeacon', function () {
            return "<?php echo view('Seo::partials.web-vitals-beacon')->render(); ?>";
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
                CheckBrokenLinksCommand::class,
                SeoWeeklyReportCommand::class,
                SeoCleanupCommand::class,
                IndexNowSubmitCommand::class,
                PurgeWebVitalsCommand::class,
                SeoHealthCommand::class,
                SeoPageSpeedCommand::class,
                SeoUpdateRankingsCommand::class,
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
                ['label' => 'Dashboard SEO', 'route' => 'setting.seo.dashboard'],
                ['label' => 'Analytics SEO', 'route' => 'setting.seo.analytics.index'],
                ['label' => 'Meta SEO', 'route' => 'setting.seo.metas.index'],
                ['label' => 'Gestión Hreflang', 'route' => 'setting.seo.metas.hreflang'],
                ['label' => 'Auditoría SEO', 'route' => 'setting.seo.audit.index'],
                ['label' => 'Historial auditorías', 'route' => 'setting.seo.audit.history'],
                ['label' => 'Redirecciones', 'route' => 'setting.seo.redirects.index'],
                ['label' => 'Errores 404', 'route' => 'setting.seo.404-logs.index'],
                ['label' => 'Plantillas SEO', 'route' => 'setting.seo.templates.index'],
                ['label' => 'Contenido sin SEO', 'route' => 'setting.seo.orphans.index'],
                ['label' => 'URLs del sitio', 'route' => 'setting.seo.page-urls.index'],
                ['label' => 'URLs estáticas', 'route' => 'setting.seo.static-urls.index'],
                ['label' => 'Robots.txt', 'route' => 'setting.seo.robots.edit'],
                ['label' => 'llms.txt (IA)', 'route' => 'setting.seo.llms.edit'],
                ['label' => 'IndexNow', 'route' => 'setting.seo.indexnow.index'],
                ['label' => 'Core Web Vitals', 'route' => 'setting.seo.web-vitals.index'],
                ['label' => 'Sitemap XML', 'route' => 'setting.seo.sitemap.index'],
                ['label' => 'Reporte SEO', 'route' => 'setting.seo.report.index'],
                ['label' => 'Verificación', 'route' => 'setting.seo.verification.index'],
                ['label' => 'Search Console', 'route' => 'setting.seo.search-console.import'],
                ['label' => 'Configuración SEO', 'route' => 'setting.seo.settings.edit'],
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
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('sitemap.cache', CacheSitemapResponse::class);
    }

    protected function registerTrack404Middleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', Track404Middleware::class);

        // Inject performance hints + security headers on anonymous HTML responses
        $router->pushMiddlewareToGroup('web', PerformanceHintsMiddleware::class);
    }

    /**
     * Register X-Robots-Tag middleware alias.
     * Apply manually with the 'seo.x-robots' alias on routes that need it.
     */
    protected function registerXRobotsTagMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('seo.x-robots', XRobotsTagMiddleware::class);
    }

    /**
     * Register automatic pagination rel=prev/next middleware.
     * Pushes to the web group so it applies to all paginated public pages.
     */
    protected function registerAutoPaginationMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('seo.pagination', AutoPaginationMiddleware::class);
        $router->pushMiddlewareToGroup('web', AutoPaginationMiddleware::class);
    }

    protected function registerSitemapCommands(): void
    {
        $this->commands([
            GenerateSitemapCommand::class,
            PingSitemapCommand::class,
        ]);
    }

    protected function registerSitemapScheduler(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(function () {
                Cache::forget('sitemap.xml');
            })->daily()->name('sitemap:clear-cache');
        });
    }

    protected function registerCleanupScheduler(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->job(new CleanupOld404Logs)->daily()->name('seo:cleanup-404-logs')->withoutOverlapping();
            $schedule->job(new CleanupOldAuditLogs)->weekly()->name('seo:cleanup-audit-logs')->withoutOverlapping();
            $schedule->job(new SendSeoWeeklyReport)
                ->weeklyOn(1, '8:00')
                ->name('seo:weekly-report')
                ->withoutOverlapping();
            $schedule->job(new BulkSeoAuditJob)
                ->weekly()
                ->sundays()
                ->at('02:00')
                ->name('seo:weekly-audit')
                ->withoutOverlapping();

            $schedule->job(new DetectContentDecayJob)
                ->weekly()
                ->mondays()
                ->at('04:00')
                ->name('seo:content-decay')
                ->withoutOverlapping();

            $schedule->command('seo:purge-web-vitals')
                ->dailyAt('03:45')
                ->name('seo:purge-web-vitals')
                ->withoutOverlapping();

            // Daily health check — writes output to storage/logs/seo-health.log
            // so the ops team can tail it and monitor the exit code.
            $schedule->command('seo:health')
                ->dailyAt('07:00')
                ->name('seo:health')
                ->appendOutputTo(storage_path('logs/seo-health.log'))
                ->withoutOverlapping();

            // Weekly PageSpeed snapshot for top URLs (only if API key is configured)
            if (seo_setting('pagespeed_api_key', config('Seo.pagespeed_api_key'))) {
                $schedule->command('seo:pagespeed')
                    ->weeklyOn(2, '05:30')
                    ->name('seo:pagespeed-weekly')
                    ->withoutOverlapping();
            }
        });
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
