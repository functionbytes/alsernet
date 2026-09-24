<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\Ext\PromosVoucherService;

/**
 * Promociones de la tienda (pieza 34 · ps-vouchers-shop): reglas públicas y
 * vigentes con código, más las automáticas (solo informativas). Es una
 * lectura sin cliente: "Usar" va por la ruta de carrito existente
 * (manager.helpdesk.ps.cart.voucher), que ya exige carts.manage y la
 * propiedad del carrito.
 */
class PromosShopController extends Controller
{
    public function __construct(
        private readonly PromosVoucherService $promos
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->can('helpdeskprestashop.view')) {
            return response()->json(['success' => false], 403);
        }

        try {
            $data = $this->promos->shopPromotions($request->boolean('fresh'));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'No se han podido cargar las promociones de la tienda.'], 503);
        }

        return response()->json(['success' => true] + $data);
    }
}
