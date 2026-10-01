<?php

namespace Modules\HelpdeskMedia\Providers;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;

/**
 * UI de medios: endpoints de lectura y scripts inyectados en la bandeja y en
 * los tickets mediante view composers, sin editar las vistas de Helpdesk ni
 * de HelpdeskTickets.
 */
class MediaUiServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HelpdeskMedia';

    protected string $moduleNameLower = 'helpdeskmedia';

    private const INBOX_VIEW = 'helpdesk::helpdesk.inbox.partials.thread';

    private const TICKETS_VIEW = 'helpdesktickets::managers.tickets.index';

    public function boot(): void
    {
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);
        $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
        $this->registerRoutes();
        $this->registerAssetInjection();
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('panel/helpdesk/media')
            ->name('helpdesk-media.')
            ->group(module_path($this->moduleName, 'routes/media-ui.php'));
    }

    /**
     * El stack `hd-thread-scripts` solo se imprime en la carga completa de la
     * bandeja; el JS se engancha solo al DOM (MutationObserver) y es
     * idempotente. En tickets se empuja al stack `scripts` del layout.
     */
    protected function registerAssetInjection(): void
    {
        View::composer(self::INBOX_VIEW, fn (ViewContract $view) => $this->push($view, 'hd-thread-scripts', 'inbox'));
        View::composer(self::TICKETS_VIEW, fn (ViewContract $view) => $this->push($view, 'scripts', 'tickets'));
    }

    private function push(ViewContract $view, string $stack, string $target): void
    {
        $user = auth()->user();

        if (! $user || ! Route::has('helpdesk-media.conversation')) {
            return;
        }

        $view->getFactory()->startPush(
            $stack,
            view('helpdeskmedia::partials.assets', ['target' => $target])->render()
        );
    }
}
