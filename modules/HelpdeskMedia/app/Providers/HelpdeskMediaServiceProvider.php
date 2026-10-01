<?php

namespace Modules\HelpdeskMedia\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskMedia\Console\Commands\ReprocessMediaCommand;
use Modules\HelpdeskMedia\Observers\ConversationItemMediaObserver;
use Modules\HelpdeskMedia\Observers\TicketAttachmentMediaObserver;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Nwidart\Modules\Facades\Module;

class HelpdeskMediaServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskMedia';

    protected string $moduleNameLower = 'helpdeskmedia';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerObservers();
        $this->commands([ReprocessMediaCommand::class]);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'config/config.php');

        $this->publishes([$configPath => config_path($this->moduleNameLower.'.php')], 'config');
        $this->mergeConfigFrom($configPath, $this->moduleNameLower);
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->moduleName, 'routes/web.php'));
    }

    protected function registerObservers(): void
    {
        if (class_exists(ConversationItem::class)) {
            ConversationItem::observe(ConversationItemMediaObserver::class);
        }

        if (class_exists(TicketAttachment::class) && Module::isEnabled('HelpdeskTickets')) {
            TicketAttachment::observe(TicketAttachmentMediaObserver::class);
        }
    }

    public function provides(): array
    {
        return [];
    }
}
