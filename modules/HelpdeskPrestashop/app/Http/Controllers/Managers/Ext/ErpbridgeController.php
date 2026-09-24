<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\Ext\ErpbridgeService;

/**
 * Tarjeta "En Gestión (ERP)" del workspace de pedido (extensión "erpbridge").
 *
 * Mismo modelo de propiedad que OrderdocsController: el pedido se ata al
 * {customer} de la ruta, se exige acceso a ESE cliente (CustomerPolicy) y la
 * propiedad del pedido la verifica el bridge con el email/external_id
 * resuelto aquí. El salto a Gestión lo acota ErpbridgeService al cliente de
 * Gestión de ese mismo cliente. Solo lectura.
 */
class ErpbridgeController extends Controller
{
    public function __construct(
        private readonly ErpbridgeService $service
    ) {}

    public function show(Request $request, Customer $customer, int $order): JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('helpdeskprestashop.orders.view')
            || ! $user->can('helpdeskprestashop.orders.erp')
            || ! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (trim((string) $customer->email) === '' && $customer->externalIdFor('prestashop') === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no tiene correo ni cuenta vinculada para verificar la propiedad del pedido.'], 422);
        }

        try {
            $data = $this->service->forOrder($customer, $order);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'No se pudo comprobar el pedido en PrestaShop (sin conexión o pedido de otro cliente).'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado para este cliente.'], 404);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
