<?php

namespace Modules\HelpdeskErp\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Listeners\ErpTicketsViewBridge;

/**
 * Tienda y Gestión en la vista de ticket — datos del cliente del ticket.
 *
 * GET /panel/helpdesk/erp/tickets/{ticket}/host
 *
 * La pantalla de tickets es una sola página que cambia de ticket sin
 * recargar, así que el "panel derecho" oculto que leen HDCommerce, PscStore
 * y ErpChat (.bv-right[data-customer-*]) se reconstruye en el navegador para
 * cada cliente. Esto devuelve lo que el navegador no tiene: los datos de
 * contacto del cliente y el HTML del tab oculto de la tienda
 * (helpdeskprestashop::inbox-slots.right-panel-prestashop-tabs), igual que
 * lo monta la ficha de Contactos 360.
 *
 * Autorización: la del propio ticket (TicketPolicy::view). El HTML de la
 * tienda, además, exige helpdeskprestashop.view y CustomerPolicy::view,
 * como el puente de Contactos 360. Solo lectura: no escribe nada.
 */
class ErpTicketsController extends Controller
{
    private const TICKET_MODEL = 'Modules\\HelpdeskTickets\\Models\\Ticket';

    public function host(Request $request, int $ticket): JsonResponse
    {
        $class = self::TICKET_MODEL;
        if (! class_exists($class)) {
            return response()->json(['success' => false, 'message' => 'Los tickets no están disponibles.'], 404);
        }

        $model = $class::query()->with('customer')->find($ticket);
        if ($model === null) {
            return response()->json(['success' => false, 'message' => 'Ticket no encontrado.'], 404);
        }

        Gate::authorize('view', $model);

        $customer = $model->customer;
        if ($customer === null) {
            return response()->json([
                'success' => true,
                'ticket_id' => $model->id,
                'customer' => null,
                'ps' => ['state' => 'nocustomer', 'html' => null],
            ]);
        }

        $user = $request->user();

        return response()->json([
            'success' => true,
            'ticket_id' => $model->id,
            'customer' => [
                'id' => $customer->id,
                'name' => (string) $customer->name,
                'email' => (string) $customer->email,
                'phone' => (string) ($customer->phone ?: $customer->whatsapp_phone),
                'city' => (string) $customer->city,
                'state' => (string) $customer->state,
                'country' => (string) ($customer->country ?: 'ES'),
                'zip' => (string) $customer->postal_code,
                'language' => (string) $customer->language,
                'timezone' => (string) $customer->timezone,
                'update_url' => Route::has('contacts.update') ? route('contacts.update', $customer) : '',
            ],
            'ps' => $this->storePanel($user, $customer),
        ]);
    }

    /**
     * @return array{state: string, html: ?string}
     */
    private function storePanel(mixed $user, mixed $customer): array
    {
        if (! ErpTicketsViewBridge::psAvailable($user)) {
            return ['state' => 'off', 'html' => null];
        }

        if (! $user->can('view', $customer)) {
            return ['state' => 'forbidden', 'html' => null];
        }

        try {
            $html = view('helpdeskprestashop::inbox-slots.right-panel-prestashop-tabs', ['rpCust' => $customer])->render();
        } catch (\Throwable $e) {
            report($e);

            return ['state' => 'error', 'html' => null];
        }

        return ['state' => 'ok', 'html' => $html];
    }
}
