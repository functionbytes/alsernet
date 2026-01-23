<?php

namespace Modules\Media\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Theme\Services\NavService;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge module configs
        $this->mergeConfigFrom(
            __DIR__.'/../../config/media-library.php',
            'media-library'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/media.php',
            'media'
        );
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Publish config
        $this->publishes([
            __DIR__.'/../../config/media.php' => config_path('media.php'),
        ], 'media-config');

        // Publish vendor views
        $this->publishes([
            __DIR__.'/../../resources/views/vendor/media-library' => resource_path('views/vendor/media-library'),
        ], 'media-views');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'media');

        // Register policies
        $this->registerPolicies();

        // Register menus
        $this->registerMenus();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(
            \Modules\Media\Entities\MediaFile::class,
            \Modules\Media\Policies\MediaFilePolicy::class
        );

        Gate::policy(
            \Modules\Media\Entities\MediaFolder::class,
            \Modules\Media\Policies\MediaFolderPolicy::class
        );
    }

    /**
     * Registrar menús del módulo Media
     */
    protected function registerMenus(): void
    {
        // Mini-nav item para Media
        NavService::registerMiniItem('media', [
            'icon' => 'fa-duotone fa-regular fa-subtitles-slash',
            'tooltip' => 'Gestor de Medios',
            'sidebar_id' => 'media',
            'order' => 30,
        ]);

        // Sidebar con los items del módulo
        NavService::registerSidebar('media', [
            'title' => 'Gestor de Medios',
            'items' => [
                ['label' => 'Gestor de archivos', 'route' => 'media.index'],
            ],
        ]);
    }
}
