<?php

namespace Modules\Media\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Media\Console\Commands\PruneOrphanMediaCommand;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Models\MediaSetting;
use Modules\Media\Policies\MediaFilePolicy;
use Modules\Media\Policies\MediaFolderPolicy;
use Modules\Media\Repositories\Eloquent\MediaFileRepository;
use Modules\Media\Repositories\Eloquent\MediaFolderRepository;
use Modules\Media\Repositories\Eloquent\MediaSettingRepository;
use Modules\Media\Repositories\Interfaces\MediaFileInterface;
use Modules\Media\Repositories\Interfaces\MediaFolderInterface;
use Modules\Media\Repositories\Interfaces\MediaSettingInterface;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/media.php', 'media');

        $this->app->bind(MediaFileInterface::class, fn () => new MediaFileRepository(new MediaFile));
        $this->app->bind(MediaFolderInterface::class, fn () => new MediaFolderRepository(new MediaFolder));
        $this->app->bind(MediaSettingInterface::class, fn () => new MediaSettingRepository(new MediaSetting));
    }

    public function boot(): void
    {
        if (Module::find('Media')?->isDisabled()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'media');

        $this->publishes([
            __DIR__.'/../../public' => public_path('modules/Media'),
        ], ['media-assets', 'laravel-assets']);

        $this->registerPolicies();
        $this->registerMenus();

        if ($this->app->runningInConsole()) {
            $this->commands([PruneOrphanMediaCommand::class]);
        }
    }

    protected function registerPolicies(): void
    {
        Gate::policy(MediaFile::class, MediaFilePolicy::class);
        Gate::policy(MediaFolder::class, MediaFolderPolicy::class);
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem('media', [
            'icon' => 'fa-duotone fa-regular fa-subtitles-slash',
            'tooltip' => 'Gestor de Medios',
            'sidebar_id' => 'media',
            'url' => 'media.index',
            'order' => 30,
        ]);

        NavService::registerSidebar('media', [
            'title' => 'Gestor de Medios',
            'items' => [
                ['label' => 'Gestor de archivos', 'route' => 'media.index'],
            ],
        ]);
    }
}
