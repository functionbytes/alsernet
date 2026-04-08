<?php

namespace Modules\Optimize\Providers;

use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Optimize\Http\Middleware\AddLoadingLazy;
use Modules\Optimize\Http\Middleware\CollapseWhitespace;
use Modules\Optimize\Http\Middleware\DeferJavascript;
use Modules\Optimize\Http\Middleware\ElideAttributes;
use Modules\Optimize\Http\Middleware\InlineCss;
use Modules\Optimize\Http\Middleware\InsertDNSPrefetch;
use Modules\Optimize\Http\Middleware\MinifyInlineScripts;
use Modules\Optimize\Http\Middleware\MinifyInlineStyles;
use Modules\Optimize\Http\Middleware\OptimizeResponseCache;
use Modules\Optimize\Http\Middleware\RemoveComments;
use Modules\Optimize\Http\Middleware\RemoveQuotes;
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
        'optimize.inline_css' => InlineCss::class,
        'optimize.insert_dns_prefetch' => InsertDNSPrefetch::class,
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

        $this->app['events']->listen(RouteMatched::class, function (): void {
            $this->registerOptimizationMiddleware();
        });
    }

    protected function registerOptimizationMiddleware(): void
    {
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
