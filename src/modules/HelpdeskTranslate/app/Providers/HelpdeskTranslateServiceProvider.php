<?php

namespace Modules\HelpdeskTranslate\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\HelpdeskTranslate\Console\Commands\PruneTranslationCacheCommand;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class HelpdeskTranslateServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'HelpdeskTranslate';

    protected string $nameLower = 'helpdesktranslate';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->registerPublishableAssets();
        $this->registerSettingsSidebar();
        $this->registerCommands();
        $this->registerSchedule();
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneTranslationCacheCommand::class,
            ]);
        }
    }

    /**
     * Poda diaria de la caché de traducciones para acotar la retención de PII.
     * Sin gating por toggle de integración: la retención GDPR debe ejecutarse
     * aunque la traducción automática esté desactivada.
     */
    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('helpdesktranslate:prune-cache')
                ->daily()
                ->withoutOverlapping();
        });
    }

    /**
     * Override to ensure the namespace is always registered, even when no
     * published lang path exists (the base ModuleServiceProvider omits the
     * namespace argument in that branch).
     */
    protected function registerTranslations(): void
    {
        $publishedPath = resource_path('lang/modules/'.$this->nameLower);

        $langPath = is_dir($publishedPath)
            ? $publishedPath
            : module_path($this->name, 'lang');

        $this->loadTranslationsFrom($langPath, $this->nameLower);
        $this->loadJsonTranslationsFrom($langPath);
    }

    /**
     * Publish the module's public assets (CSS + JS) so they can be served from
     * `public/vendor/helpdesktranslate/` via the `helpdesktranslate-assets` tag.
     */
    protected function registerPublishableAssets(): void
    {
        $this->publishes([
            module_path($this->name, 'resources/css') => public_path('vendor/helpdesktranslate'),
            module_path($this->name, 'resources/js') => public_path('vendor/helpdesktranslate'),
        ], 'helpdesktranslate-assets');
    }

    /**
     * Add a single entry to the helpdesk settings sidebar so admins can find
     * the translation configuration alongside the rest of the helpdesk
     * settings without leaving the panel.
     */
    protected function registerSettingsSidebar(): void
    {
        if (! class_exists(NavService::class)) {
            return;
        }

        NavService::registerSidebar('settings', [
            'title' => __('helpdesktranslate::messages.sidebar.section'),
            'items' => [
                [
                    'label' => __('helpdesktranslate::messages.sidebar.item'),
                    'route' => 'settings.helpdesk-translate.index',
                    'permission' => 'helpdesk-translate.settings.view',
                ],
            ],
        ]);
    }
}
