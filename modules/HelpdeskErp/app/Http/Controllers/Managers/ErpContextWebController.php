<?php

namespace Modules\HelpdeskErp\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpdeskErp\Http\Resources\CustomerContextResource;
use Modules\HelpdeskErp\Services\ErpContextService;

class ErpContextWebController extends Controller
{
    public function __construct(
        private readonly ErpContextService $service,
    ) {}

    public function context(Request $request): JsonResponse
    {
        if (! $request->user()?->can('helpdeskerp.view')) {
            return response()->json(['success' => false], 403);
        }

        $email = trim((string) $request->query('email', ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Email inválido.'], 422);
        }

        $customerId = $request->query('customer_id') ? (int) $request->query('customer_id') : null;
        $data = $this->service->getCustomerContext($email, null, $customerId);

        return response()->json([
            'success' => true,
            'data' => (new CustomerContextResource($data, $email))->toArray($request),
        ]);
    }

    public function orderDetail(Request $request, int $customerId, int $orderId): JsonResponse
    {
        if (! $request->user()?->can('helpdeskerp.view')) {
            return response()->json(['success' => false], 403);
        }

        $detail = $this->service->getOrderDetail($customerId, $orderId);

        if ($detail === null) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $detail]);
    }
}
