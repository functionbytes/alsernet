<?php

namespace Modules\Cookie\Providers;

use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\ServiceProvider;

class CookieServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Cookie';

    protected string $moduleNameLower = 'Cookie';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

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

        // Register view composer
        $this->app['view']->composer('Cookie::index', function (View $view): void {
            $CookieConfig = config('Cookie.general', []);
            $view->with(compact('CookieConfig'));
        });

        // Only load assets if cookie hasn't been set
        if (! Cookie::has(config('Cookie.general.cookie_name'))) {
            $this->loadAssets();
        }

        // NOTE: add_filter() is a Botble CMS function, not available in Laravel.
        // The cookie consent banner should be included in the layout via:
        // @include('Cookie::index')
        if (function_exists('add_filter') && defined('THEME_FRONT_FOOTER')) {
            add_filter(THEME_FRONT_FOOTER, [$this, 'registerCookie'], 1346);
        }
    }

    /**
     * Load CSS and JS assets
     */
    protected function loadAssets(): void
    {
        if (function_exists('add_theme_asset')) {
            // CSS
            add_theme_asset([
                'name' => 'cookie-consent-css',
                'src' => 'vendor/modules/Cookie/css/cookie-consent.css',
                'type' => 'css',
                'version' => '1.0.0',
            ]);

            // JS
            add_theme_asset([
                'name' => 'cookie-consent-js',
                'src' => 'vendor/modules/Cookie/js/cookie-consent.js',
                'type' => 'js',
                'dependencies' => ['jquery'],
                'location' => 'footer',
                'version' => '1.0.0',
            ]);
        }
    }

    /**
     * Check if cookie consent should be displayed
     */
    protected function shouldDisplayCookie(): bool
    {
        // Don't show in settings area
        if (function_exists('is_in_admin') && is_in_admin()) {
            return false;
        }

        // Check if enabled in settings (if using theme options)
        if (function_exists('theme_option')) {
            return theme_option('cookie_consent_enable', 'yes') === 'yes';
        }

        // Default to enabled if no theme options
        return config('Cookie.general.enabled', true);
    }

    /**
     * Render cookie consent banner
     */
    public function registerCookie(?string $html): string
    {
        $CookieConfig = config('Cookie.general', []);
        $alreadyConsentedWithCookies = Cookie::has($CookieConfig['cookie_name'] ?? 'cookie_for_consent');

        if ($this->isInAdmin() || $alreadyConsentedWithCookies) {
            return $html;
        }

        return $html.view('Cookie::index')->render();
    }

    /**
     * Check if we're in settings area
     */
    protected function isInAdmin(): bool
    {
        if (function_exists('is_in_admin')) {
            return is_in_admin();
        }

        return request()->is('settings/*') || request()->is('managers/*');
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
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'resources/lang'));
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
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
