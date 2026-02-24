<?php

namespace Modules\Reviews\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Listeners\NotifyOnNewReview;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Policies\ReviewAutoSuggestionPolicy;
use Modules\Reviews\Policies\ReviewGoogleConnectionPolicy;
use Modules\Reviews\Policies\ReviewPolicy;
use Modules\Reviews\Policies\ReviewReplyPolicy;
use Modules\Reviews\Policies\ReviewReplyTemplatePolicy;
use Modules\Theme\Services\NavService;

class ReviewsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Reviews';

    protected string $moduleNameLower = 'reviews';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        // Load configs in boot instead of register to avoid cache resolution issues
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/general.php'),
            'reviews.general'
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/google.php'),
            'reviews.google'
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/permissions.php'),
            'reviews.permissions'
        );

        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), 'reviews');

        $this->publishes([
            module_path($this->moduleName, 'config/general.php') => config_path('reviews/general.php'),
            module_path($this->moduleName, 'config/google.php') => config_path('reviews/google.php'),
        ], 'reviews-config');

        $this->registerPolicies();
        $this->registerScheduledTasks();
        $this->registerCommands();
        $this->registerEventListeners();
        $this->registerMenus();
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Reviews\Console\Commands\InstallReviewsCommand::class,
                \Modules\Reviews\Console\Commands\SyncReviewsCommand::class,
                \Modules\Reviews\Console\Commands\CleanupExpiredConnectionsCommand::class,
                \Modules\Reviews\Console\Commands\GenerateReportCommand::class,
                \Modules\Reviews\Console\Commands\PruneOldReviewsCommand::class,
            ]);
        }
    }

    protected function registerPolicies(): void
    {
        Gate::policy(\Modules\Reviews\Models\Review::class, ReviewPolicy::class);
        Gate::policy(\Modules\Reviews\Models\ReviewReply::class, ReviewReplyPolicy::class);
        Gate::policy(ReviewGoogleConnection::class, ReviewGoogleConnectionPolicy::class);
        Gate::policy(\Modules\Reviews\Models\ReviewReplyTemplate::class, ReviewReplyTemplatePolicy::class);
        Gate::policy(\Modules\Reviews\Models\ReviewAutoSuggestion::class, ReviewAutoSuggestionPolicy::class);
    }

    protected function registerScheduledTasks(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(function () {
                ReviewGoogleLocation::query()
                    ->active()
                    ->needingSync()
                    ->each(function (ReviewGoogleLocation $location) {
                        SyncGoogleReviewsJob::dispatch($location);
                    });
            })
                ->everyFifteenMinutes()
                ->name('reviews:sync-google-reviews')
                ->withoutOverlapping()
                ->onOneServer();
        });
    }

    protected function registerEventListeners(): void
    {
        Event::listen(
            ReviewSynced::class,
            NotifyOnNewReview::class
        );
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem('reviews', [
            'icon' => 'fa-duotone fa-star',
            'tooltip' => 'Reseñas',
            'sidebar_id' => 'reviews',
            'order' => 50,
        ]);

        NavService::registerSidebar('reviews', [
            'title' => 'Gestion de reseñas',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'reviews.dashboard', 'permission' => 'reviews.reviews.view'],
                ['label' => 'Todas las reseñas', 'route' => 'reviews.index', 'permission' => 'reviews.reviews.view'],
            ],
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Reseñas',
            'items' => [
                ['label' => 'Configuracion general', 'route' => 'settings.reviews.config.index', 'permission' => 'reviews.settings.manage'],
                ['label' => 'Conexiones Google', 'route' => 'settings.reviews.connections.index', 'permission' => 'reviews.google.manage'],
                ['label' => 'Ubicaciones', 'route' => 'settings.reviews.locations.index', 'permission' => 'reviews.google.manage'],
                ['label' => 'Plantillas de respuesta', 'route' => 'settings.reviews.templates.index', 'permission' => 'reviews.templates.view'],
            ],
        ]);
    }
}
