<?php

namespace Modules\Template\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
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
        $this->registerMenus();
        $this->loadMenuHelpers();
        $this->loadTemplateFunctions();
        $this->registerDynamicShortcodes();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register manager service
        $this->app->singleton(\Modules\Template\Services\TemplateManager::class);

        // Register template service
        $this->app->singleton(\Modules\Template\Services\TemplateService::class);

        // Register menu service
        $this->app->singleton(\Modules\Template\Services\MenuService::class);

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
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Template\Console\ClearMenuCacheCommand::class,
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
                $shortcodes = \Modules\Template\Models\Shortcode::query()
                    ->where('is_active', true)
                    ->whereNotNull('render_template')
                    ->get();

                foreach ($shortcodes as $sc) {
                    app('shortcode')->register($sc->key, function (array $attrs, string $content) use ($sc): string {
                        $html = $sc->render_template;
                        foreach ($attrs as $key => $val) {
                            $html = str_replace('{'.$key.'}', e($val), $html);
                        }

                        return str_replace('{content}', $content, $html);
                    });
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
     * Load template functions from the active template
     */
    protected function loadTemplateFunctions(): void
    {
        try {
            $activeTemplateName = $this->getActiveTemplateName();
            $functionsPath = base_path('platform/themes/'.$activeTemplateName.'/functions/functions.php');

            if (file_exists($functionsPath)) {
                require $functionsPath;
            }

            // Also load helpers
            $helpersPath = base_path('platform/themes/'.$activeTemplateName.'/functions/helpers.php');
            if (file_exists($helpersPath)) {
                require $helpersPath;
            }
        } catch (\Exception $e) {
            // Silently fail if unable to load template functions
            \Log::debug('Could not load template functions: '.$e->getMessage());
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
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
