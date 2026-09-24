<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\OrdereditReorderRequest;
use Modules\HelpdeskPrestashop\Services\Ext\OrdereditReorderService;

/**
 * Repetir pedido desde el workspace de pedido del inbox (pieza 11 ·
 * ps-order-reorder): vista previa a precio actual y creación del carrito
 * nuevo del cliente en PrestaShop, opcionalmente enviándole por correo (a la
 * dirección de su cuenta, con la plantilla del back-office) el enlace para
 * abrirlo y pagarlo. El enlace inicia sesión como el cliente, por eso no
 * viaja nunca hasta aquí ni al chat.
 */
class OrdereditReorderController extends Controller
{
    private const PERMISSION = 'helpdeskprestashop.orders.reorder';

    private const LINK_PERMISSION = 'helpdeskprestashop.orders.reorder_link';

    /** Motivos del bridge → texto para el agente. */
    private const ERRORS = [
        'no_lines' => 'Marca al menos una línea del pedido.',
        'nothing_addable' => 'Ninguna de las líneas marcadas se puede volver a comprar (descatalogadas o sin stock).',
        'nothing_added' => 'PrestaShop no ha aceptado ninguna de las líneas (stock o disponibilidad).',
        'customer_not_available' => 'La cuenta del cliente en la tienda ya no está disponible.',
        'cart_not_created' => 'PrestaShop no ha podido crear el carrito.',
    ];

    public function __construct(
        private readonly OrdereditReorderService $reorder,
    ) {}

    public function preview(Request $request, Customer $customer, int $order): JsonResponse
    {
        if (($deny = $this->deny($request, $customer, 'view')) !== null) {
            return $deny;
        }

        try {
            $data = $this->reorder->preview($customer, $order);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde ahora mismo.'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado para este cliente.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'can_link' => (bool) $request->user()->can(self::LINK_PERMISSION),
        ]);
    }

    public function store(OrdereditReorderRequest $request, Customer $customer, int $order): JsonResponse
    {
        if (($deny = $this->deny($request, $customer, 'update')) !== null) {
            return $deny;
        }

        $user = $request->user();
        $data = $request->validated();
        $withLink = (bool) ($data['with_link'] ?? false);

        if ($withLink && ! $user->can(self::LINK_PERMISSION)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para enviar el enlace del carrito al cliente.'], 403);
        }

        $lines = array_map(static fn (array $l): array => [
            'order_detail_id' => (int) $l['order_detail_id'],
            'quantity' => (int) $l['quantity'],
        ], $data['lines']);

        // Idempotencia: mismo agente + cliente + pedido + líneas en el mismo
        // minuto = doble clic o reintento, no un segundo carrito.
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', '')) ?: sha1(implode(':', [
            'orderedit.reorder_create', $user->getAuthIdentifier(), $customer->id, $order,
            json_encode($lines), (int) $withLink, now()->format('YmdHi'),
        ]));

        try {
            $result = $this->reorder->create($customer, $order, $lines, $withLink, $idempotencyKey);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido crear el carrito ahora mismo.'], 503);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado para este cliente.'], 404);
        }

        if (! ($result['created'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => self::ERRORS[$result['error'] ?? ''] ?? 'PrestaShop ha rechazado el carrito.',
                'skipped' => $result['skipped'] ?? [],
            ], 422);
        }

        // Por si un bridge antiguo aún devolviera el enlace de auto-login: no
        // debe llegar al navegador del agente.
        unset($result['link']);

        if (function_exists('activity')) {
            activity('helpdeskprestashop')
                ->causedBy($user)
                ->performedOn($customer)
                ->withProperties([
                    'order_id' => $order,
                    'order_reference' => $result['source_reference'] ?? null,
                    'cart_id' => $result['cart_id'] ?? null,
                    'lines_added' => count($result['added'] ?? []),
                    'lines_skipped' => count($result['skipped'] ?? []),
                    'products_total' => $result['products_total'] ?? null,
                    'link_requested' => $withLink,
                    'link_sent' => (bool) ($result['link_sent'] ?? false),
                    'conversation_id' => $data['conversation_id'] ?? null,
                ])
                ->log('ps.orderedit.reorder');
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Permiso de la pieza + acceso a ESE cliente (CustomerPolicy) + algo con
     * lo que el bridge pueda verificar la propiedad del pedido.
     */
    private function deny(Request $request, Customer $customer, string $ability): ?JsonResponse
    {
        $user = $request->user();

        if (! $user?->can(self::PERMISSION) || ! $user->can($ability, $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (trim((string) $customer->email) === '' && $customer->externalIdFor('prestashop') === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no está vinculado a PrestaShop.'], 422);
        }

        return null;
    }
}
