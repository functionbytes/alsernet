<?php

namespace Modules\HelpdeskDocument\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Contracts\GdprExportContributor;
use Modules\HelpdeskDocument\Console\Commands\ReclassifyMisfiledAttachments;
use Modules\HelpdeskDocument\Services\Compliance\DocumentGdprExportContributor;
use Nwidart\Modules\Facades\Module;

class HelpdeskDocumentServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskDocument';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), 'helpdeskdocument');
        $this->registerRoutes();

        // Seccion 'documents' (expedientes KYC) del export GDPR (derecho de
        // acceso); no atado al toggle de integracion, igual que la cascada.
        $this->app->tag([DocumentGdprExportContributor::class], GdprExportContributor::TAG);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ReclassifyMisfiledAttachments::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        $path = module_path($this->moduleName, 'routes/managers.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'auth'])
            ->prefix('panel/helpdesk')
            ->group($path);
    }
}
