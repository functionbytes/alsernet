<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Repetir pedido (pieza 11): vista previa de las líneas de un pedido frente al
 * catálogo de hoy y creación del carrito nuevo del cliente en PrestaShop.
 *
 * La propiedad del pedido la verifica el bridge contra el lookup del cliente
 * (email + external_id resueltos server-side desde el Customer local), nunca
 * contra un dato que venga del navegador.
 */
class OrdereditReorderService
{
    public function __construct(
        private readonly PrestashopContextService $service,
    ) {}

    /**
     * @return array<string, mixed>|null null = pedido inexistente o ajeno
     *
     * @throws PsUpstreamException
     */
    public function preview(Customer $customer, int $orderId): ?array
    {
        $lookup = $this->lookup($customer, 'orderedit.reorder_preview');
        if ($lookup === null) {
            return null;
        }

        $key = 'ps_orderedit_reorder:'.$customer->id.':'.$orderId;
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $data = $this->service->callBridge('orderedit.reorder_preview', [
            'order_id' => $orderId,
            'lookup' => $lookup,
        ]);

        if (is_array($data)) {
            Cache::put($key, $data, (int) config('helpdeskprestashop.ext.orderedit.preview_ttl', 60));
        }

        return $data;
    }

    /**
     * @param  array<int, array{order_detail_id:int, quantity:int}>  $lines
     * @return array<string, mixed>|null
     *
     * @throws PsUpstreamException
     */
    public function create(Customer $customer, int $orderId, array $lines, bool $withLink, string $idempotencyKey): ?array
    {
        $lookup = $this->lookup($customer, 'orderedit.reorder_create');
        if ($lookup === null) {
            return null;
        }

        $result = $this->service->callBridge('orderedit.reorder_create', [
            'order_id' => $orderId,
            'lines' => array_values($lines),
            'with_link' => $withLink,
            'lookup' => $lookup,
        ], $idempotencyKey);

        if (is_array($result) && ($result['created'] ?? false)) {
            // Carrito nuevo del cliente: el contexto cacheado (tab Tienda,
            // carritos) ya no refleja la tienda.
            if ($customer->email) {
                $this->service->forgetCache($customer->email);
            }
            Cache::forget('ps_orderedit_reorder:'.$customer->id.':'.$orderId);
        }

        return $result;
    }

    /**
     * @return array{email?: string, external_id?: int}|null
     */
    private function lookup(Customer $customer, string $action): ?array
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $this->service->ownershipLookup(
            $customer->email ?: null,
            $externalId !== null ? (int) $externalId : null,
            $action,
        );
    }
}
