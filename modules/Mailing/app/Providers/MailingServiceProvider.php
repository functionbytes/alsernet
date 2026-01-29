<?php

namespace Modules\Mailing\Providers;

use App\Helpers\ModuleStatusHelper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
// use Modules\Mailing\Console\Commands\SyncMailingCommand; // TODO: Create this command or remove reference
use Modules\Mailing\Http\ViewComposers\NavigationComposer;
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Models\ImportJob;
use Modules\Mailing\Models\Mailing\MailingLayout;
use Modules\Mailing\Models\Mailing\MailingTemplate;
use Modules\Mailing\Models\Mailing\MailingVariable;
use Modules\Mailing\Models\Subscriber;
use Modules\Mailing\Observers\MailingLayoutObserver;
use Modules\Mailing\Observers\MailingTemplateObserver;
use Modules\Mailing\Observers\MailingVariableObserver;
use Modules\Mailing\Policies\CampaignPolicy;
use Modules\Mailing\Policies\ImportPolicy;
use Modules\Mailing\Policies\SubscriberPolicy;
use Modules\Theme\Services\NavService;

class MailingServiceProvider extends ServiceProvider
{
    /**
     * Module namespace
     */
    protected string $moduleNamespace = 'Modules\Mailing\Http\Controllers';

    /**
     * Module name
     */
    protected string $moduleName = 'Mailing';

