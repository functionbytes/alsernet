<?php

namespace Modules\Theme\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Console\Commands\AuditA11yCommand;

class ThemeServiceProvider extends ServiceProvider
{
    protected string $name = 'Theme';

    protected string $nameLower = 'theme';

    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/custom.php',
            'custom'
        );

        $this->app->register(MenuServiceProvider::class);
    }

    public function boot(): void
    {
        // Register theme views in root namespace
        // This allows @extends('layouts.theme') to work without any changes
        // to the 334+ files that use it
        View::addLocation(__DIR__.'/../../resources/views');

        // Register theme:: namespace for modules that reference views with the prefix
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'theme');

        // Register helper functions
        $this->registerHelpers();

        // Publish theme assets (CSS, JS, images, libs)
        $this->publishes([
            __DIR__.'/../../public/theme' => public_path('theme'),
        ], 'theme-assets');

        // Load routes if they exist
        if (file_exists(__DIR__.'/../../routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([AuditA11yCommand::class]);
        }
    }

    /**
     * Register helper functions
     */
    private function registerHelpers(): void
    {
        $helperFile = __DIR__.'/../Helpers/helpers.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }
}
