<?php

namespace Modules\MailsSettings\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Setting;
use Modules\Theme\Services\NavService;

class MailsSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mails-settings.php',
            'mails-backups'
        );
    }

    public function boot(): void
    {
        // Register routes
        $this->registerRoutes();

        // Register navigation
        $this->registerMenus();

        // Load dynamic mail configuration
        $this->loadDynamicMailConfig();

        // Publish config
        $this->publishes([
            __DIR__.'/../../config/mails-settings.php' => config_path('mails-backups.php'),
        ], 'mails-backups-config');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'mails-backups');
    }

    protected function registerRoutes(): void
    {
        $modulePath = dirname(__DIR__, 2);

        // Load routes directly without wrapping (routes already have their own groups)
        require $modulePath.'/routes/web.php';
    }

    protected function registerMenus(): void
    {

        NavService::registerSidebar('settings', [
            'title' => 'Configuración de Email',
            'items' => [
                ['label' => 'Configuración general', 'route' => 'settings.email.index'],
                ['label' => 'Email entrante', 'route' => 'settings.incoming-email.index'],
                ['label' => 'Email saliente', 'route' => 'settings.outgoing-email.index'],
            ],
        ]);
    }

    /**
     * Load dynamic mail configuration from settings
     */
    private function loadDynamicMailConfig(): void
    {
        // Only load if database is available
        if ($this->app->runningInConsole() && ! $this->isDatabaseReady()) {
            return;
        }

        try {
            $this->loadMailConfig();
        } catch (\Exception $e) {
            // Mail config not available yet - silently fail
        }
    }

    /**
     * Check if database is ready and configured
     */
    private function isDatabaseReady(): bool
    {
        try {
            \DB::connection()->getPDO();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Load mail configuration from database settings
     */
    private function loadMailConfig(): void
    {
        try {
            $mailSettings = Setting::getEmailSettings();

            config([
                'mail.default' => $mailSettings['mail_mailer'],
                'mail.mailers.smtp' => [
                    'transport' => 'smtp',
                    'host' => $mailSettings['mail_host'],
                    'port' => (int) $mailSettings['mail_port'],
                    'encryption' => $mailSettings['mail_encryption'],
                    'username' => $mailSettings['mail_username'],
                    'password' => $mailSettings['mail_password'],
                    'timeout' => null,
                    'local_domain' => env('MAIL_LOCAL_DOMAIN'),
                ],
                'mail.from' => [
                    'address' => $mailSettings['mail_from_address'],
                    'name' => $mailSettings['mail_from_name'],
                ],
            ]);
        } catch (\Exception $e) {
            // Mail config not available yet
        }
    }
}
