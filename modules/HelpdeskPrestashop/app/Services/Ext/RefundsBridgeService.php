<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Modules\HelpdeskPrestashop\Support\OrderDetailCache;

/**
 * Llamadas de la extensión "refunds" al puente (helpers/ext/refunds.php):
 * reembolso parcial por el handler del core de PrestaShop y cambio de estado
 * de las devoluciones (RMA). La propiedad del pedido/RMA la verifica el puente
 * contra el cliente del lookup, que sale SIEMPRE del Customer del helpdesk.
 */
class RefundsBridgeService
{
    public function __construct(
        private readonly PrestashopContextService $context
    ) {}

    /**
     * Líneas reembolsables, envío reembolsable y lo ya abonado de un pedido.
     *
     * @throws PsUpstreamException también cuando el pedido no es del cliente (404)
     */
    public function orderRefundable(Customer $customer, int $orderId): ?array
    {
        $lookup = $this->lookup($customer, 'refunds.order_refundable');
        if ($lookup === null) {
            return null;
        }

        return $this->context->callBridge('refunds.order_refundable', [
            'lookup' => $lookup,
            'order_id' => $orderId,
        ]);
    }

    /**
     * Emite el reembolso parcial. $maxAmountCents es el tope del agente: el
     * puente lo vuelve a comprobar con el importe de PrestaShop antes de escribir.
     *
     * @param  array<int, array{order_detail_id:int, quantity:int}>  $lines
     *
     * @throws PsUpstreamException
     */
    public function issuePartialRefund(Customer $customer, int $orderId, array $lines, bool $refundShipping, string $destination, bool $restock, int $maxAmountCents, string $idempotencyKey): ?array
    {
        $lookup = $this->lookup($customer, 'refunds.issue_partial');
        if ($lookup === null) {
            return null;
        }

        $result = $this->context->callBridge('refunds.issue_partial', [
            'lookup' => $lookup,
            'order_id' => $orderId,
            'lines' => array_values($lines),
            'refund_shipping' => $refundShipping,
            'destination' => $destination,
            'restock' => $restock,
            'max_amount_cents' => $maxAmountCents,
        ], $idempotencyKey);

        $this->forget($customer, $orderId);

        return $result;
    }

    /**
     * @throws PsUpstreamException también cuando la RMA no es del cliente (404)
     */
    public function rmaDetail(Customer $customer, int $returnId): ?array
    {
        $lookup = $this->lookup($customer, 'refunds.rma_detail');
        if ($lookup === null) {
            return null;
        }

        return $this->context->callBridge('refunds.rma_detail', [
            'lookup' => $lookup,
            'return_id' => $returnId,
        ]);
    }

    /**
     * @throws PsUpstreamException
     */
    public function setRmaState(Customer $customer, int $returnId, int $stateId, bool $notify, string $idempotencyKey): ?array
    {
        $lookup = $this->lookup($customer, 'refunds.rma_set_state');
        if ($lookup === null) {
            return null;
        }

        $result = $this->context->callBridge('refunds.rma_set_state', [
            'lookup' => $lookup,
            'return_id' => $returnId,
            'state_id' => $stateId,
            'notify' => $notify,
        ], $idempotencyKey);

        if ($customer->email) {
            $this->context->forgetCache($customer->email);
        }

        return $result;
    }

    public function externalId(Customer $customer): ?int
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $externalId !== null ? (int) $externalId : null;
    }

    private function lookup(Customer $customer, string $action): ?array
    {
        return $this->context->ownershipLookup($customer->email ?: null, $this->externalId($customer), $action);
    }

    /**
     * Tras un reembolso cambian el detalle del pedido (cantidades reembolsadas)
     * y el contexto del cliente (tarjeta Reembolsos): se olvidan ambos.
     */
    private function forget(Customer $customer, int $orderId): void
    {
        OrderDetailCache::forget($orderId, $customer->email ?: null, $this->externalId($customer));

        if ($customer->email) {
            $this->context->forgetCache($customer->email);
        }
    }
}
