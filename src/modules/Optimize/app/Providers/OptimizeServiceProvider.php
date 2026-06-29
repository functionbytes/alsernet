<?php

namespace Modules\Optimize\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Optimize\Console\Commands\ConvertThemePngToWebpCommand;
use Modules\Optimize\Console\Commands\EnableAllCommand;
use Modules\Optimize\Console\Commands\GenerateCriticalCssCommand;
use Modules\Optimize\Console\Commands\MinifyModuleAssetsCommand;
use Modules\Optimize\Console\Commands\MinifyThemeAssetsCommand;
use Modules\Optimize\Console\Commands\OptimizeModuleImagesCommand;
use Modules\Optimize\Console\Commands\OptimizeThemeImagesCommand;
use Modules\Optimize\Console\Commands\PurgeCacheCommand;
use Modules\Optimize\Http\Middleware\AddImageDecoding;
use Modules\Optimize\Http\Middleware\AddImageDimensions;
use Modules\Optimize\Http\Middleware\AddLoadingLazy;
use Modules\Optimize\Http\Middleware\AsyncStylesheets;
use Modules\Optimize\Http\Middleware\CacheControlHeaders;
use Modules\Optimize\Http\Middleware\CollapseWhitespace;
use Modules\Optimize\Http\Middleware\DeferJavascript;
use Modules\Optimize\Http\Middleware\ElideAttributes;
use Modules\Optimize\Http\Middleware\GzipResponse;
use Modules\Optimize\Http\Middleware\InjectCriticalCss;
use Modules\Optimize\Http\Middleware\InjectCriticalPreload;
use Modules\Optimize\Http\Middleware\InjectFontDisplay;
use Modules\Optimize\Http\Middleware\InlineCss;
use Modules\Optimize\Http\Middleware\InsertDNSPrefetch;
use Modules\Optimize\Http\Middleware\LinkPreloadHeader;
use Modules\Optimize\Http\Middleware\MinifyInlineScripts;
use Modules\Optimize\Http\Middleware\MinifyInlineStyles;
use Modules\Optimize\Http\Middleware\OptimizeResponseCache;
use Modules\Optimize\Http\Middleware\RemoveComments;
use Modules\Optimize\Http\Middleware\RemoveQuotes;
use Modules\Optimize\Http\Middleware\RewriteMinAssets;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class OptimizeServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Optimize';

    protected string $moduleNameLower = 'optimize';

    /** @var array<string, class-string> */
    private const MIDDLEWARE_MAP = [
        'optimize.remove_comments' => RemoveComments::class,
        'optimize.collapse_whitespace' => CollapseWhitespace::class,
        'optimize.elide_attributes' => ElideAttributes::class,
        'optimize.remove_quotes' => RemoveQuotes::class,
        'optimize.minify_inline_styles' => MinifyInlineStyles::class,
        'optimize.minify_inline_scripts' => MinifyInlineScripts::class,
        'optimize.defer_javascript' => DeferJavascript::class,
        'optimize.add_loading_lazy' => AddLoadingLazy::class,
        'optimize.async_stylesheets' => AsyncStylesheets::class,
        'optimize.inline_css' => InlineCss::class,
        'optimize.insert_dns_prefetch' => InsertDNSPrefetch::class,
        'optimize.inject_font_display' => InjectFontDisplay::class,
        'optimize.inject_critical_preload' => InjectCriticalPreload::class,
        'optimize.add_image_dimensions' => AddImageDimensions::class,
        'optimize.add_image_decoding' => AddImageDecoding::class,
        'optimize.cache_control_headers' => CacheControlHeaders::class,
        'optimize.rewrite_min_assets' => RewriteMinAssets::class,
        'optimize.link_preload_header' => LinkPreloadHeader::class,
        'optimize.inject_critical_css' => InjectCriticalCss::class,
    ];

    public function boot(): void
    {
        if (Module::find('Optimize')?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerMenus();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ConvertThemePngToWebpCommand::class,
                EnableAllCommand::class,
                GenerateCriticalCssCommand::class,
                MinifyModuleAssetsCommand::class,
                MinifyThemeAssetsCommand::class,
                OptimizeModuleImagesCommand::class,
                OptimizeThemeImagesCommand::class,
                PurgeCacheCommand::class,
            ]);
        }

        $this->app['events']->listen(RouteMatched::class, function (): void {
            $this->registerOptimizationMiddleware();
        });

        // Schedules para mantener el sitio optimizado sin intervención manual.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            // Purga semanal del response cache — evita servir HTML viejo tras
            // cambios de catálogo/shortcodes que pasen desapercibidos.
            $schedule->command('optimize:purge-cache')
                ->weekly()->mondays()->at('04:00')
                ->withoutOverlapping()
                ->onOneServer();

            // Asegura que cualquier imagen subida sin .webp / srcset (p.ej.
            // si el job falló o la optimize auto estaba off) termine con sus
            // variantes. Idempotente — saltea las ya convertidas.
            $schedule->command('media:optimize-all --quality=82')
                ->weekly()->sundays()->at('03:00')
                ->withoutOverlapping()
                ->onOneServer();
        });
    }

    protected function registerOptimizationMiddleware(): void
    {
        try {
            if (Setting::get('optimize.enabled', '0') !== '1') {
                return;
            }

            $router = $this->app['router'];

            if (Setting::get('optimize.response_cache', '0') === '1') {
                $router->pushMiddlewareToGroup('web', OptimizeResponseCache::class);
            }

            foreach (self::MIDDLEWARE_MAP as $settingKey => $middlewareClass) {
                if (Setting::get($settingKey, '0') === '1') {
                    $router->pushMiddlewareToGroup('web', $middlewareClass);
                }
            }

            // Gzip compression — must run last so it compresses the fully-rendered response
            if (Setting::get('optimize.gzip_response', '0') === '1') {
                $router->pushMiddlewareToGroup('web', GzipResponse::class);
            }
        } catch (\Throwable) {
            // DB not available (e.g. during migrations or test setup)
        }
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/general.php') => config_path('Optimize/general.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/general.php'),
            'Optimize.general'
        );
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    protected function registerRoutes(): void
    {
        $webPath = module_path($this->moduleName, 'routes/web.php');

        Route::middleware(['web', 'auth'])
            ->prefix('panel/setting/optimize')
            ->name('settings.optimize.')
            ->group(function () use ($webPath) {
                require $webPath;
            });
    }

    protected function registerMenus(): void
    {

        NavService::addItemsToSection('settings', 'Configuraciones', [
            ['label' => 'Configuracion de optimización', 'route' => 'settings.optimize.index'],
            ['label' => 'Herramientas de optimización', 'route' => 'settings.optimize.tools'],
        ]);

    }

    /**
     * @return string[]
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
