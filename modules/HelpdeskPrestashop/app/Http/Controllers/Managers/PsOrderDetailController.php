<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Support\Concerns\ScopesCustomerByInbox;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Modules\HelpdeskPrestashop\Support\OrderDetailCache;

/**
 * Endpoint web (cookie auth) para enriquecer el modal de detalle de pedido PS
 * desde el inbox del helpdesk — devuelve el formato raw del bridge.
 */
class PsOrderDetailController extends Controller
{
    use ScopesCustomerByInbox;

    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    public function __invoke(Request $request, int $order): JsonResponse
    {
        if (! $request->user()?->can('helpdeskprestashop.orders.view')) {
            return response()->json(['success' => false], 403);
        }

        // El email es OBLIGATORIO y acota la propiedad del pedido en el bridge:
        // sin él, cualquier agente con helpdeskprestashop.orders.view podía leer
        // el detalle completo de CUALQUIER pedido por id (IDOR). Además se exige
        // que el agente comparta inbox con ese cliente (aislamiento por inbox).
        $customerEmail = trim((string) $request->query('email'));

        if ($customerEmail === '') {
            return response()->json([
                'success' => false,
                'message' => 'Falta el email del cliente para verificar la propiedad del pedido.',
            ], 422);
        }

        $this->assertScopedToCustomerEmail($customerEmail, 'helpdeskprestashop.prospect.view');

        // external_id (opcional): lo manda Contacts 360 cuando el contacto de
        // Helpdesk está vinculado explícitamente a un cliente de PrestaShop
        // cuyo email no coincide con el suyo propio — sin esto, el bridge no
        // podía resolver la propiedad del pedido y el detalle salía vacío.
        $externalId = $request->query('external_id');
        $externalId = ($externalId !== null && $externalId !== '') ? (int) $externalId : null;

        // El email (o el external_id, si vino) forma parte de la clave de
        // caché: si no, el resultado del primero se serviría a otro sobre el
        // mismo order id.
        $cacheKey = $externalId !== null
            ? OrderDetailCache::keyForExternalId($order, $externalId)
            : OrderDetailCache::key($order, $customerEmail);

        $data = Cache::remember($cacheKey, 600, function () use ($order, $customerEmail, $externalId) {
            try {
                $result = $this->service->getOrderDetail($order, $customerEmail ?: null, $externalId);

                return $result ?? 'not_found';
            } catch (PsUpstreamException) {
                return false;
            }
        });

        if ($data === false) {
            Cache::forget($cacheKey);

            return response()->json(['success' => false, 'data' => null], 503);
        }

        if ($data === 'not_found') {
            return response()->json(['success' => false, 'data' => null], 404);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
