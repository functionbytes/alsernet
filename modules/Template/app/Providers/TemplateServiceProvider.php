<?php

namespace Modules\Template\Providers;

use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Factory;
use Modules\Captcha\Facades\Captcha;
use Modules\Core\Models\Setting;
use Modules\Page\Http\Controllers\VisualEditorController;
use Modules\Template\Console\ClearMenuCacheCommand;
use Modules\Template\Console\ExportTemplateCommand;
use Modules\Template\Console\ThemeActivateCommand;
use Modules\Template\Console\ThemeLinkCommand;
use Modules\Template\Facades\Menu;
use Modules\Template\Helpers\BaseHelper;
use Modules\Template\Helpers\RvMedia;
use Modules\Template\Helpers\SeoHelper;
use Modules\Template\Models\MenuItem;
use Modules\Template\Models\Shortcode;
use Modules\Template\Observers\MenuItemObserver;
use Modules\Template\Services\MenuService;
use Modules\Template\Services\TemplateManager;
use Modules\Template\Services\TemplateService;
use Modules\Template\Theme\Asset;
use Modules\Template\Theme\Breadcrumb;
use Modules\Template\Theme\Theme;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TemplateServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Template';

    protected string $nameLower = 'template';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerObservers();
        $this->registerMenus();
        $this->loadMenuHelpers();
        $this->loadTemplateFunctions();
        $this->loadThemeRoutes();
        $this->initializeThemeEngine();
        $this->registerDynamicShortcodes();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register Theme engine
        $this->app->singleton(Asset::class);

        $this->app->singleton(Theme::class, function ($app) {
            return new Theme(
                $app->make(Dispatcher::class),
                $app->make(Factory::class),
                $app->make(Asset::class),
            );
        });

        $this->app->alias(Theme::class, 'theme.engine');

        AliasLoader::getInstance()->alias(
            'Theme',
            \Modules\Template\Facades\Theme::class
        );

        // BaseHelper
        $this->app->singleton(BaseHelper::class);
        AliasLoader::getInstance()->alias(
            'BaseHelper',
            \Modules\Template\Facades\BaseHelper::class
        );

        // RvMedia
        $this->app->singleton(RvMedia::class);
        AliasLoader::getInstance()->alias(
            'RvMedia',
            \Modules\Template\Facades\RvMedia::class
        );

        // SeoHelper (stub de compatibilidad con plantillas Botble)
        $this->app->singleton(SeoHelper::class);
        AliasLoader::getInstance()->alias(
            'SeoHelper',
            \Modules\Template\Facades\SeoHelper::class
        );

        // Menu facade (compatibilidad con Botble: Menu::renderMenuLocation())
        AliasLoader::getInstance()->alias(
            'Menu',
            Menu::class
        );

        // Breadcrumb
        $this->app->singleton(Breadcrumb::class);
        AliasLoader::getInstance()->alias(
            'Breadcrumb',
            \Modules\Template\Facades\Breadcrumb::class
        );

        // Register manager service
        $this->app->singleton(TemplateManager::class);

        // Register template service
        $this->app->singleton(TemplateService::class);

        // Register menu service
        $this->app->singleton(MenuService::class);

        // Register factories
        $this->registerFactories();
    }

    /**
     * Register factories.
     *
     * Note: Laravel 8+ uses class-based factories that are auto-discovered.
     * No manual registration needed.
     */
    protected function registerFactories(): void
    {
        // Laravel 8+ factories are auto-discovered from database/factories
        // No manual registration required
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        MenuItem::observe(MenuItemObserver::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            ClearMenuCacheCommand::class,
            ExportTemplateCommand::class,
            ThemeActivateCommand::class,
            ThemeLinkCommand::class,
        ]);
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
            $this->loadTranslationsFrom(module_path($this->name, 'resources/lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'resources/lang'));
        }

        // Load active template's translations
        try {
            $activeTemplateName = $this->getActiveTemplateName();
            $templateLangPath = base_path('platform/themes/'.$activeTemplateName.'/lang');

            if (is_dir($templateLangPath)) {
                $this->loadJsonTranslationsFrom($templateLangPath);
            }
        } catch (\Exception $e) {
            // Silently fail if unable to load template translations
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        // Publish JavaScript assets
        $jsSourcePath = module_path($this->name, 'resources/js');
        $jsPublicPath = public_path('modules/'.$this->nameLower.'/js');
        $this->publishes([$jsSourcePath => $jsPublicPath], [$this->nameLower.'-assets', 'assets']);

        // Get active template path from filesystem
        $viewPaths = array_merge($this->getPublishableViewPaths(), [$sourcePath]);

        // Add active template's path so @extends('template::layouts.default') works
        try {
            $activeTemplateName = $this->getActiveTemplateName();
            $activeTemplatePath = base_path('platform/themes/'.$activeTemplateName);

            if (is_dir($activeTemplatePath)) {
                array_unshift($viewPaths, $activeTemplatePath);
            }
        } catch (\Exception $e) {
            // If something goes wrong, just continue without the active template path
        }

        $this->loadViewsFrom($viewPaths, $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the active template name from settings
     */
    private function getActiveTemplateName(): string
    {
        // Try to get from cache/settings
        if (function_exists('setting')) {
            return setting('template', 'default');
        }

        return 'default';
    }

    /**
     * Registrar menus del modulo Template
     */
    protected function registerMenus(): void
    {
        NavService::registerSidebar('settings', [
            'title' => 'Plantillas',
            'items' => [
                ['label' => 'Plantillas', 'route' => 'settings.templates.index'],
                ['label' => 'Menus', 'route' => 'settings.menus.index'],
                ['label' => 'Shortcodes', 'route' => 'settings.shortcodes.index'],
                ['label' => 'Opciones del tema', 'route' => 'settings.theme.options'],
                ['label' => 'CSS personalizado', 'route' => 'settings.templates.custom-css.edit'],
                ['label' => 'JavaScript personalizado', 'route' => 'settings.theme.custom-js'],
                ['label' => 'HTML personalizado', 'route' => 'settings.theme.custom-html'],
            ],
        ]);
    }

    /**
     * Registrar dinámicamente los shortcodes de la BD en el compiler del módulo Shortcode.
     */
    protected function registerDynamicShortcodes(): void
    {
        $this->app->booted(function () {
            try {
                $shortcodes = Shortcode::query()
                    ->where('is_active', true)
                    ->get();

                foreach ($shortcodes as $sc) {
                    // If the DB has a render_template, it takes priority over any PHP handler.
                    // If not, keep the existing PHP handler (if registered) and only register
                    // a stub so the shortcode still appears in the visual editor panel.
                    $alreadyRegistered = app('shortcode')->has($sc->key);

                    if (! $sc->render_template && $alreadyRegistered) {
                        continue;
                    }

                    $meta = [
                        'description' => $sc->description ?? '',
                        'example' => $sc->shortcode_template ?? "[{$sc->key}][/{$sc->key}]",
                        'attributes' => collect($sc->config_fields ?? [])
                            ->mapWithKeys(fn ($f) => [$f['id'] => $f['label'] ?? $f['id']])
                            ->all(),
                    ];

                    if ($sc->render_template) {
                        app('shortcode')->register($sc->key, function (array $attrs, string $content) use ($sc): string {
                            $html = $sc->render_template;

                            // Resolve {__key} translation placeholders before attribute substitution.
                            $html = preg_replace_callback('/\{__([^}]+)\}/', fn ($m) => __($m[1]), $html);

                            // Resolve {route:name} route placeholders.
                            $html = preg_replace_callback('/\{route:([^}]+)\}/', function ($m) {
                                try {
                                    return route($m[1]);
                                } catch (\Exception) {
                                    return $m[0];
                                }
                            }, $html);

                            // Resolve {captcha} → reCAPTCHA widget si está habilitado, vacío si no.
                            $captchaHtml = '';
                            if (
                                Setting::get('newsletter.recaptcha_enabled') === '1'
                                && class_exists(Captcha::class)
                                && Captcha::isEnabled()
                            ) {
                                $captchaDisplay = Captcha::display() ?? '';
                                // The theme footer defines onloadCallback + loads Google API.
                                // Captcha::display() only injects the push script via add_filter()
                                // which is not available here. So we extract the ID and push manually.
                                if ($captchaDisplay && preg_match('/id="([^"]+)"/', $captchaDisplay, $m)) {
                                    $captchaDisplay .= '<script>window.recaptchaInputs=window.recaptchaInputs||[];window.recaptchaInputs.push("'.$m[1].'");</script>';
                                }
                                $captchaHtml = '<div class="mb-3">'.$captchaDisplay.'</div>';
                            }
                            $html = str_replace('{captcha}', $captchaHtml, $html);

                            foreach ($attrs as $key => $val) {
                                $html = str_replace('{'.$key.'}', e($val), $html);
                            }

                            $html = str_replace('{content}', e($content), $html);

                            // CSS → <head> y JS → footer, una sola vez por clave y por request.
                            // Usamos el contenedor (reset en cada request) en lugar de static (persiste en FPM workers).
                            $injectedKey = 'sc.assets.'.$sc->key;
                            if (! app()->bound($injectedKey)) {
                                app()->instance($injectedKey, true);

                                if ($sc->css_code) {
                                    app('theme.engine')->append('head', "\n<style id=\"sc-{$sc->key}-css\">\n{$sc->css_code}\n</style>");
                                }

                                if ($sc->js_code) {
                                    $jsCode = str_replace('</script>', '<\/script>', $sc->js_code);
                                    app('theme.engine')->append('footer', "\n<script id=\"sc-{$sc->key}-js\">\n{$jsCode}\n</script>");
                                }
                            }

                            // In visual-editor preview mode, wrap output with a sentinel div
                            // so extractContent() can restore the raw shortcode tag on save.
                            if (app()->bound('ve_preview_wrap')) {
                                $safe = VisualEditorController::buildVeScTag($sc->key, $attrs, $content);

                                return '<div data-ve-sc="'.$safe.'">'.$html.'</div>';
                            }

                            return $html;
                        }, $meta);
                    } else {
                        // No render template — register stub so the shortcode appears in the
                        // visual editor panel. The builder inserts the raw shortcode tag,
                        // which the theme compiles on the public-facing page.
                        app('shortcode')->register($sc->key, function (array $attrs, string $content) use ($sc): string {
                            if (app()->bound('ve_preview_wrap')) {
                                $safe = VisualEditorController::buildVeScTag($sc->key, $attrs, $content);

                                return '<div data-ve-sc="'.$safe.'"></div>';
                            }

                            return '';
                        }, $meta);
                    }
                }
            } catch (\Exception) {
                // Silently fail if the table does not exist yet (e.g. before migration)
            }
        });
    }

    /**
     * Load menu helper functions.
     */
    protected function loadMenuHelpers(): void
    {
        $helpersPath = module_path($this->name, 'helpers/MenuHelper.php');

        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }

    /**
     * Load template functions from the active template.
     *
     * Deferred via app()->booted() so modules with higher priority (e.g. Widget)
     * have already registered their helpers before the theme's functions.php runs.
     * This prevents Template's stubs from overriding Widget's real dynamic_sidebar().
     */
    protected function loadTemplateFunctions(): void
    {
        $this->app->booted(function () {
            try {
                $activeTemplateName = $this->getActiveTemplateName();
                $functionsPath = base_path('platform/themes/'.$activeTemplateName.'/functions/functions.php');

                if (file_exists($functionsPath)) {
                    require $functionsPath;
                }

                $helpersPath = base_path('platform/themes/'.$activeTemplateName.'/functions/helpers.php');
                if (file_exists($helpersPath)) {
                    require $helpersPath;
                }

                // Cargar shortcodes del tema (requiere Shortcode module ya booteado y add_shortcode() disponible)
                $shortcodesPath = base_path('platform/themes/'.$activeTemplateName.'/functions/shortcodes.php');
                if (file_exists($shortcodesPath)) {
                    require $shortcodesPath;
                }

                // Cargar registros de widgets del tema
                $widgetsPath = base_path('platform/themes/'.$activeTemplateName.'/widgets');
                if (is_dir($widgetsPath) && function_exists('register_widget')) {
                    foreach (glob($widgetsPath.'/*/registration.php') as $registrationFile) {
                        require_once $registrationFile;
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Could not load template functions: '.$e->getMessage());
            }
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    protected function loadThemeRoutes(): void
    {
        $this->app->booted(function () {
            try {
                $activeTemplateName = $this->getActiveTemplateName();
                $routesPath = base_path('platform/themes/'.$activeTemplateName.'/routes/web.php');

                if (file_exists($routesPath)) {
                    require $routesPath;
                }
            } catch (\Exception $e) {
                Log::debug('Could not load theme routes: '.$e->getMessage());
            }
        });
    }

    protected function initializeThemeEngine(): void
    {
        $this->app->booted(function () {
            try {
                $activeTemplateName = $this->getActiveTemplateName();
                $theme = $this->app->make(Theme::class);
                $theme->uses($activeTemplateName)->layout(setting('layout', 'default'));
            } catch (\Exception $e) {
                Log::debug('Could not initialize Theme engine: '.$e->getMessage());
            }
        });
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach ((array) config('view.paths', []) as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
