<?php

namespace Modules\HelpdeskErp\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Support\Concerns\ScopesCustomerByInbox;
use Modules\HelpdeskErp\Http\Requests\ErpChatSectionRequest;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatCustomerResolver;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatOverview;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatSections;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatService;

/**
 * Gestión (ERP) dentro del chat — SOLO LECTURA.
 *
 * Todas las rutas reciben el id del CLIENTE DEL HELPDESK; el IDCLIENTE de
 * Gestión se resuelve aquí (ErpChatCustomerResolver) y nunca viaja desde el
 * navegador. Cada petición exige helpdeskerp.view + el permiso de la sección
 * y que el cliente esté en alguna bandeja del agente (ScopesCustomerByInbox,
 * el mismo aislamiento que ErpContextWebController).
 *
 * Respuesta: {success: true, state, data, message, ...}. Un cliente sin
 * vínculo con Gestión responde 200 con state 'unlinked'.
 */
class ErpChatController extends Controller
{
    use ScopesCustomerByInbox;

    public function __construct(
        private readonly ErpChatService $service,
        private readonly ErpChatOverview $overview,
        private readonly ErpChatCustomerResolver $resolver,
    ) {}

    public function overview(Request $request, int $customer): JsonResponse
    {
        $resolved = $this->resolve($request, $customer, []);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$model, $erpId] = $resolved;
        $user = $request->user();

        $data = $this->overview->build($model, $erpId, fn (string $perm): bool => (bool) $user?->can($perm), $request->boolean('force'));

        return response()->json([
            'success' => true,
            'state' => $data['state'],
            'data' => $data,
            'message' => $data['sections']['summary']['message'] ?? null,
            'fetched_at' => $data['fetched_at'],
        ]);
    }

    public function section(ErpChatSectionRequest $request, int $customer, string $section): JsonResponse
    {
        if (! ErpChatSections::exists($section)) {
            return response()->json(['success' => false, 'message' => 'Sección desconocida.'], 404);
        }

        $resolved = $this->resolve($request, $customer, ErpChatSections::permsFor($section));
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [, $erpId] = $resolved;

        $result = $this->service->section($erpId, $section, $request->filters(), $request->fresh());
        $user = $request->user();
        $result = ErpChatSections::redactFor($section, $result, fn (string $perm): bool => (bool) $user?->can($perm));

        return $this->respond($result, ['section' => $section]);
    }

    public function order(Request $request, int $customer, int $orderId): JsonResponse
    {
        $resolved = $this->resolve($request, $customer, ErpChatSections::DETAIL_PERMS['order']);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [, $erpId] = $resolved;

        $bundle = $this->service->orderBundle($erpId, $orderId, $request->boolean('force'));
        $detail = $bundle['detail'];

        if ($detail['state'] === 'unavailable') {
            return $this->notFound($detail, 'Pedido no encontrado para este cliente.');
        }

        $shipping = $bundle['shipping'];
        $deliveryNotes = ($shipping['state'] === 'ok' && is_array($shipping['data']['delivery_notes'] ?? null))
            ? array_values($shipping['data']['delivery_notes'])
            : [];

        return response()->json([
            'success' => true,
            'state' => $detail['state'],
            'message' => $detail['message'],
            'reason' => $detail['reason'],
            'fetched_at' => $detail['fetched_at'],
            'data' => [
                'order' => $detail['data'],
                'history' => $bundle['history'],
                'shipping' => $shipping,
                'delivery_notes' => $deliveryNotes,
            ],
        ]);
    }

    public function deliveryNote(Request $request, int $customer, int $deliveryId): JsonResponse
    {
        $resolved = $this->resolve($request, $customer, ErpChatSections::DETAIL_PERMS['delivery-note']);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [, $erpId] = $resolved;

        $result = $this->service->deliveryNoteDetail($erpId, $deliveryId, $request->boolean('force'));

        if ($result['state'] === 'unavailable') {
            return $this->notFound($result, 'Albarán no encontrado para este cliente.');
        }

        return $this->respond($result);
    }

    public function invoice(Request $request, int $customer, int $invoiceId): JsonResponse
    {
        $resolved = $this->resolve($request, $customer, ErpChatSections::DETAIL_PERMS['invoice']);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [, $erpId] = $resolved;

        $result = $this->service->invoiceDetail($erpId, $invoiceId, $request->boolean('force'));

        if ($result['state'] === 'unavailable') {
            return $this->notFound($result, 'Factura no encontrada para este cliente.');
        }

        return $this->respond($result);
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    /**
     * Permisos → cliente → alcance por bandeja → integración activa → id ERP.
     *
     * @param  list<string>  $anyPerms  basta con uno (además de helpdeskerp.view)
     * @return array{0: Customer, 1: int}|JsonResponse
     */
    private function resolve(Request $request, int $customerId, array $anyPerms): array|JsonResponse
    {
        $user = $request->user();

        if (! $user?->can(ErpChatSections::PERM_VIEW)) {
            return response()->json(['success' => false, 'message' => 'Sin permiso para ver Gestión.'], 403);
        }

        if ($anyPerms !== [] && ! collect($anyPerms)->contains(fn (string $p): bool => $user->can($p))) {
            return response()->json(['success' => false, 'message' => 'Sin permiso para ver esta sección de Gestión.'], 403);
        }

        $customer = Customer::find($customerId);

        if ($customer === null) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
        }

        // Mismo aislamiento por bandeja que el resto de controladores ERP
        // (aborta con 403 si el cliente no está en ninguna bandeja del agente).
        $this->assertScopedToResolvedCustomer($customer, null);

        if (function_exists('helpdesk_erp_enabled') && ! helpdesk_erp_enabled()) {
            return response()->json([
                'success' => true,
                'state' => 'unavailable',
                'reason' => 'disabled',
                'data' => null,
                'message' => 'La integración con Gestión está desactivada.',
            ]);
        }

        $erpId = $this->resolver->erpIdFor($customer);

        if ($erpId === null) {
            return response()->json([
                'success' => true,
                'state' => 'unlinked',
                'data' => null,
                'message' => 'Este cliente no está vinculado con Gestión.',
                'lookup_status' => $customer->erp_lookup_status,
            ]);
        }

        return [$customer, $erpId];
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $extra
     */
    private function respond(array $result, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $extra, $result));
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function notFound(array $result, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'state' => 'unavailable',
            'reason' => $result['reason'] ?? null,
            'data' => null,
            'message' => $message,
        ], 404);
    }
}
