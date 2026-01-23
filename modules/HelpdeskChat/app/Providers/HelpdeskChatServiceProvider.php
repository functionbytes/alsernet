<?php

namespace Modules\HelpdeskChat\Providers;

use App\Helpers\ModuleStatusHelper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HelpdeskChat\Http\ViewComposers\NavigationComposer;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;
use Modules\HelpdeskChat\Models\Webhook;
use Modules\HelpdeskChat\Observers\ConversationObserver;
use Modules\HelpdeskChat\Observers\MessageObserver;
use Modules\HelpdeskChat\Policies\WebhookPolicy;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class HelpdeskChatServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'HelpdeskChat';

    protected string $nameLower = 'helpdeskchat';

    /**
     * Register the service provider
     */
    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/helpdeskchat.php',
            'helpdeskchat'
        );

        // Register child service providers
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // Register policies early
        $this->registerPolicies();
    }

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        // ✅ CRITICAL: Only boot module features if HelpdeskChat module is enabled
        if (! ModuleStatusHelper::isModuleEnabled('HelpdeskChat')) {
            return;
        }

        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerViewComposers();
        $this->registerGates();
        $this->registerMenus();
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->registerRoutes();

        // Register model observers
        Conversation::observe(ConversationObserver::class);
        ConversationMessage::observe(MessageObserver::class);
    }

    /**
     * Register authorization policies
     */
    protected function registerPolicies(): void
    {
        if (class_exists(Webhook::class)) {
            Gate::policy(
                Webhook::class,
                WebhookPolicy::class
            );
        }
    }

    /**
     * Register gates for helpdesk authorization
     */
    protected function registerGates(): void
    {
        // Conversation gates
        Gate::define('view-conversations', fn ($user) => $user->can('helpdesk.conversations.view'));
        Gate::define('create-conversation', fn ($user) => $user->can('helpdesk.conversations.create'));
        Gate::define('update-conversation', fn ($user) => $user->can('helpdesk.conversations.update'));
        Gate::define('delete-conversation', fn ($user) => $user->can('helpdesk.conversations.delete'));

        // Contact gates
        Gate::define('view-contacts', fn ($user) => $user->can('helpdesk.contacts.view'));
        Gate::define('create-contact', fn ($user) => $user->can('helpdesk.contacts.create'));
        Gate::define('update-contact', fn ($user) => $user->can('helpdesk.contacts.update'));
        Gate::define('delete-contact', fn ($user) => $user->can('helpdesk.contacts.delete'));

        // Team gates
        Gate::define('view-teams', fn ($user) => $user->can('helpdesk.teams.view'));
        Gate::define('create-team', fn ($user) => $user->can('helpdesk.teams.create'));
        Gate::define('update-team', fn ($user) => $user->can('helpdesk.teams.update'));
        Gate::define('delete-team', fn ($user) => $user->can('helpdesk.teams.delete'));

        // Labels gates
        Gate::define('view-labels', fn ($user) => $user->can('helpdesk.labels.view'));
        Gate::define('create-label', fn ($user) => $user->can('helpdesk.labels.create'));
        Gate::define('update-label', fn ($user) => $user->can('helpdesk.labels.update'));
        Gate::define('delete-label', fn ($user) => $user->can('helpdesk.labels.delete'));

        // Channel gates
        Gate::define('view-channels', fn ($user) => $user->can('helpdesk.channels.view'));
        Gate::define('manage-channels', fn ($user) => $user->can('helpdesk.channels.manage'));

        // Template gates
        Gate::define('view-templates', fn ($user) => $user->can('helpdesk.templates.view'));
        Gate::define('create-template', fn ($user) => $user->can('helpdesk.templates.create'));
        Gate::define('update-template', fn ($user) => $user->can('helpdesk.templates.update'));
        Gate::define('delete-template', fn ($user) => $user->can('helpdesk.templates.delete'));

        // Automation gates
        Gate::define('view-automations', fn ($user) => $user->can('helpdesk.automations.view'));
        Gate::define('create-automation', fn ($user) => $user->can('helpdesk.automations.create'));
        Gate::define('update-automation', fn ($user) => $user->can('helpdesk.automations.update'));
        Gate::define('delete-automation', fn ($user) => $user->can('helpdesk.automations.delete'));

        // Settings gates
        Gate::define('configure-helpdesk', fn ($user) => $user->can('helpdesk.settings.configure'));
        Gate::define('view-helpdesk-settings', fn ($user) => $user->can('helpdesk.settings.view'));

        // Webhook gates
        Gate::define('manage-webhooks', fn ($user) => $user->can('helpdesk.webhooks.manage'));
        Gate::define('view-webhooks', fn ($user) => $user->can('helpdesk.webhooks.view'));

        // Reports gates
        Gate::define('view-helpdesk-reports', fn ($user) => $user->can('helpdesk.reports.view'));
        Gate::define('export-helpdesk-reports', fn ($user) => $user->can('helpdesk.reports.export'));

        // Audit gates
        Gate::define('view-helpdesk-audits', fn ($user) => $user->can('helpdesk.audits.view'));

        // Dynamic permission gate - checks if user has permission in helpdesk_permissions table
        Gate::before(function ($user, $ability) {
            // Super-admin bypass
            if ($user->hasRole('super-admin')) {
                return true;
            }

            // Check if this ability exists in helpdesk permissions
            if (class_exists('Modules\HelpdeskChat\Models\Permission')) {
                $permission = \Modules\HelpdeskChat\Models\Permission::where('name', $ability)->first();

                if ($permission) {
                    // Check if user has this permission through roles
                    return $user->permissions()->where('name', $ability)->exists();
                }
            }

            return null; // Let other gates/policies handle it
        });
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        // Web routes for admin panel
        Route::middleware('web')
            ->group(function () {
                require __DIR__.'/../../routes/web.php';
            });

        // Admin routes
        Route::middleware(['web', 'auth', 'admin'])
            ->prefix('admin/helpdesk')
            ->name('admin.helpdesk.')
            ->group(function () {
                require __DIR__.'/../../routes/admin.php';
            });

        // Callcenter routes
        Route::middleware(['web', 'auth', 'callcenter'])
            ->prefix('callcenter')
            ->name('callcenter.')
            ->group(function () {
                require __DIR__.'/../../routes/callcenter.php';
            });

        // Customer routes
        Route::middleware(['web', 'auth', 'customer'])
            ->prefix('customer/support')
            ->name('customer.support.')
            ->group(function () {
                require __DIR__.'/../../routes/customer.php';
            });

        // API routes
        Route::middleware(['api', 'throttle:60,1'])
            ->prefix('api/helpdesk')
            ->name('api.helpdesk.')
            ->group(function () {
                require __DIR__.'/../../routes/api.php';
            });
    }

    /**
     * Register view composers
     */
    protected function registerViewComposers(): void
    {
        // Register navigation composer for theme layout
        view()->composer(
            'theme.components.nav',
            NavigationComposer::class
        );
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\HelpdeskChat\Console\Commands\GenerateAgentPerformanceSnapshots::class,
            \Modules\HelpdeskChat\Console\Commands\FetchEmailsCommand::class,
            \Modules\HelpdeskChat\Console\Commands\RunAutomationScheduleds::class,
            \Modules\HelpdeskChat\Console\Commands\CheckSlaBreaches::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Fetch emails every 5 minutes
            $schedule->command('chat:fetch-emails')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            // Check SLA breaches every 10 minutes
            $schedule->command('chat:check-sla-breaches')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->runInBackground();

            // Run automation schedules every 1 minute
            $schedule->command('chat:run-automation-scheduled')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground();

            // Generate agent performance snapshots every 15 minutes
            $schedule->command('chat:generate-agent-performance-snapshots')
                ->everyFifteenMinutes()
                ->withoutOverlapping()
                ->runInBackground();
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            // Use relative path from this file location
            $moduleLangPath = __DIR__.'/../../lang';
            if (is_dir($moduleLangPath)) {
                $this->loadTranslationsFrom($moduleLangPath, $this->nameLower);
                $this->loadJsonTranslationsFrom($moduleLangPath);
            }
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = __DIR__.'/../../config';

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower.'.'.str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = __DIR__.'/../../resources/views';

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Register menus using NavService
     */
    protected function registerMenus(): void
    {
        // Mini-nav item para Helpdesk (operaciones)
        NavService::registerMiniItem('helpdesk', [
            'icon' => 'fa-headset',
            'tooltip' => 'Helpdesk',
            'sidebar_id' => 'helpdesk',
            'order' => 30,
        ]);

        // Sidebar local - Helpdesk (operaciones)
        NavService::registerSidebar('helpdesk', [
            'title' => 'Helpdesk',
            'items' => [
                ['label' => 'Conversaciones', 'route' => 'admin.helpdesk.conversation.index', 'permission' => 'view-conversations'],
                ['label' => 'Mis conversaciones', 'route' => 'admin.helpdesk.conversation.mine', 'permission' => 'view-conversations'],
                ['label' => 'Sin asignar', 'route' => 'admin.helpdesk.conversation.unassigned', 'permission' => 'view-conversations'],
                ['label' => 'Contactos', 'route' => 'admin.helpdesk.contacts.index', 'permission' => 'view-contacts'],
                ['label' => 'Clientes', 'route' => 'admin.helpdesk.customers.index', 'permission' => 'view-conversations'],
                ['label' => 'Equipos', 'route' => 'admin.helpdesk.teams.index', 'permission' => 'view-teams'],
                ['label' => 'Etiquetas', 'route' => 'admin.helpdesk.labels.index', 'permission' => 'view-labels'],
            ],
        ]);

        // Mini-nav item para Canales
        NavService::registerMiniItem('channels', [
            'icon' => 'fa-plug',
            'tooltip' => 'Canales',
            'sidebar_id' => 'channels',
            'order' => 40,
        ]);

        // Sidebar local - Canales de comunicación
        NavService::registerSidebar('channels', [
            'title' => 'Canales',
            'items' => [
                ['label' => 'Widgets Web', 'route' => 'admin.helpdesk.channels.webs.index', 'permission' => 'view-channels'],
                ['label' => 'Email', 'route' => 'admin.helpdesk.channels.emails.index', 'permission' => 'view-channels'],
                ['label' => 'Facebook', 'route' => 'admin.helpdesk.channels.facebook-pages.index', 'permission' => 'view-channels'],
                ['label' => 'Instagram', 'route' => 'admin.helpdesk.channels.instagrams.index', 'permission' => 'view-channels'],
                ['label' => 'WhatsApp', 'route' => 'admin.helpdesk.channels.whatsapps.index', 'permission' => 'view-channels'],
                ['label' => 'SMS', 'route' => 'admin.helpdesk.channels.sms.index', 'permission' => 'view-channels'],
                ['label' => 'API', 'route' => 'admin.helpdesk.channels.api.index', 'permission' => 'view-channels'],
            ],
        ]);

        // Agregar configuraciones de helpdesk al sidebar genérico 'settings'
        NavService::registerSidebar('settings', [
            'title' => 'Helpdesk',
            'items' => [
                ['label' => 'Configuración general', 'route' => 'admin.helpdesk.settings.index'],
                ['label' => 'Respuestas enlatadas', 'route' => 'admin.helpdesk.canneds.index', 'permission' => 'view-templates'],
                ['label' => 'Automatizaciones', 'route' => 'admin.helpdesk.automation-rules.index', 'permission' => 'view-automations'],
                ['label' => 'Macros', 'route' => 'admin.helpdesk.macros.index', 'permission' => 'view-templates'],
                ['label' => 'Plantillas de mensaje', 'route' => 'admin.helpdesk.message-templates.index', 'permission' => 'view-templates'],
                ['label' => 'Plantillas de email', 'route' => 'admin.helpdesk.email-templates.index', 'permission' => 'view-templates'],
                ['label' => 'Horarios de negocio', 'route' => 'admin.helpdesk.settings.hours.index'],
                ['label' => 'Notificaciones', 'route' => 'admin.helpdesk.settings.notifications.index'],
                ['label' => 'Atributos personalizados', 'route' => 'admin.helpdesk.custom-attributes.index'],
                ['label' => 'Roles de equipo', 'route' => 'admin.helpdesk.team-roles.index'],
                ['label' => 'Políticas SLA', 'route' => 'admin.helpdesk.sla-policies.index'],
                ['label' => 'Webhooks', 'route' => 'admin.helpdesk.webhooks.index', 'permission' => 'manage-webhooks'],
                ['label' => 'Integraciones', 'route' => 'admin.helpdesk.integrations.index'],
                ['label' => 'Registros de auditoría', 'route' => 'admin.helpdesk.audits.index', 'permission' => 'view-helpdesk-audits'],
            ],
        ]);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
