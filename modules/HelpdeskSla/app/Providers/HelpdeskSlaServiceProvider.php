<?php

namespace Modules\HelpdeskSla\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskSla\Console\Commands\CheckConversationSlaBreaches;
use Modules\HelpdeskSla\Console\Commands\PruneResolvedSlaBreaches;
use Modules\HelpdeskSla\Console\Commands\SendConversationSlaWarnings;
use Modules\HelpdeskSla\Listeners\MarkConversationFirstResponse;
use Modules\HelpdeskSla\Models\ConversationSlaBreach;
use Modules\HelpdeskSla\Observers\ConversationSlaObserver;
use Modules\HelpdeskSla\Policies\ConversationSlaBreachPolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class HelpdeskSlaServiceProvider extends ServiceProvider
{
    protected string $name = 'HelpdeskSla';

    protected string $nameLower = 'helpdesksla';

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

        Conversation::observe(ConversationSlaObserver::class);
        Event::listen(ConversationMessageCreated::class, MarkConversationFirstResponse::class);
        Gate::policy(ConversationSlaBreach::class, ConversationSlaBreachPolicy::class);

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
            ->prefix('panel/helpdesksla')
            ->group($web);
    }

    protected function registerNav(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        if (! helpdesk_sla_enabled()) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk — SLA',
            'items' => [
                ['label' => 'Incumplimientos SLA', 'route' => 'helpdesksla.breaches.index', 'permission' => 'helpdesksla.view'],
                ['label' => 'Festivos', 'route' => 'helpdesksla.holidays.index', 'permission' => 'helpdesksla.view'],
            ],
        ]);
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CheckConversationSlaBreaches::class,
            SendConversationSlaWarnings::class,
            PruneResolvedSlaBreaches::class,
        ]);

        $checkInterval = (int) config('helpdesksla.check_interval_minutes', 5);
        $warningInterval = (int) config('helpdesksla.warning_interval_minutes', 15);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) use ($checkInterval, $warningInterval): void {
            // withoutOverlapping()+onOneServer(): ambos iteran conversaciones
            // abiertas y en multi-servidor duplicarían alertas/registros.
            // Gate ->when(helpdesk_sla_enabled()) alineado en los tres comandos:
            // el propio comando también lo comprueba, pero así el scheduler ni
            // siquiera lo lanza cuando la integración está apagada.
            $check = $schedule->command('helpdesksla:check-breaches')
                ->withoutOverlapping()
                ->onOneServer()
                ->when(fn (): bool => helpdesk_sla_enabled());
            $checkInterval <= 1 ? $check->everyMinute() : $check->cron("*/{$checkInterval} * * * *");

            $warn = $schedule->command('helpdesksla:send-warnings')
                ->withoutOverlapping()
                ->onOneServer()
                ->when(fn (): bool => helpdesk_sla_enabled());
            $warningInterval <= 1 ? $warn->everyMinute() : $warn->cron("*/{$warningInterval} * * * *");

            $schedule->command('helpdesksla:prune-breaches')
                ->daily()
                ->at('03:40')
                ->withoutOverlapping()
                ->onOneServer()
                ->when(fn (): bool => helpdesk_sla_enabled());
        });
    }
}
