<?php

namespace Modules\HelpdeskErp\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Nwidart\Modules\Facades\Module;

/**
 * Tienda (PrestaShop) y Gestión (ERP) en la vista de ticket.
 *
 * Escucha "composing: helpdesktickets::managers.tickets.index" (config/ext/
 * tickets.php) y empuja al stack 'scripts' del layout el puente
 * helpdeskerp::tickets.bridge: los modales de Gestión (workspace de pedido,
 * ficha de cliente, pedidos, finanzas, fidelización) y, si el agente puede
 * usar la tienda, los de HelpdeskPrestashop, más erp-tickets.js.
 *
 * Así no hace falta tocar ningún fichero de HelpdeskTickets. Si algún día
 * la vista incluye el puente a mano (@include('helpdeskerp::tickets.bridge')),
 * el @once de la plantilla evita que se pinte dos veces.
 */
class ErpTicketsViewBridge
{
    public function handle(View $view): void
    {
        try {
            $user = auth()->user();
            if (! $user) {
                return;
            }

            $erp = self::erpAvailable($user);
            $ps = self::psAvailable($user);
            if (! $erp && ! $ps) {
                return;
            }

            $factory = $view->getFactory();
            $html = $factory->make('helpdeskerp::tickets.bridge', [
                'ercTktErp' => $erp,
                'ercTktPs' => $ps,
            ])->render();

            if (trim($html) !== '') {
                $factory->startPush('scripts', $html);
            }
        } catch (\Throwable $e) {
            // Un fallo del puente nunca puede tumbar la pantalla de tickets.
            report($e);
        }
    }

    /**
     * Gestión en el ticket: integración activa + helpdeskerp.view.
     */
    public static function erpAvailable(?Authenticatable $user): bool
    {
        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        if (function_exists('helpdesk_erp_enabled') && ! helpdesk_erp_enabled()) {
            return false;
        }

        return $user->can('helpdeskerp.view')
            && view()->exists('helpdeskerp::modals.order-workspace');
    }

    /**
     * Tienda en el ticket: mismo criterio que el puente de Contactos 360
     * (integración activa, módulo encendido y helpdeskprestashop.view), más
     * la ruta que sirve el panel oculto de la tienda para cada cliente.
     */
    public static function psAvailable(?Authenticatable $user): bool
    {
        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        if (function_exists('helpdesk_integration_enabled') && ! helpdesk_integration_enabled()) {
            return false;
        }

        return (bool) Module::find('HelpdeskPrestashop')?->isEnabled()
            && view()->exists('helpdeskprestashop::modals.order-workspace')
            && view()->exists('helpdeskprestashop::inbox-slots.right-panel-prestashop-tabs')
            && Route::has('manager.helpdesk.erp.tickets.host')
            && $user->can('helpdeskprestashop.view');
    }
}
