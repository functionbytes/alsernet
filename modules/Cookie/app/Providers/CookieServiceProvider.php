<?php

namespace Modules\Cookie\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Cookie\Console\Commands\CheckConsentRateCommand;
use Modules\Cookie\Console\Commands\CleanupCookieLogsCommand;
use Modules\Cookie\Models\CookieInventory;
use Modules\Core\Models\Setting;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class CookieServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Cookie';

    protected string $moduleNameLower = 'cookie';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        if (Module::find('Cookie')?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->loadHelpers();
        $this->registerRoutes();
        $this->registerMenus();
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerShortcodes();

        $this->publishes([
            module_path($this->moduleName, 'public') => public_path('modules/'.$this->moduleName),
        ], 'cookie-assets');

        $this->app['events']->listen(RouteMatched::class, function (): void {
            $this->registerCookieAssets();
        });
    }

    /**
     * Register cookie consent assets and view composer
     */
    protected function registerCookieAssets(): void
    {
        if (! $this->shouldDisplayCookie()) {
            return;
        }

        // Disable encryption for the cookie consent cookie
        $this->app->resolving(EncryptCookies::class, function (EncryptCookies $encryptCookies): void {
            $encryptCookies->disableFor(config('Cookie.general.cookie_name'));
        });
    }

    /**
     * Check if cookie consent should be displayed
     */
    protected function shouldDisplayCookie(): bool
    {
        if ($this->isInAdmin()) {
            return false;
        }

        return Setting::get('cookie.enabled', '0') === '1';
    }

    /**
     * Check if we're in the admin/settings area
     */
    protected function isInAdmin(): bool
    {
        return request()->is('panel/*') || request()->is('settings/*') || request()->is('managers/*');
    }

    protected function registerTranslations(): void
    {
        $langPath = module_path($this->moduleName, 'lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/general.php') => config_path('Cookie/general.php'),
            module_path($this->moduleName, 'config/permissions.php') => config_path('Cookie/permissions.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/general.php'),
            'Cookie.general'
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/permissions.php'),
            'Cookie.permissions'
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Load helper files.
     */
    protected function loadHelpers(): void
    {
        $helpersPath = module_path($this->moduleName, 'helpers/CookieHelper.php');

        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }

    /**
     * Register module routes.
     */
    protected function registerRoutes(): void
    {
        $webPath = module_path($this->moduleName, 'routes/web.php');
        $publicPath = module_path($this->moduleName, 'routes/public.php');

        Route::middleware(['web', 'auth'])
            ->prefix('panel/settings/cookie')
            ->name('settings.cookie.')
            ->group(function () use ($webPath) {
                require $webPath;
            });

        if (file_exists($publicPath)) {
            Route::middleware(['web'])
                ->prefix('cookie')
                ->name('cookie.')
                ->group(function () use ($publicPath) {
                    require $publicPath;
                });
        }
    }

    protected function registerCommands(): void
    {
        $this->commands([
            CleanupCookieLogsCommand::class,
            CheckConsentRateCommand::class,
        ]);
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('cookie:cleanup-logs')
                ->daily()
                ->at('03:00')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('cookie:check-consent-rate')
                ->dailyAt('08:00')
                ->withoutOverlapping()
                ->onOneServer();
        });
    }

    /**
     * Register module menus.
     */
    protected function registerMenus(): void
    {
        NavService::registerSidebar('settings', [
            'title' => 'Configuración de cookies',
            'items' => [
                ['label' => 'Cookies', 'route' => 'settings.cookie.index'],
                ['label' => 'Registros de consentimiento', 'route' => 'settings.cookie.logs'],
                ['label' => 'Inventario de cookies', 'route' => 'settings.cookie.inventory'],
            ],
        ]);
    }

    /**
     * Register [cookie-inventory] shortcode — renders grouped cookie inventory as plain text.
     */
    protected function registerShortcodes(): void
    {
        $this->app->booted(function (): void {
            if (! $this->app->bound('shortcode')) {
                return;
            }

            app('shortcode')->register('cookie-inventory', function (): string {
                $categories = config('Cookie.general.cookie_categories', []);
                $inventory = CookieInventory::query()->active()->ordered()->get()->groupBy('category');

                $html = '';
                foreach ($categories as $key => $category) {
                    $items = $inventory->get($key);
                    if (! $items || $items->isEmpty()) {
                        continue;
                    }

                    $html .= '<p><strong>'.e(__($category['name'])).':</strong> '.e(__($category['description'])).'</p>';
                    $html .= '<ul>';
                    foreach ($items as $cookie) {
                        $html .= '<li><strong>'.e($cookie->name).'</strong> — '.e($cookie->provider).' ('.e($cookie->duration).'): '.e($cookie->purpose).'</li>';
                    }
                    $html .= '</ul>';
                }

                return $html;
            }, [
                'name' => 'Inventario de cookies',
                'description' => 'Lista agrupada del inventario de cookies activas del sitio.',
                'category' => 'cookie',
            ]);
        });
    }

    /**
     * Get publishable view paths
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
