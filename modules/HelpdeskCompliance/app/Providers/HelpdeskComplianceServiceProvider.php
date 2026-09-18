<?php

namespace Modules\HelpdeskCompliance\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\HelpdeskCompliance\Console\Commands\CheckStaleComplianceRequestsCommand;
use Modules\HelpdeskCompliance\Listeners\RunComplianceCascade;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;
use Modules\HelpdeskCompliance\Policies\ComplianceRequestPolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskComplianceServiceProvider extends ServiceProvider
{
    protected string $name = 'HelpdeskCompliance';

    protected string $nameLower = 'helpdeskcompliance';

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->name, 'config/config.php'),
            $this->nameLower
        );
    }

    public function boot(): void
    {
        if (Module::find($this->name)?->isDisabled()) {
            return;
        }

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        Event::listen(CustomerGdprDeleted::class, RunComplianceCascade::class);
        Gate::policy(ComplianceRequest::class, ComplianceRequestPolicy::class);

        $this->registerRoutes();
        $this->registerNav();
        $this->registerCommands();
    }

    protected function registerRoutes(): void
    {
        $web = module_path($this->name, 'routes/web.php');

        if (! file_exists($web)) {
            return;
        }

        Route::middleware(['web', 'auth'])
            ->prefix('panel/helpdeskcompliance')
            ->group($web);
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        if (! helpdesk_compliance_enabled()) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk · Cumplimiento',
            'order' => 270,
            'items' => [
                ['label' => 'Solicitudes GDPR', 'route' => 'helpdeskcompliance.requests.index', 'permission' => 'helpdeskcompliance.view'],
            ],
        ]);
    }

    /**
     * Sin gate de helpdesk_compliance_enabled(): la alerta de solicitudes GDPR
     * estancadas es responsabilidad legal, no una feature de integración — ver
     * el docblock de RunComplianceCascade sobre qué gatea el toggle y qué no.
     */
    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([CheckStaleComplianceRequestsCommand::class]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('helpdeskcompliance:check-stale-requests')
                ->hourly()
                ->withoutOverlapping()
                ->onOneServer();
        });
    }
}
