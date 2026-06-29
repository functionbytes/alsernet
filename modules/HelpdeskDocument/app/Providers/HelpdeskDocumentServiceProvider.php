<?php

namespace Modules\HelpdeskDocument\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
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