    /**
     * Module name (lowercase)
     */
    protected string $moduleNameLower = 'mailing';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mailing.php',
            'mailing'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/email-validator.php',
            'email-validator'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/email-utilities.php',
            'email-utilities'
        );

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }

        // Note: Services are NOT registered as singletons here to prevent boot-time errors
        // when configuration values (like API keys) are not yet available.
        // Laravel will auto-resolve these services when needed with proper configuration.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Only boot module features if Mailing module is enabled
        if (! ModuleStatusHelper::isModuleEnabled('Mailing')) {
            return;
        }

        // Register translations
        $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);

        // Register views
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);

        // Register middleware
        $this->registerMiddleware();

        // Register view composers
        $this->registerViewComposers();

        // Register migrations
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        // Register policies
        $this->registerPolicies();

        // Register gates
        $this->registerGates();

        // Register Blade directives
        $this->registerBladeDirectives();

        // Register scheduled tasks
        $this->registerScheduledTasks();

        // Register menu items with NavService
        $this->registerNavigation();

        // Publish assets
        if ($this->app->runningInConsole()) {
            $this->publishAssets();
        }

        // Register routes after all providers have booted
        $this->booted(function () {
            $this->registerRoutes();
        });
    }

    /**
     * Register module routes
     *
     * Routes are loaded after boot to ensure router is ready
     * Middleware applied within routes/web.php file
     */
    protected function registerRoutes(): void
    {
        // Web routes (operational /mailing and configuration /settings/mailing)
        require module_path($this->moduleName, 'routes/web.php');

        // API routes
        Route::prefix('api')
            ->middleware('api')
            ->name('api.')
            ->group(function () {
                $apiPath = module_path($this->moduleName, 'routes/api.php');
                if (file_exists($apiPath)) {
                    require $apiPath;
                }
            });
    }

    /**
     * Register observers for automatic cache clearing when models change
     */
    protected function registerObservers(): void
    {
        MailingVariable::observe(MailingVariableObserver::class);
        MailingTemplate::observe(MailingTemplateObserver::class);
        MailingLayout::observe(MailingLayoutObserver::class);
    }

    /**
     * Register artisan commands
     */
    protected function registerCommands(): void
    {
        $this->commands([
            // SyncMailingCommand::class, // TODO: Create this command or remove reference
        ]);
    }

    /**
     * Register middleware aliases
     *
     * Middleware migrated from Acelle:
     * - BackendAccess: Admin authorization (from Acelle Backend.php)
     * - CustomerAccess: Customer authorization (from Acelle Frontend.php)
     * - GuestLocale: Guest language preference (from Acelle NotLoggedIn.php)
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // Register middleware aliases for Mailing module
        $router->aliasMiddleware('mailing.backend', \Modules\Mailing\Http\Middleware\BackendAccess::class);
        $router->aliasMiddleware('mailing.customer', \Modules\Mailing\Http\Middleware\CustomerAccess::class);
        $router->aliasMiddleware('mailing.guest.locale', \Modules\Mailing\Http\Middleware\GuestLocale::class);
    }

    /**
     * Register view composers
     */
    protected function registerViewComposers(): void
    {
        view()->composer('*', NavigationComposer::class);
    }

    /**
     * Register authorization policies
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Subscriber::class, SubscriberPolicy::class);
        Gate::policy(ImportJob::class, ImportPolicy::class);
    }

    /**
     * Register authorization gates
     *
     * Uses standard Spatie Permission pattern with Gate::before()
     * for dynamic permission checking
     */
    protected function registerGates(): void
    {
        // Dynamic gate handler for all mailing permissions
        Gate::before(function ($user, $ability) {
            // Super-admin bypass
            if ($user->hasRole('super-admin')) {
                return true;
            }

            // Check if this is a mailing permission
            if (str_starts_with($ability, 'mailing.')) {
                try {
                    return $user->hasPermissionTo($ability);
                } catch (\Exception $e) {
                    // Permission doesn't exist in database yet
                    return false;
                }
            }

            return null; // Let other gates/policies handle it
        });
    }

    /**
     * Register Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // @mailingStatus directive for status badges
        Blade::directive('mailingStatus', function ($expression) {
            return "<?php echo view('mailing::components.status-badge', ['status' => $expression])->render(); ?>";
        });

        // @campaignAnalytics directive
        Blade::directive('campaignAnalytics', function ($expression) {
            return "<?php echo view('mailing::components.campaign-analytics', ['campaign' => $expression])->render(); ?>";
        });
    }

    /**
     * Register scheduled tasks
     */
    protected function registerScheduledTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Sync with Mailrelay every hour
            if (config('mailing.sync.enabled')) {
                $schedule->command('mailing:sync')
                    ->hourly()
                    ->withoutOverlapping()
                    ->runInBackground();
            }

            // Send scheduled campaigns every 15 minutes
            $schedule->command('mailing:send-campaigns')
                ->everyFifteenMinutes()
                ->withoutOverlapping()
                ->runInBackground();
        });
    }

    /**
     * Register navigation menu items
     */
    protected function registerNavigation(): void
    {
        // Integration with NavService for sidebar menu
        if (class_exists('\Modules\Theme\Services\NavService')) {
            // Register Email Marketing items in Settings sidebar
            NavService::registerSidebar('settings', [
                'title' => 'Email Marketing',
                'items' => [
                    [
                        'label' => 'General',
                        'route' => 'settings.mailing.settings.general.show',
                        'icon' => 'fas fa-cog',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'API',
                        'route' => 'settings.mailing.api.index',
                        'icon' => 'fas fa-key',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'URLs',
                        'route' => 'settings.mailing.settings.urls.index',
                        'icon' => 'fas fa-link',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Mailer',
                        'route' => 'settings.mailing.settings.mailer.index',
                        'icon' => 'fas fa-envelope',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Cronjobs',
                        'route' => 'settings.mailing.settings.cronjobs.index',
                        'icon' => 'fas fa-clock',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Servidores de envío',
                        'route' => 'settings.mailing.sending-servers.index',
                        'icon' => 'fas fa-server',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Servidores de verificación',
                        'route' => 'settings.mailing.verification-servers.index',
                        'icon' => 'fas fa-shield-alt',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Sub-cuentas',
                        'route' => 'settings.mailing.sub-accounts.index',
                        'icon' => 'fas fa-user-cog',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Manejadores de rebotes',
                        'route' => 'settings.mailing.bounce-handlers.index',
                        'icon' => 'fas fa-exclamation-triangle',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Manejadores de feedback',
                        'route' => 'settings.mailing.feedback-handlers.index',
                        'icon' => 'fas fa-comment-dots',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Plantillas de email',
                        'route' => 'settings.mailing.templates.email.index',
                        'icon' => 'fas fa-file-alt',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Formularios',
                        'route' => 'settings.mailing.templates.forms.index',
                        'icon' => 'fas fa-wpforms',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Diseños',
                        'route' => 'settings.mailing.templates.layouts.index',
                        'icon' => 'fas fa-th-large',
                        'permission' => 'mailing.access',
                    ],
                    [
                        'label' => 'Idiomas',
                        'route' => 'settings.mailing.templates.languages.index',
                        'icon' => 'fas fa-language',
                        'permission' => 'mailing.access',
                    ],
                ],
            ]);
        }
    }

    /**
     * Publish module assets
     */
    protected function publishAssets(): void
    {
        // Publish configuration
        $this->publishes([
            module_path($this->moduleName, 'config/mailing.php') => config_path('mailing.php'),
            module_path($this->moduleName, 'config/email-validator.php') => config_path('email-validator.php'),
            module_path($this->moduleName, 'config/email-utilities.php') => config_path('email-utilities.php'),
        ], 'mailing-config');

        // Publish migrations
        $this->publishes([
            module_path($this->moduleName, 'database/migrations') => database_path('migrations'),
        ], 'mailing-migrations');

        // Publish views
        $this->publishes([
            module_path($this->moduleName, 'resources/views') => resource_path('views/vendor/mailing'),
        ], 'mailing-views');

        // Publish public assets
        $this->publishes([
            module_path($this->moduleName, 'public') => public_path('modules/mailing'),
        ], 'mailing-assets');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }
}
