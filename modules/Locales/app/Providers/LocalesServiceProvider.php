<?php

namespace Modules\Locales\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Locales\Console\Commands\SyncLangsCommand;
use Modules\Locales\Console\Commands\TranslationsMissingCommand;
use Modules\Locales\Models\Locale;
use Modules\Locales\Observers\LocaleObserver;
use Modules\Locales\Services\LocaleService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LocalesServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Locales';

    protected string $nameLower = 'locales';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerMenus();

        Locale::observe(LocaleObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SyncLangsCommand::class, TranslationsMissingCommand::class]);
        }
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(LocaleService::class);
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }

        // Register active theme translations so __() works in theme views.
        // Must run after boot AND clear the translator's JSON cache so already-cached
        // lines (from other modules) are reloaded including the theme's lang files.
        $this->app->booted(function () {
            try {
                $theme = \DB::table('settings')->where('key', 'template')->value('value') ?? 'caixilhariablanco';
            } catch (\Throwable) {
                $theme = 'caixilhariablanco';
            }

            $themeLangPath = base_path("platform/themes/{$theme}/lang");

            if (! is_dir($themeLangPath)) {
                return;
            }

            $translator = $this->app['translator'];
            $translator->getLoader()->addJsonPath($themeLangPath);

            // Clear cached JSON translation lines (namespace=*, group=*) for all
            // locales so the next __() call reloads with the theme path included.
            $reflection = new \ReflectionClass($translator);
            $loadedProp = $reflection->getProperty('loaded');
            $loadedProp->setAccessible(true);
            $loaded = $loadedProp->getValue($translator);

            unset($loaded['*']['*']);

            $loadedProp->setValue($translator, $loaded);
        });
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (! is_dir($configPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
            $segments = explode('.', $this->nameLower.'.'.$configKey);

            $normalized = [];
            foreach ($segments as $segment) {
                if (end($normalized) !== $segment) {
                    $normalized[] = $segment;
                }
            }

            $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

            $this->publishes([$file->getPathname() => config_path($config)], 'config');
            $this->mergeConfigFrom($file->getPathname(), $key);
        }
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    protected function registerMenus(): void
    {
        NavService::addItemsToSection('settings', 'Localización', [
            ['label' => 'Idiomas', 'route' => 'locales.index', 'permission' => 'locale.view'],
            ['label' => 'Traducciones', 'route' => 'locales.translations.index', 'permission' => 'locale.view'],
        ]);
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
