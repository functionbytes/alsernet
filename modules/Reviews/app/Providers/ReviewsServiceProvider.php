<?php

namespace Modules\Reviews\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Reviews\Console\Commands\DrainStoreOutboxCommand;
use Modules\Reviews\Console\Commands\FetchGoogleReviewsCommand;
use Modules\Reviews\Console\Commands\ScreenReviewsCommand;
use Modules\Reviews\Console\Commands\SyncReviewsCommand;
use Modules\Reviews\Services\PrestashopReviewClient;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class ReviewsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Reviews';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            'reviews'
        );

        $this->app->singleton(PrestashopReviewClient::class);
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path('reviews.php'),
        ], 'config');

        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), 'reviews');
        $this->registerRoutes();
        $this->registerCommands();
        $this->registerSchedule();
        $this->registerNav();
    }

    protected function registerRoutes(): void
    {
        // Receptor del webhook: autenticado por HMAC, no por Sanctum. Mismo
        // esquema que api/forms/webhooks y api/helpdeskprestashop/webhooks.
        Route::middleware(['api', 'throttle:120,1'])
            ->prefix('api/reviews/webhooks')
            ->name('api.reviews.webhooks.')
            ->group(module_path($this->moduleName, 'routes/webhooks.php'));

        Route::middleware(['web', 'auth', 'can:reviews.view'])
            ->prefix('panel/reviews')
            ->group(module_path($this->moduleName, 'routes/managers.php'));
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncReviewsCommand::class,
                FetchGoogleReviewsCommand::class,
                ScreenReviewsCommand::class,
                DrainStoreOutboxCommand::class,
            ]);
        }
    }

    /**
     * Lectura diaria de las fichas de Google, de madrugada: las reseñas nuevas
     * amanecen en la bandeja para revisarlas por la mañana.
     */
    protected function registerSchedule(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            /* La tienda encola cada opinión nueva y su cron es quien la empuja,
               pero ese contenedor no lleva demonio cron: se dispara desde aquí
               para que una opinión recién escrita llegue a la bandeja enseguida. */
            $schedule->command('reviews:drain-store')
                ->everyMinute()
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground();

            $schedule->command('reviews:fetch-google')
                ->dailyAt('05:30')
                ->withoutOverlapping()
                ->onOneServer();
        });
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Tienda',
            'order' => 240,
            'items' => [
                ['label' => 'Opiniones', 'route' => 'reviews.index', 'permission' => 'reviews.view'],
                ['label' => 'Fichas de opiniones', 'route' => 'reviews.sources.index', 'permission' => 'reviews.settings'],
                ['label' => 'Ajustes de opiniones', 'route' => 'reviews.settings', 'permission' => 'reviews.settings'],
            ],
        ]);
    }
}
