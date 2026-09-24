<?php

namespace Modules\HelpdeskErp\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Http\Controllers\Concerns\ScopesErpCustomerAccess;
use Modules\HelpdeskErp\Services\CustomerTimelineService;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatCustomerResolver;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatSections;
use Modules\HelpdeskErp\Services\ErpCross\ErpCrossShopMatcher;

/**
 * Cruce Gestión ↔ tienda y línea de tiempo del cliente — SOLO LECTURA.
 *
 * {customer} es SIEMPRE el id del cliente del helpdesk; el IDCLIENTE de
 * Gestión se resuelve en servidor. Mismo control que ErpChatController:
 * helpdeskerp.view (+ permiso de la ruta) y alcance por bandeja.
 *
 *   GET customers/{customer}/erp/orders/{orderId}/shop
 *       → {state, data: {ps_order_id, reference, matched_by, erp_order_id, erp_number, origin}}
 *   GET customers/{customer}/erp/timeline[?force=1&types=a,b&limit=]
 *       → {state, data: {items, sources, counts, erp_id, truncated}}
 */
class ErpCrossController extends Controller
{
    use ScopesErpCustomerAccess;

    public function __construct(
        private readonly ErpChatCustomerResolver $resolver,
    ) {}

    public function shopOrder(Request $request, int $customer, int $orderId, ErpCrossShopMatcher $matcher): JsonResponse
    {
        $user = $request->user();

        if (! $user?->can(ErpChatSections::PERM_VIEW) || ! $user->can(ErpChatSections::PERM_ORDERS)) {
            return response()->json(['success' => false, 'message' => 'Sin permiso para ver pedidos de Gestión.'], 403);
        }

        // El enlace lleva al workspace de pedido de la tienda: sin permiso
        // para verlo no se revela ni el id ni la referencia.
        if (! $user->can('helpdeskprestashop.orders.view')) {
            return response()->json(['success' => false, 'message' => 'Sin permiso para ver pedidos de la tienda.'], 403);
        }

        $model = $this->customer($customer);
        if ($model instanceof JsonResponse) {
            return $model;
        }

        if (function_exists('helpdesk_erp_enabled') && ! helpdesk_erp_enabled()) {
            return $this->state('unavailable', 'La integración con Gestión está desactivada.', 'disabled');
        }

        $erpId = $this->resolver->erpIdFor($model);
        if ($erpId === null) {
            return response()->json([
                'success' => true,
                'state' => 'unlinked',
                'data' => null,
                'message' => 'Este cliente no está vinculado con Gestión.',
                'lookup_status' => $model->erp_lookup_status,
            ]);
        }

        $result = $matcher->shopOrderFor($model, $erpId, $orderId, $request->boolean('force'));

        if ($result['state'] === 'unavailable' && $result['reason'] === 'not_found') {
            return response()->json([
                'success' => false,
                'state' => 'unavailable',
                'reason' => 'not_found',
                'data' => null,
                'message' => $result['message'],
            ], 404);
        }

        return response()->json(array_merge(['success' => true, 'fetched_at' => now()->toIso8601String()], $result));
    }

    public function timeline(Request $request, int $customer, CustomerTimelineService $timeline): JsonResponse
    {
        $user = $request->user();

        if (! $user?->can(ErpChatSections::PERM_VIEW)) {
            return response()->json(['success' => false, 'message' => 'Sin permiso para ver Gestión.'], 403);
        }

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'force' => ['nullable', 'boolean'],
        ], [
            'limit.integer' => 'El límite tiene que ser un número.',
            'limit.min' => 'El límite mínimo es 1.',
            'limit.max' => 'El límite máximo es 500.',
        ]);

        $model = $this->customer($customer);
        if ($model instanceof JsonResponse) {
            return $model;
        }

        $erpEnabled = ! function_exists('helpdesk_erp_enabled') || helpdesk_erp_enabled();
        $erpId = $erpEnabled ? $this->resolver->erpIdFor($model) : null;

        $data = $timeline->forCustomer(
            $model,
            $erpId,
            fn (string $perm): bool => (bool) $user->can($perm),
            fn (Conversation $conversation): bool => Gate::forUser($user)->allows('view', $conversation),
            $request->boolean('force'),
            (int) ($validated['limit'] ?? 200),
        );

        return response()->json([
            'success' => true,
            'state' => 'ok',
            'message' => null,
            'fetched_at' => now()->toIso8601String(),
            'data' => $data,
        ]);
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    private function customer(int $customerId): Customer|JsonResponse
    {
        $customer = Customer::find($customerId);

        if ($customer === null) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
        }

        // Aborta con 403 si el cliente no está en ninguna bandeja del agente.
        $this->assertErpCustomerAccess($customer);

        return $customer;
    }

    private function state(string $state, string $message, string $reason): JsonResponse
    {
        return response()->json([
            'success' => true,
            'state' => $state,
            'reason' => $reason,
            'data' => null,
            'message' => $message,
        ]);
    }
}
