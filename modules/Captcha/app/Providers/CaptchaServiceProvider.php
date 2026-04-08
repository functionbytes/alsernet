<?php

namespace Modules\Captcha\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory;
use Modules\Captcha\Facades\Captcha as CaptchaFacade;
use Modules\Captcha\Services\Captcha;
use Modules\Captcha\Services\CaptchaV3;
use Modules\Captcha\Services\MathCaptcha;
use Modules\Theme\Services\NavService;

class CaptchaServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Captcha';

    protected string $moduleNameLower = 'captcha';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->bootValidator();
        $this->registerMenus();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Register captcha singleton
        $this->app->singleton('captcha', function () {
            $key = setting('captcha_site_key');
            $secret = setting('captcha_secret');

            if (setting('captcha_type') === 'v3') {
                return new CaptchaV3($key, $secret);
            }

            return new Captcha($key, $secret);
        });

        // Register math-captcha singleton
        $this->app->singleton('math-captcha', function ($app) {
            return new MathCaptcha($app['session']);
        });

        // Register facade alias
        AliasLoader::getInstance()->alias('Captcha', CaptchaFacade::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );

        // Load general config
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/general.php'),
            $this->moduleNameLower.'.general'
        );

        // Load permissions config
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/permissions.php'),
            $this->moduleNameLower.'.permissions'
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
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Boot validator extensions
     */
    public function bootValidator(): void
    {
        $app = $this->app;

        /**
         * @var Factory $validator
         */
        $validator = $app['validator'];

        // Register captcha validation rule
        $validator->extend('captcha', function ($attribute, $value, $parameters) use ($app) {
            if (! $app['captcha']->reCaptchaEnabled()) {
                return true;
            }

            if (! is_string($value)) {
                return false;
            }

            if (setting('captcha_type') === 'v3') {
                if (empty($parameters)) {
                    $parameters = ['form', (float) setting('recaptcha_score', 0.6)];
                }
            } else {
                $parameters = $this->mapParameterToOptions($parameters);
            }

            return $app['captcha']->verify($value, $this->app['request']->getClientIp(), $parameters);
        }, __('Captcha Verification Failed!'));

        // Register math captcha validation rule
        $validator->extend('math_captcha', function ($attribute, $value) {
            if (! is_string($value)) {
                return false;
            }

            return $this->app['math-captcha']->verify($value);
        }, __('Math Captcha Verification Failed!'));
    }

    /**
     * Map parameter to options array
     */
    public function mapParameterToOptions(?array $parameters = []): array
    {
        if (! is_array($parameters)) {
            return [];
        }

        $options = [];

        foreach ($parameters as $parameter) {
            $option = explode(':', $parameter);
            if (count($option) === 2) {
                Arr::set($options, $option[0], $option[1]);
            }
        }

        return $options;
    }

    /**
     * Registrar menus del modulo Captcha
     */
    protected function registerMenus(): void
    {
        NavService::addItemsToSection('settings', 'Configuraciones', [
            ['label' => 'Captcha', 'route' => 'captcha.settings'],
        ]);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['captcha', 'math-captcha'];
    }

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
