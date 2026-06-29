<?php

namespace Modules\Media\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Media\Console\Commands\ApplyRetentionPoliciesCommand;
use Modules\Media\Console\Commands\BackfillPerceptualHashCommand;
use Modules\Media\Console\Commands\CleanOrphanedChunksCommand;
use Modules\Media\Console\Commands\ConvertToWebpCommand;
use Modules\Media\Console\Commands\GdprDeleteCommand;
use Modules\Media\Console\Commands\GdprExportCommand;
use Modules\Media\Console\Commands\GenerateSpriteSheetsCommand;
use Modules\Media\Console\Commands\GenerateSrcsetCommand;
use Modules\Media\Console\Commands\MediaStatsCommand;
use Modules\Media\Console\Commands\OptimizeAllCommand;
use Modules\Media\Console\Commands\PruneOrphanMediaCommand;
use Modules\Media\Console\Commands\PurgeExpiredMediaCommand;
use Modules\Media\Console\Commands\PurgeTrashCommand;
use Modules\Media\Console\Commands\RetentionReportCommand;
use Modules\Media\Console\Commands\SendMediaDigestCommand;
use Modules\Media\Console\Commands\VerifyAuditLogCommand;
use Modules\Media\Facades\Media;
use Modules\Media\Http\Middleware\ModernImageMiddleware;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Models\MediaSetting;
use Modules\Media\Observers\ActivityLogSignatureObserver;
use Modules\Media\Observers\MediaFileOptimizeObserver;
use Modules\Media\Policies\MediaFilePolicy;
use Modules\Media\Policies\MediaFolderPolicy;
use Modules\Media\Repositories\Eloquent\MediaFileRepository;
use Modules\Media\Repositories\Eloquent\MediaFolderRepository;
use Modules\Media\Repositories\Eloquent\MediaSettingRepository;
use Modules\Media\Repositories\Interfaces\MediaFileInterface;
use Modules\Media\Repositories\Interfaces\MediaFolderInterface;
use Modules\Media\Repositories\Interfaces\MediaSettingInterface;
use Modules\Media\Services\MediaFileService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;
use Spatie\Activitylog\Models\Activity;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/media.php', 'media');

        $this->app->bind(MediaFileInterface::class, fn ($app) => new MediaFileRepository(
            new MediaFile,
            $app->make(MediaFileService::class)
        ));
        $this->app->bind(MediaFolderInterface::class, fn () => new MediaFolderRepository(new MediaFolder));
        $this->app->bind(MediaSettingInterface::class, fn () => new MediaSettingRepository(new MediaSetting));

        $this->app->booting(function (): void {
            $loader = AliasLoader::getInstance();
            $loader->alias('Media', Media::class);
        });
    }

    public function boot(): void
    {
        if (Module::find('Media')?->isDisabled()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'media');

        $this->publishes([
            __DIR__.'/../../public' => public_path('modules/Media'),
        ], ['media-assets', 'laravel-assets']);

        $this->registerPolicies();
        $this->registerMenus();
        $this->registerBladeDirectives();

        // Alias for opt-in modern image conversion on image-serving routes
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('media.modern-images', ModernImageMiddleware::class);

        // Immutable audit log HMAC signing for Media activity logs
        if (class_exists(Activity::class)) {
            Activity::observe(ActivityLogSignatureObserver::class);
        }

        // Auto-optimise uploads: generate webp + responsive srcset variants
        // asynchronously when a new MediaFile is created. Toggleable via
        // Setting 'optimize.auto_optimize_uploads' (default: 1).
        MediaFile::observe(MediaFileOptimizeObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneOrphanMediaCommand::class,
                CleanOrphanedChunksCommand::class,
                PurgeTrashCommand::class,
                GdprExportCommand::class,
                GdprDeleteCommand::class,
                BackfillPerceptualHashCommand::class,
                MediaStatsCommand::class,
                RetentionReportCommand::class,
                PurgeExpiredMediaCommand::class,
                ApplyRetentionPoliciesCommand::class,
                VerifyAuditLogCommand::class,
                SendMediaDigestCommand::class,
                ConvertToWebpCommand::class,
                GenerateSrcsetCommand::class,
                GenerateSpriteSheetsCommand::class,
                OptimizeAllCommand::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            if (config('media.chunk.clear.schedule.enabled', true)) {
                $schedule->command('media:clean-orphaned-chunks')
                    ->cron(config('media.chunk.clear.schedule.cron', '25 * * * *'));
            }
            $schedule->command('media:purge-trash')->daily();
            $schedule->command('media:purge-expired')->hourly();
            $schedule->command('media:send-digest --frequency=weekly')->weekly();
            $schedule->command('media:apply-retention')->dailyAt('03:00');
        });
    }

    protected function registerPolicies(): void
    {
        Gate::policy(MediaFile::class, MediaFilePolicy::class);
        Gate::policy(MediaFolder::class, MediaFolderPolicy::class);
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('mediaImage', function (string $expression): string {
            return "<?php
                \$_mediaParts = explode(',', {$expression}, 2);
                \$_mediaId = trim(\$_mediaParts[0] ?? '', ' \\'\"');
                \$_mediaSize = trim(\$_mediaParts[1] ?? 'thumb', ' \\'\"');
                \$_mediaUrl = media(\$_mediaId, \$_mediaSize);
            ?>
            <?php if (\$_mediaUrl): ?>
                <img src=\"<?php echo e(\$_mediaUrl); ?>\" alt=\"\" class=\"img-fluid\">
            <?php endif; ?>";
        });
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem('media', [
            'icon' => 'fa-duotone fa-thin fa-album-circle-plus',
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
