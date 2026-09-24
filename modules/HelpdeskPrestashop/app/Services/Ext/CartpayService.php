<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Modules\HelpdeskPrestashop\Support\OrderDetailCache;

/**
 * Extensión "cartpay": datos de cobro de un pedido pendiente (pieza 02) y
 * conversión / vaciado del carrito en vivo (pieza 32). La propiedad del pedido
 * o del carrito la comprueba el puente contra el cliente resuelto por email o
 * external_id, nunca por un id suelto.
 */
class CartpayService
{
    public function __construct(
        private readonly PrestashopContextService $ps
    ) {}

    /**
     * @return array<string, mixed>|null null si el pedido no es del cliente o
     *                                   el cliente no está vinculado
     *
     * @throws PsUpstreamException
     */
    public function orderPayment(Customer $customer, int $orderId): ?array
    {
        return $this->call($customer, 'cartpay.order_payment', ['order_id' => $orderId]);
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws PsUpstreamException
     */
    public function convertPreview(Customer $customer, int $cartId): ?array
    {
        return $this->call($customer, 'cartpay.convert_preview', ['cart_id' => $cartId]);
    }

    /**
     * @return array<string, mixed>|null created=true + order_id, o
     *                                   ok_semantic=false + error/blocking
     *
     * @throws PsUpstreamException
     */
    public function convert(Customer $customer, int $cartId, string $state, string $agent, string $idempotencyKey, bool $sendConfirmation = true): ?array
    {
        $payload = [
            'cart_id' => $cartId,
            'state' => $state,
            'agent' => $agent,
        ];
        // Solo se manda cuando se pide omitir el correo: sin el campo, el
        // puente se comporta como siempre (envía order_conf).
        if (! $sendConfirmation) {
            $payload['send_confirmation'] = false;
        }

        $result = $this->call($customer, 'cartpay.convert', $payload, $idempotencyKey);

        if (($result['created'] ?? false) === true) {
            $this->forget($customer, (int) ($result['order_id'] ?? 0));
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws PsUpstreamException
     */
    public function empty(Customer $customer, int $cartId, string $idempotencyKey): ?array
    {
        $result = $this->call($customer, 'cartpay.empty', ['cart_id' => $cartId], $idempotencyKey);

        if (is_array($result) && ($result['ok_semantic'] ?? true) !== false) {
            $this->forget($customer);
        }

        return $result;
    }

    public function externalId(Customer $customer): ?int
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $externalId !== null ? (int) $externalId : null;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws PsUpstreamException
     */
    private function call(Customer $customer, string $action, array $payload, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->ps->ownershipLookup($customer->email ?: null, $this->externalId($customer), $action);
        if ($lookup === null) {
            return null;
        }

        $data = $this->ps->callBridge($action, ['lookup' => $lookup] + $payload, $idempotencyKey);

        return is_array($data) ? $data : null;
    }

    /**
     * El contexto del panel (carritos, pedidos) y el detalle del pedido nuevo
     * se leen de caché: tras una escritura se olvidan para que el siguiente
     * repintado vea el carrito vacío o el pedido recién creado.
     */
    private function forget(Customer $customer, int $orderId = 0): void
    {
        if (trim((string) $customer->email) !== '') {
            $this->ps->forgetCache((string) $customer->email);
        }

        if ($orderId > 0) {
            OrderDetailCache::forget($orderId, $customer->email ?: null, $this->externalId($customer));
        }
    }
}
