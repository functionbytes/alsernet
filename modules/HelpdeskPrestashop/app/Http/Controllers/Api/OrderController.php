<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Support\Concerns\ScopesCustomerByInbox;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\OrderDetailRequest;
use Modules\HelpdeskPrestashop\Http\Requests\StartReturnRequest;
use Modules\HelpdeskPrestashop\Http\Resources\OrderDetailResource;
use Modules\HelpdeskPrestashop\Http\Resources\OrderReturnResource;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class OrderController extends Controller
{
    use ScopesCustomerByInbox;

    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    public function detail(OrderDetailRequest $request, int $order): JsonResponse
    {
        $email = $request->validated('customer_email');

        // Aislamiento de bandeja: si el email es de un cliente local del helpdesk,
        // el agente debe compartir bandeja con él. Sin esto, el permiso
        // helpdeskprestashop.orders.view bastaba para leer el pedido de cualquier
        // cliente aportando su email + iterando {order} (mismo guard que el
        // Managers PsOrderDetailController y CustomerContextController).
        $this->assertScopedToCustomerEmail($email);

        try {
            $data = $this->service->getOrderDetail($order, $email);
        } catch (PsUpstreamException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El servicio PrestaShop no está disponible temporalmente.',
                'data' => null,
            ], 503);
        }

        if ($data === null) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => new OrderDetailResource($data),
        ]);
    }

    public function startReturn(StartReturnRequest $request, int $order): JsonResponse
    {
        $items = $request->validated('items');
        $email = $request->validated('customer_email');

        $this->assertScopedToCustomerEmail($email);

        try {
            $data = $this->service->startOrderReturn(
                $order,
                $items,
                $email,
                $this->idempotencyKey($request, $order, $items),
            );
        } catch (PsUpstreamException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El servicio PrestaShop no está disponible temporalmente.',
                'data' => null,
            ], 503);
        }

        if ($data === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar la devolución.',
                'data' => null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Devolución iniciada.',
            'data' => new OrderReturnResource($data),
        ]);
    }

    /**
     * Build a stable idempotency key for the start-return action.
     * Prefers the client-supplied header; falls back to a deterministic hash
     * of user + order + items so accidental double-submits don't duplicate
     * the return on PrestaShop.
     */
    private function idempotencyKey(StartReturnRequest $request, int $order, array $items): string
    {
        $header = trim((string) $request->header('Idempotency-Key', ''));

        if ($header !== '') {
            return $header;
        }

        return sha1(sprintf(
            '%s:%d:%s',
            (string) ($request->user()?->getAuthIdentifier() ?? 'anon'),
            $order,
            json_encode($items),
        ));
    }
}
