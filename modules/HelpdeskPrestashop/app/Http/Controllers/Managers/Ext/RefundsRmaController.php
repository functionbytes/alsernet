<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\RefundsRmaStateRequest;
use Modules\HelpdeskPrestashop\Services\Ext\RefundsBridgeService;

/**
 * Resolver devolución (pieza 35 · ps-rma-resolve): cambio de estado de la RMA
 * entre los estados reales de PrestaShop, con aviso opcional por la plantilla
 * del core. El mensaje para el cliente no viaja a PrestaShop (su plantilla no
 * admite texto libre): el JS lo deja en el composer y como nota interna, y
 * aquí queda en el log de actividad junto al cambio.
 */
class RefundsRmaController extends Controller
{
    public function __construct(
        private readonly RefundsBridgeService $refunds
    ) {}

    public function show(Request $request, Customer $customer, int $rma): JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('helpdeskprestashop.orders.view') || ! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (($resp = $this->denyUnlinked($customer)) !== null) {
            return $resp;
        }

        try {
            $data = $this->refunds->rmaDetail($customer, $rma);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'No se ha podido consultar la devolución en PrestaShop.'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha devuelto la devolución.'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'can_resolve' => $user->can('helpdeskprestashop.returns.resolve') && $user->can('update', $customer),
        ]);
    }

    public function updateState(RefundsRmaStateRequest $request, Customer $customer, int $rma): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('helpdeskprestashop.returns.resolve') || ! $user->can('update', $customer)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para resolver devoluciones.'], 403);
        }

        if (($resp = $this->denyUnlinked($customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();
        $notify = (bool) ($data['notify'] ?? false);

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', '')) ?: sha1(implode(':', [
            $user->getAuthIdentifier(), $customer->id, $rma, (int) $data['state_id'], (int) $notify, now()->format('YmdHi'),
        ]));

        try {
            $result = $this->refunds->setRmaState($customer, $rma, (int) $data['state_id'], $notify, $idempotencyKey);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde ahora mismo; la devolución no se ha cambiado.'], 503);
        }

        if (! is_array($result) || ! ($result['updated'] ?? false)) {
            $message = match ((string) ($result['error'] ?? '')) {
                'same_state' => 'La devolución ya está en ese estado.',
                'invalid_state' => 'Ese estado no existe en PrestaShop.',
                default => 'PrestaShop ha rechazado el cambio de estado.',
            };

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        if (function_exists('activity')) {
            activity('helpdeskprestashop')
                ->causedBy($user)
                ->performedOn($customer)
                ->withProperties([
                    'return_id' => $rma,
                    'order_id' => $result['order_id'] ?? null,
                    'from_state_id' => $result['previous_state_id'] ?? null,
                    'state_id' => (int) $data['state_id'],
                    'state_name' => $result['state_name'] ?? null,
                    'notified' => (bool) ($result['notified'] ?? false),
                    // Texto libre (puede llevar datos personales): solo si lo hubo.
                    'message_present' => trim((string) ($data['message'] ?? '')) !== '',
                    'conversation_id' => $data['conversation_id'] ?? null,
                ])
                ->log('ps.rma.state_changed');
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    private function denyUnlinked(Customer $customer): ?JsonResponse
    {
        if (trim((string) $customer->email) === '' && $this->refunds->externalId($customer) === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no está vinculado a PrestaShop.'], 422);
        }

        return null;
    }
}
