<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Services\Ext\OrderlinkService;

/**
 * Extensión "orderlink": una escritura sobre un pedido hecha desde una
 * conversación liga ese pedido a esa conversación (fuente "action").
 *
 * Se escucha el final de cada petición, igual que la auditoría de opsmap:
 * rutas no GET con respuesta 2xx de manager.helpdesk.ps.* /
 * manager.helpdesk.customers.ps.* que llevan parámetro {order}. La
 * conversación sale del cuerpo (conversation_id) o de la cabecera
 * X-Ps-Conversation (la añade opsmap.js a toda escritura del inbox) y solo
 * vale si es del {customer} de la ruta: sin cliente en la ruta no hay forma
 * de verificarla y no se liga nada.
 */
class OrderlinkRecordStoreAction
{
    public function __construct(
        private readonly OrderlinkService $service
    ) {}

    public function handle(RequestHandled $event): void
    {
        $request = $event->request;

        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $route = $request->route();
        $routeName = $route?->getName();
        if (! is_string($routeName) || ! $this->watched($routeName)) {
            return;
        }

        $status = $event->response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return;
        }

        // 200 con success=false = PrestaShop rechazó el cambio: no hubo acción.
        if ($event->response instanceof JsonResponse && ($event->response->getData(true)['success'] ?? null) === false) {
            return;
        }

        $orderId = $route->parameter('order');
        $orderId = is_numeric($orderId) ? (int) $orderId : 0;
        $customer = $route->parameter('customer');

        if ($orderId <= 0 || ! $customer instanceof Customer || $request->user() === null) {
            return;
        }

        try {
            $conversation = $this->conversation($request, $customer);
            if ($conversation !== null) {
                $this->service->record($conversation, $orderId, null, 'action', $request->user()->id);
            }
        } catch (\Throwable $e) {
            // La acción ya se hizo: un fallo al ligar no puede convertirla en
            // un error para el agente.
            report($e);
        }
    }

    private function watched(string $routeName): bool
    {
        $config = (array) config('helpdeskprestashop.ext.orderlink', []);

        if (! Str::is((array) ($config['action_routes'] ?? []), $routeName)) {
            return false;
        }

        return ! Str::is((array) ($config['action_exclude_routes'] ?? []), $routeName);
    }

    private function conversation(Request $request, Customer $customer): ?Conversation
    {
        // «?:» y no input($key, $default): un conversation_id vacío en el
        // cuerpo existe como clave (null) y no caería a la cabecera.
        $raw = $request->input('conversation_id') ?: $request->header('X-Ps-Conversation');
        $id = is_numeric($raw) ? (int) $raw : 0;

        if ($id <= 0) {
            return null;
        }

        return Conversation::query()
            ->whereKey($id)
            ->where('customer_id', $customer->id)
            ->first();
    }
}
