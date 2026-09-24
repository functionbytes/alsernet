<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\CatalogCompareRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\CatalogStockAlertRequest;
use Modules\HelpdeskPrestashop\Services\Ext\CatalogService;

/**
 * Extensión "catalog" dentro del modal "Recomendar producto":
 *  - sheet:      stock por ubicación (25), disponibilidad y plazo (16) y
 *                precio del grupo del cliente con tramos (26). Lectura.
 *  - compare:    comparación de 2-3 productos (10). Lectura.
 *  - stockAlert: "Avisar cuando vuelva" (16). Escritura en la tienda con
 *                permiso propio, idempotencia y log de actividad.
 */
class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog
    ) {}

    public function sheet(Request $request, Customer $customer, int $product): JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        $attribute = max(0, (int) $request->query('product_attribute_id', 0));

        try {
            $data = $this->catalog->productSheet($customer, $product, $attribute, $request->boolean('fresh'));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde ahora mismo.'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado en la tienda.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'can' => [
                'stock_alert' => $user->can('helpdeskprestashop.catalog.stock_alert'),
            ],
        ]);
    }

    public function compare(CatalogCompareRequest $request, Customer $customer): JsonResponse
    {
        if (! $request->user()->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        $ids = array_map('intval', $request->validated()['product_ids']);

        try {
            $items = $this->catalog->compare($ids, $customer->language ?: 'es');
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde ahora mismo.'], 503);
        }

        return response()->json([
            'success' => true,
            'items' => $items,
            // Productos pedidos que la tienda no devolvió (inactivos o borrados).
            'missing' => array_values(array_diff($ids, array_map(fn (array $i) => (int) ($i['id'] ?? 0), $items))),
        ]);
    }

    public function stockAlert(CatalogStockAlertRequest $request, Customer $customer, int $product): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('helpdeskprestashop.catalog.stock_alert') || ! $user->can('view', $customer)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para apuntar al cliente al aviso.'], 403);
        }

        if (trim((string) $customer->email) === '' && $customer->externalIdFor('prestashop') === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no está vinculado a PrestaShop.'], 422);
        }

        $data = $request->validated();
        $attribute = (int) ($data['product_attribute_id'] ?? 0);

        // Idempotencia: mismo agente + cliente + producto en el mismo minuto
        // = doble clic. El puente además no duplica un aviso ya existente.
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', '')) ?: sha1(implode(':', [
            'catalog.stock_alert', $user->getAuthIdentifier(), $customer->id, $product, $attribute, now()->format('YmdHi'),
        ]));

        try {
            $result = $this->catalog->subscribeStockAlert($customer, $product, $attribute, $idempotencyKey);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido guardar el aviso ahora mismo.'], 503);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se ha encontrado al cliente en la tienda.'], 404);
        }

        if (! ($result['subscribed'] ?? false)) {
            $error = $result['error'] ?? 'rejected';

            return response()->json([
                'success' => false,
                'error' => $error,
                'message' => match ($error) {
                    'in_stock' => 'El producto ya tiene stock: no hace falta el aviso.',
                    'alerts_disabled' => 'Los avisos de stock están desactivados en la tienda.',
                    'product_unavailable' => 'El producto ya no está activo en la tienda.',
                    'combination_required' => 'Elige primero la combinación (talla, color…) de la que quiere el aviso.',
                    'customer_not_found' => 'No se ha encontrado al cliente en la tienda.',
                    default => 'PrestaShop ha rechazado el aviso.',
                },
            ], $error === 'customer_not_found' ? 404 : 422);
        }

        // Un aviso que ya existía no es una acción nueva del agente.
        if (! ($result['already'] ?? false) && function_exists('activity')) {
            activity('helpdeskprestashop')
                ->causedBy($user)
                ->performedOn($customer)
                ->withProperties([
                    'product_id' => $product,
                    'product_attribute_id' => $attribute,
                    'conversation_id' => $data['conversation_id'] ?? null,
                ])
                ->log('ps.catalog.stock_alert');
        }

        return response()->json(['success' => true, 'data' => $result]);
    }
}
