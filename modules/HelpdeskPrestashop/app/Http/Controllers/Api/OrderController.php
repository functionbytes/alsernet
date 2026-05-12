<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HelpdeskPrestashop\Http\Requests\OrderDetailRequest;
use Modules\HelpdeskPrestashop\Http\Requests\StartReturnRequest;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class OrderController extends Controller
{
    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    public function detail(OrderDetailRequest $request, int $order): JsonResponse
    {
        $data = $this->service->getOrderDetail($order);

        if ($data === null) {
            return response()->json(['message' => 'Pedido no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function startReturn(StartReturnRequest $request, int $order): JsonResponse
    {
        $data = $this->service->startOrderReturn($order, $request->validated('items'));

        if ($data === null) {
            return response()->json(['message' => 'No se pudo iniciar la devolución.'], 422);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
