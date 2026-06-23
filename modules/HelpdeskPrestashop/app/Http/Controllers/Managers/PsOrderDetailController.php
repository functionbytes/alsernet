<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Endpoint web (cookie auth) para enriquecer el modal de detalle de pedido PS
 * desde el inbox del helpdesk — devuelve el formato raw del bridge.
 */
class PsOrderDetailController extends Controller
{
    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    public function __invoke(Request $request, int $order): JsonResponse
    {
        if (! $request->user()?->can('helpdeskprestashop.orders.view')) {
            return response()->json(['success' => false], 403);
        }

        // The email scopes ownership validation in getOrderDetail(), so it MUST be
        // part of the cache key — otherwise the first email's result is served to
        // any other email querying the same order id (data leak / cache poisoning).
        $customerEmail = $request->query('email');
        $cacheKey = 'ps_order_detail:'.$order.':'.md5((string) $customerEmail);

        $data = Cache::remember($cacheKey, 600, function () use ($order, $customerEmail) {
            try {
                $result = $this->service->getOrderDetail($order, $customerEmail ?: null);

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
