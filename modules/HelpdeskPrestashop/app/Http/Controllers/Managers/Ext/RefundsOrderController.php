<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\RefundsIssueRequest;
use Modules\HelpdeskPrestashop\Services\Ext\RefundsBridgeService;

/**
 * Reembolso parcial desde el workspace de pedido (pieza 08 · ps-order-refund).
 *
 * El importe lo calcula PrestaShop con su propio handler; aquí se comprueba
 * antes el límite por rol (con IVA, lo que sale de caja) sobre las líneas
 * reembolsables que devuelve el puente, y el puente lo vuelve a comprobar con
 * su cálculo antes de escribir. Cada reembolso queda en el log de actividad.
 */
class RefundsOrderController extends Controller
{
    public function __construct(
        private readonly RefundsBridgeService $refunds
    ) {}

    public function show(Request $request, Customer $customer, int $order): JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('helpdeskprestashop.orders.view') || ! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (($resp = $this->denyUnlinked($customer)) !== null) {
            return $resp;
        }

        try {
            $data = $this->refunds->orderRefundable($customer, $order);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'No se ha podido consultar el pedido en PrestaShop.'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha devuelto el pedido.'], 422);
        }

        $limit = $this->limitFor($user);

        return response()->json([
            'success' => true,
            'data' => $data,
            'limit' => $limit,
            'can_issue' => $limit !== null && $user->can('update', $customer),
        ]);
    }

    public function store(RefundsIssueRequest $request, Customer $customer, int $order): JsonResponse
    {
        $user = $request->user();

        $limit = $this->limitFor($user);
        if ($limit === null) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para emitir reembolsos.'], 403);
        }

        if (! $user->can('update', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (($resp = $this->denyUnlinked($customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();
        $lines = array_map(fn (array $l) => [
            'order_detail_id' => (int) $l['order_detail_id'],
            'quantity' => (int) $l['quantity'],
        ], $data['lines'] ?? []);
        $refundShipping = (bool) ($data['refund_shipping'] ?? false);

        try {
            $refundable = $this->refunds->orderRefundable($customer, $order);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde o el pedido no es de este cliente.'], 503);
        }

        if ($refundable === null) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha devuelto el pedido.'], 422);
        }

        if (! ($refundable['paid'] ?? false)) {
            return response()->json(['success' => false, 'message' => 'El pedido no está pagado: no hay nada que reembolsar.'], 422);
        }

        $estimate = $this->estimate($refundable, $lines, $refundShipping);
        if (is_string($estimate)) {
            return response()->json(['success' => false, 'message' => $estimate], 422);
        }

        if ($estimate > $limit) {
            return response()->json([
                'success' => false,
                'needs_approval' => true,
                'limit' => $limit,
                'amount' => $estimate,
                'message' => 'El reembolso supera tu límite ('.$this->fmt($limit).'). Pide aprobación.',
            ], 422);
        }

        // Idempotencia: mismo agente + pedido + contenido en el mismo minuto =
        // doble clic. Un segundo reembolso idéntico más tarde sí debe pasar.
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', '')) ?: sha1(implode(':', [
            $user->getAuthIdentifier(), $customer->id, $order, json_encode($lines), (int) $refundShipping,
            $data['destination'], (int) ($data['restock'] ?? false), now()->format('YmdHi'),
        ]));

        try {
            $result = $this->refunds->issuePartialRefund(
                $customer,
                $order,
                $lines,
                $refundShipping,
                $data['destination'],
                (bool) ($data['restock'] ?? false),
                (int) round($limit * 100),
                $idempotencyKey,
            );
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido emitir el reembolso ahora mismo.'], 503);
        }

        if (! is_array($result) || ! ($result['refunded'] ?? false)) {
            return $this->rejection($result, $limit);
        }

        if (function_exists('activity')) {
            activity('helpdeskprestashop')
                ->causedBy($user)
                ->performedOn($customer)
                ->withProperties([
                    'order_id' => $order,
                    'order_slip_id' => $result['order_slip_id'] ?? null,
                    'amount' => $result['amount'] ?? $estimate,
                    'destination' => $data['destination'],
                    'refund_shipping' => $refundShipping,
                    'lines' => $lines,
                    'voucher_code' => $result['voucher']['code'] ?? null,
                    'conversation_id' => $data['conversation_id'] ?? null,
                ])
                ->log('ps.refund.issued');
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Importe con IVA de lo pedido, con las mismas reglas que el calculador
     * de PrestaShop (unidades ≤ reembolsables, envío ≤ lo que queda). Devuelve
     * un mensaje de error si alguna línea no es válida.
     */
    private function estimate(array $refundable, array $lines, bool $refundShipping): float|string
    {
        $byId = collect($refundable['lines'] ?? [])->keyBy(fn ($l) => (int) ($l['order_detail_id'] ?? 0));
        $cents = 0;

        foreach ($lines as $line) {
            $row = $byId->get($line['order_detail_id']);
            if ($row === null) {
                return 'Una de las líneas no pertenece a este pedido.';
            }
            if ($line['quantity'] > (int) ($row['refundable'] ?? 0)) {
                return 'Solo quedan '.(int) ($row['refundable'] ?? 0).' unidad(es) por reembolsar de «'.($row['name'] ?? 'la línea').'».';
            }
            $cents += (int) round($line['quantity'] * (float) ($row['unit_price_tax_incl'] ?? 0) * 100);
        }

        if ($refundShipping) {
            if ((float) ($refundable['shipping_refundable'] ?? 0) <= 0) {
                return 'Los gastos de envío ya están reembolsados.';
            }
            $cents += (int) round((float) $refundable['shipping_refundable'] * 100);
        }

        return $cents / 100;
    }

    private function rejection(?array $result, float $limit): JsonResponse
    {
        $error = (string) ($result['error'] ?? '');

        if ($error === 'over_limit') {
            return response()->json([
                'success' => false,
                'needs_approval' => true,
                'limit' => $limit,
                'amount' => $result['amount'] ?? null,
                'message' => 'PrestaShop calcula '.$this->fmt((float) ($result['amount'] ?? 0)).', por encima de tu límite. Pide aprobación.',
            ], 422);
        }

        $message = match ($error) {
            'not_paid' => 'El pedido no está pagado: no hay nada que reembolsar.',
            'quantity_too_high' => 'Alguna línea ya no tiene tantas unidades por reembolsar. Recarga el pedido.',
            'exceeds_paid' => 'El reembolso superaría lo cobrado en el pedido (queda '.$this->fmt((float) ($result['refundable'] ?? 0)).').',
            'nothing_to_refund' => 'No hay nada que reembolsar con esa selección.',
            'invalid_lines' => 'Alguna línea no pertenece a este pedido.',
            default => 'PrestaShop ha rechazado el reembolso.',
        };

        return response()->json(['success' => false, 'message' => $message, 'error' => $error ?: null], 422);
    }

    /**
     * Límite por reembolso del agente, o null si no puede reembolsar.
     */
    private function limitFor($user): ?float
    {
        if ($user?->can('helpdeskprestashop.refunds.approve')) {
            return (float) config('helpdeskprestashop.ext.refunds.approver_limit', 500);
        }

        if ($user?->can('helpdeskprestashop.refunds.issue')) {
            return (float) config('helpdeskprestashop.ext.refunds.agent_limit', 50);
        }

        return null;
    }

    private function denyUnlinked(Customer $customer): ?JsonResponse
    {
        if (trim((string) $customer->email) === '' && $this->refunds->externalId($customer) === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no está vinculado a PrestaShop.'], 422);
        }

        return null;
    }

    private function fmt(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' €';
    }
}
