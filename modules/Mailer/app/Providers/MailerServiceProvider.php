<?php

namespace Modules\Mailer\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Mailer\Models\MailerEndpoint;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerVariable;
use Modules\Mailer\Observers\MailerLayoutObserver;
use Modules\Mailer\Observers\MailerTemplateObserver;
use Modules\Mailer\Observers\MailerVariableObserver;
use Modules\Mailer\Policies\MailerComponentPolicy;
use Modules\Mailer\Policies\MailerEndpointPolicy;
use Modules\Mailer\Policies\MailerSettingsPolicy;
use Modules\Mailer\Policies\MailerTemplatePolicy;
use Modules\Mailer\Policies\MailerVariablePolicy;
use Modules\Theme\Services\NavService;

class MailerServiceProvider extends ServiceProvider
{
    /**
     * Authorization policies for Mailer models
     */
    protected $policies = [
        MailerTemplate::class => MailerTemplatePolicy::class,
        MailerLayout::class => MailerComponentPolicy::class,
        MailerVariable::class => MailerVariablePolicy::class,
        MailerEndpoint::class => MailerEndpointPolicy::class,
    ];

    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mailer.php',
            'mailer'
        );
    }

    public function boot(): void
    {
        // Register authorization policies
        $this->registerPolicies();

        // Register observers for automatic cache clearing
        $this->registerObservers();

        // Load routes with proper prefix and middleware
        $this->registerRoutes();

        // Register navigation menus
        $this->registerMenus();

        // Register authorization gates
        $this->registerGates();

        // Register scheduled tasks
        $this->registerSchedules();

        // Publish config
        $this->publishes([
            __DIR__.'/../../config/mailer.php' => config_path('mailer.php'),
        ], 'mailer-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'mailer');
    }

    /**
     * Register authorization policies for Mailer models
     */
    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Register observers for automatic cache clearing when models change
     */
    protected function registerObservers(): void
    {
        MailerVariable::observe(MailerVariableObserver::class);
        MailerTemplate::observe(MailerTemplateObserver::class);
        MailerLayout::observe(MailerLayoutObserver::class);
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        // Load web routes (settings admin panel)
        require module_path('Mailer', 'routes/web.php');

        // Public API routes (email sending endpoints)
        Route::middleware(['api', 'throttle:60,1'])
            ->prefix('api/email-endpoints')
            ->name('api.email-endpoints.')
            ->group(module_path('Mailer', 'routes/api.php'));
    }

    /**
     * Register navigation menus for the Mailer module
     */
    protected function registerMenus(): void
    {
        // Mini-nav item for Mailers
        NavService::registerMiniItem('mailers', [
            'icon' => 'fa-duotone fa-thin fa-envelopes-bulk',
            'tooltip' => 'Emails',
            'sidebar_id' => 'mailers',
            'order' => 25,
        ]);

        // Sidebar with menu items
        NavService::registerSidebar('mailers', [
            'title' => 'Emails',
            'items' => [
                ['label' => 'Plantillas', 'route' => 'mailers.templates.index'],
                ['label' => 'Componentes', 'route' => 'mailers.components.index'],
                ['label' => 'Variables', 'route' => 'mailers.variables.index'],
                ['label' => 'Puntos de envío', 'route' => 'mailers.endpoints.index'],
            ],
        ]);
    }

    /**
     * Register authorization gates for mailer settings
     */
    protected function registerGates(): void
    {
        $settingsPolicy = new MailerSettingsPolicy;

        // Configure mailer settings gates
        Gate::define('configure-mailers', fn ($user) => $settingsPolicy->configure($user));
        Gate::define('view-mailer-settings', fn ($user) => $settingsPolicy->viewSettings($user));
        Gate::define('manage-mailer-templates', fn ($user) => $settingsPolicy->manageTemplates($user));
        Gate::define('manage-mailer-components', fn ($user) => $settingsPolicy->manageComponents($user));
        Gate::define('manage-mailer-variables', fn ($user) => $settingsPolicy->manageVariables($user));
        Gate::define('manage-mailer-endpoints', fn ($user) => $settingsPolicy->manageEndpoints($user));
    }

    /**
     * Register scheduled tasks for Mailer module
     */
    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Handle bounce and feedback loop - every 30 minutes
            $schedule->command('handler:run')->everyThirtyMinutes();

            // Verify sender - every 5 minutes
            $schedule->command('sender:verify')->everyFiveMinutes();
        });
    }
}
