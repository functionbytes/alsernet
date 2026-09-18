<?php

namespace Modules\Questions\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Questions\Console\Commands\DrainStoreOutboxCommand;
use Modules\Questions\Console\Commands\SyncQuestionsCommand;
use Modules\Questions\Services\PrestashopQuestionClient;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class QuestionsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Questions';

    public function register(): void
    {
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), 'questions');
        $this->app->singleton(PrestashopQuestionClient::class);
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path('questions.php'),
        ], 'config');

        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), 'questions');
        $this->registerRoutes();
        $this->registerCommands();
        $this->registerSchedule();
        $this->registerNav();
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['api', 'throttle:120,1'])
            ->prefix('api/questions/webhooks')
            ->name('api.questions.webhooks.')
            ->group(module_path($this->moduleName, 'routes/webhooks.php'));

        Route::middleware(['web', 'auth', 'can:questions.view'])
            ->prefix('panel/questions')
            ->group(module_path($this->moduleName, 'routes/managers.php'));
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncQuestionsCommand::class,
                DrainStoreOutboxCommand::class,
            ]);
        }
    }

    /**
     * La tienda encola cada consulta nueva y su cron es quien la empuja, pero
     * ese contenedor no lleva demonio cron: se dispara desde aquí cada minuto
     * para que una consulta recién enviada aparezca en la bandeja enseguida.
     * La reconciliación diaria recoge lo que ese camino se haya dejado.
     */
    protected function registerSchedule(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('questions:drain-store')
                ->everyMinute()
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground();

            $schedule->command('questions:sync')
                ->dailyAt('05:45')
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
                ['label' => 'Consultas', 'route' => 'questions.index', 'permission' => 'questions.view'],
            ],
        ]);
    }
}
