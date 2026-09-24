<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Lecturas de la extensión "rever" contra el bridge: cambios de producto que
 * gestiona REVER (plataforma externa de devoluciones y cambios).
 *
 * REVER crea en PrestaShop un pedido de cambio (module = 'rever', estado
 * "Cambio productos") pagado con una regla "Exchange from {REF} return (by
 * REVER)" que apunta al pedido original. El bridge reconstruye el vínculo,
 * el proceso retp_… (notas privadas), lo que el cliente recibe (líneas del
 * pedido de cambio) y lo que devolvió (nota de crédito al completar).
 *
 * Solo lecturas y siempre con lookup de propiedad: sin email ni external_id
 * no se llama (fail-closed). Nunca se llama a la API de REVER.
 */
class ReverExchangesService
{
    public const ACTION = 'rever.order_exchanges';

    public function __construct(
        private readonly PrestashopContextService $bridge
    ) {}

    /**
     * Cambios REVER en los que participa un pedido del cliente (como original
     * o como pedido de cambio) y los procesos REVER del pedido original.
     *
     * @return array{mode:string, order_id:int, reference:string, role:string, rever_enabled:bool, exchanges:array, processes:array}|null
     *
     * @throws PsUpstreamException
     */
    public function forOrder(int $orderId, ?string $email, ?int $externalId): ?array
    {
        $lookup = $this->bridge->ownershipLookup($email, $externalId, self::ACTION);
        if ($lookup === null) {
            return null;
        }

        $data = $this->bridge->callBridge(self::ACTION, [
            'order_id' => $orderId,
            'lookup' => $lookup,
        ]);

        return $this->normalize($data);
    }

    /**
     * Últimos cambios REVER del cliente (máx. 30, el bridge da además el total).
     *
     * @return array{mode:string, rever_enabled:bool, total:int, exchanges:array}|null
     *
     * @throws PsUpstreamException
     */
    public function forCustomer(?string $email, ?int $externalId): ?array
    {
        $lookup = $this->bridge->ownershipLookup($email, $externalId, self::ACTION);
        if ($lookup === null) {
            return null;
        }

        $data = $this->bridge->callBridge(self::ACTION, [
            'lookup' => $lookup,
        ]);

        return $this->normalize($data);
    }

    /**
     * Respuesta del bridge con la forma que espera el panel; null si no es un
     * resultado válido (error semántico, pedido de otro cliente…).
     */
    private function normalize(mixed $data): ?array
    {
        if (! is_array($data) || ($data['ok_semantic'] ?? true) === false) {
            return null;
        }

        $data['exchanges'] = array_values(array_filter(
            (array) ($data['exchanges'] ?? []),
            fn ($e) => is_array($e) && is_array($e['exchange_order'] ?? null)
        ));
        $data['processes'] = array_values(array_filter((array) ($data['processes'] ?? []), 'is_array'));
        $data['rever_enabled'] = (bool) ($data['rever_enabled'] ?? false);

        return $data;
    }
}
