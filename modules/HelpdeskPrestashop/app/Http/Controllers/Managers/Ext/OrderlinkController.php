<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\OrderlinkStoreRequest;
use Modules\HelpdeskPrestashop\Listeners\Ext\OpsmapMarkAuditedRequest;
use Modules\HelpdeskPrestashop\Services\Ext\OrderlinkService;

/**
 * Pedidos de PrestaShop ligados a una conversación (extensión "orderlink").
 *
 * Solo datos del helpdesk: nada de esto llama al bridge ni escribe en la
 * tienda. Ver y ligar exige ver la conversación y el permiso de pedidos;
 * desligar exige además poder editar la conversación.
 */
class OrderlinkController extends Controller
{
    public function __construct(
        private readonly OrderlinkService $service
    ) {}

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        if (($resp = $this->deny($request, $conversation, 'view')) !== null) {
            return $resp;
        }

        return response()->json([
            'success' => true,
            'data' => $this->service->present($this->service->forConversation($conversation)),
            'can_unlink' => (bool) $request->user()?->can('update', $conversation),
            'ready' => $this->service->ready(),
        ]);
    }

    public function store(OrderlinkStoreRequest $request, Conversation $conversation): JsonResponse
    {
        $this->skipGenericAudit($request);

        if (($resp = $this->deny($request, $conversation, 'view')) !== null) {
            return $resp;
        }

        // Sin cliente no hay pedido que ligar (y el mapeo de estados no
        // podría usarlo nunca).
        if (! $conversation->customer_id) {
            return response()->json(['success' => false, 'message' => 'La conversación no tiene cliente.'], 422);
        }

        $data = $request->validated();

        $link = $this->service->record(
            $conversation,
            (int) $data['ps_order_id'],
            $data['ps_order_reference'] ?? null,
            (string) $data['source'],
            $request->user()?->id,
        );

        if ($link === null) {
            // Tabla aún sin migrar: no es un error para el agente.
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->service->present(collect([$link]))[0],
        ]);
    }

    public function destroy(Request $request, Conversation $conversation, int $link): JsonResponse
    {
        $this->skipGenericAudit($request);

        if (($resp = $this->deny($request, $conversation, 'update')) !== null) {
            return $resp;
        }

        if (! $this->service->unlink($conversation, $link)) {
            return response()->json(['success' => false, 'message' => 'Ese vínculo ya no existe.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * null = autorizado.
     */
    private function deny(Request $request, Conversation $conversation, string $ability): ?JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('helpdeskprestashop.orders.view') || ! $user->can($ability, $conversation)) {
            return response()->json(['success' => false], 403);
        }

        return null;
    }

    /**
     * Las rutas manager.helpdesk.ps.* no GET entran en la auditoría genérica
     * de acciones contra la tienda (opsmap). Ligar/desligar no toca la
     * tienda: se usa la marca de "ya auditada" para que no aparezca allí.
     */
    private function skipGenericAudit(Request $request): void
    {
        $request->attributes->set(OpsmapMarkAuditedRequest::ATTRIBUTE, true);
    }
}
