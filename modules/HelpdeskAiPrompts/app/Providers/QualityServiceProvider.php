<?php

namespace Modules\HelpdeskAiPrompts\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskAiPrompts\Console\Commands\QualityAlertsCommand;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

/**
 * Calidad del asistente IA: regresión con conversaciones reales y alertas por
 * caso. Vive aparte de HelpdeskAiPromptsServiceProvider (vistas y traducciones
 * con namespace `helpdeskaiprompts` ya las carga éste).
 */
class QualityServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskAiPrompts';

    public function register(): void
    {
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/quality.php'), 'helpdeskaiprompts_quality');
    }

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerRoutes();
        $this->registerMenu();
        $this->registerCommandsAndSchedule();
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'can:helpdesk.ai-prompts.view'])
            ->prefix('panel/helpdesk/ai-prompts')
            ->name('helpdesk-ai-prompts.')
            ->group(module_path($this->moduleName, 'routes/quality.php'));
    }

    /**
     * Misma sección ("Asistente IA") del sidebar Helpdesk que la biblioteca:
     * NavService fusiona secciones con el mismo título.
     */
    protected function registerMenu(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('helpdesk', [
            'title' => 'Asistente IA',
            'items' => [
                [
                    'label' => 'Calidad del asistente',
                    'route' => 'helpdesk-ai-prompts.quality.index',
                    'icon' => 'fas fa-chart-line',
                    'permission' => 'helpdesk.ai-prompts.view',
                ],
            ],
        ]);
    }

    protected function registerCommandsAndSchedule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([QualityAlertsCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('ai-prompts:quality-alerts')
                ->hourly()
                ->withoutOverlapping()
                ->onOneServer();
        });
    }
}
