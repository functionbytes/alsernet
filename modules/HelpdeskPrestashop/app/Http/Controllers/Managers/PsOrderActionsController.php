<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\AddOrderNoteRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\ChangeOrderStatusRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\SendOrderEmailRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\SetOrderAddressRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\SetOrderTrackingRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\StartOrderReturnRequest;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Acciones mutadoras del workspace de pedido PrestaShop (cambiar estado,
 * seguimiento, dirección, devolución, nota, correo) + catálogo de estados.
 *
 * El pedido se ata al {customer} de la ruta: el email que verifica la
 * propiedad en el bridge se resuelve SERVER-SIDE desde $customer->email, no
 * de un campo del body. Antes el email viajaba en el request, así que un
 * agente podía mutar el pedido de cualquier cliente con order_id + email —
 * ahora se exige además el permiso de acceso a ESE cliente (CustomerPolicy).
 */
class PsOrderActionsController extends Controller
{
    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    public function states(Request $request): JsonResponse
    {
        if (! $request->user()?->can('helpdeskprestashop.orders.view')) {
            return response()->json(['success' => false], 403);
        }

        return response()->json(['success' => true, 'states' => $this->service->getOrderStates()]);
    }

    public function documents(Request $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->denyUnlessOwns($request, $customer, 'helpdeskprestashop.orders.view', 'view')) !== null) {
            return $resp;
        }

        try {
            $data = $this->service->getOrderDocuments($order, $customer->email ?: null);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde.'], 503);
        }

        return response()->json(['success' => $data !== null, 'data' => $data]);
    }

    public function setAddress(SetOrderAddressRequest $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer, 'update')) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->setOrderAddress($order, (int) $data['address_id'], $data['type'] ?? 'delivery', $customer->email, (string) Str::uuid());
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó el cambio (pedido/dirección no válidos o sin acceso).'], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo cambiar la dirección.'], 422);
        }

        $this->forgetDetailCache($order, $customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function sendEmail(SendOrderEmailRequest $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer, 'update')) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->sendOrderEmail($order, $data['type'], $customer->email, (string) Str::uuid());
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó el envío (pedido no encontrado o sin acceso).'], 422);
        }

        if ($result === null || empty($result['sent'])) {
            return response()->json(['success' => false, 'message' => 'No se pudo enviar el correo.'], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function startReturn(StartOrderReturnRequest $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer, 'update')) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->startOrderReturn($order, $data['items'], $customer->email, (string) Str::uuid());
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó la devolución (pedido no encontrado o sin acceso).'], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo iniciar la devolución.'], 422);
        }

        $this->forgetDetailCache($order, $customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function addNote(AddOrderNoteRequest $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer, 'update')) !== null) {
            return $resp;
        }

        $data = $request->validated();

        $agent = trim(($request->user()->firstname ?? '').' '.($request->user()->lastname ?? '')) ?: 'Helpdesk';

        try {
            $result = $this->service->addOrderNote($order, $data['note'], $agent, $customer->email, (string) Str::uuid());
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó la nota (pedido no encontrado o sin acceso).'], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo añadir la nota.'], 422);
        }

        $this->forgetDetailCache($order, $customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function changeStatus(ChangeOrderStatusRequest $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer, 'update')) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->changeOrderStatus(
                $order,
                (int) $data['state_id'],
                (bool) ($data['notify'] ?? false),
                $customer->email,
                (string) Str::uuid(),
            );
        } catch (PsUpstreamException) {
            return response()->json([
                'success' => false,
                'message' => 'PrestaShop rechazó el cambio (pedido no encontrado o sin acceso).',
            ], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo cambiar el estado del pedido.'], 422);
        }

        $this->forgetDetailCache($order, $customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function setTracking(SetOrderTrackingRequest $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer, 'update')) !== null) {
            return $resp;
        }

        $data = $request->validated();

        try {
            $result = $this->service->setOrderTracking(
                $order,
                (string) $data['tracking_number'],
                isset($data['carrier_id']) ? (int) $data['carrier_id'] : null,
                $customer->email,
                (string) Str::uuid(),
            );
        } catch (PsUpstreamException) {
            return response()->json([
                'success' => false,
                'message' => 'PrestaShop rechazó el seguimiento (pedido no encontrado o sin acceso).',
            ], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo asignar el seguimiento.'], 422);
        }

        $this->forgetDetailCache($order, $customer->email);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Verifica el permiso PS de la acción Y que el usuario puede acceder a ese
     * cliente (CustomerPolicy), y que el cliente tiene email (necesario para
     * resolver la propiedad del pedido en el bridge). Devuelve una respuesta
     * de error lista para retornar, o null si está autorizado.
     */
    private function denyUnlessOwns(Request $request, Customer $customer, string $psPermission, string $customerAbility): ?JsonResponse
    {
        $user = $request->user();

        if (! $user?->can($psPermission) || ! $user->can($customerAbility, $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (trim((string) $customer->email) === '') {
            return response()->json(['success' => false, 'message' => 'El cliente no tiene correo para verificar la propiedad del pedido.'], 422);
        }

        return null;
    }

    /**
     * Variante para changeStatus/setTracking, cuyo permiso PS ya lo comprueba
     * el Form Request (authorize): aquí solo falta el acceso al cliente.
     */
    private function denyUnlessOwnsCustomer(Request $request, Customer $customer, string $customerAbility): ?JsonResponse
    {
        $user = $request->user();

        if (! $user?->can($customerAbility, $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (trim((string) $customer->email) === '') {
            return response()->json(['success' => false, 'message' => 'El cliente no tiene correo para verificar la propiedad del pedido.'], 422);
        }

        return null;
    }

    /**
     * Invalida el detalle cacheado (misma clave que PsOrderDetailController)
     * para que el panel refleje el cambio inmediatamente.
     */
    private function forgetDetailCache(int $order, string $email): void
    {
        Cache::forget('ps_order_detail:'.$order.':'.md5($email));
    }
}
