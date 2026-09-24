<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\Ext\ReverExchangesService;

/**
 * Cambios gestionados por REVER: tarjeta del workspace de pedido y sección del
 * tab Devoluciones del panel derecho. Solo lecturas.
 *
 * Mismo modelo de propiedad que OrderdocsController: el pedido se ata al
 * {customer} de la ruta, se exige helpdeskprestashop.orders.view y acceso a
 * ESE cliente (CustomerPolicy::view), y la propiedad la verifica el bridge
 * con el email/external_id resuelto aquí, nunca con datos del navegador.
 */
class ReverExchangesController extends Controller
{
    public function __construct(
        private readonly ReverExchangesService $service
    ) {}

    public function order(Request $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->deny($request, $customer)) !== null) {
            return $resp;
        }

        try {
            $data = $this->service->forOrder($order, $customer->email ?: null, $this->externalId($customer));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'No se pudieron leer los cambios de REVER en PrestaShop (sin conexión).'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado para este cliente.'], 404);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function customer(Request $request, Customer $customer): JsonResponse
    {
        if (($resp = $this->deny($request, $customer)) !== null) {
            return $resp;
        }

        try {
            $data = $this->service->forCustomer($customer->email ?: null, $this->externalId($customer));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'No se pudieron leer los cambios de REVER en PrestaShop (sin conexión).'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado en PrestaShop.'], 404);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** null = autorizado. */
    private function deny(Request $request, Customer $customer): ?JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('helpdeskprestashop.orders.view') || ! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (trim((string) $customer->email) === '' && $this->externalId($customer) === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no tiene correo ni cuenta vinculada para verificar la propiedad del pedido.'], 422);
        }

        return null;
    }

    private function externalId(Customer $customer): ?int
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $externalId !== null ? (int) $externalId : null;
    }
}
