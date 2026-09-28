<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Extensión "catalog": ficha de disponibilidad y precio de un producto
 * (piezas 16, 25 y 26), comparación de productos (pieza 10) y alta en el
 * aviso de vuelta a stock. Todo pasa por el puente
 * (alsernetbridge/helpers/ext/catalog.php); aquí solo se decide el lookup
 * del cliente, se cachea la lectura y se ponen nombre a las ubicaciones.
 */
class CatalogService
{
    public function __construct(
        private readonly PrestashopContextService $ps
    ) {}

    /**
     * Stock por ubicación, plazo, precio del cliente/público y tramos de un
     * producto. Sin forma de identificar al cliente en PrestaShop se pide
     * igual, sin lookup: el puente devuelve el precio público y sin fila
     * "este cliente". $customer es opcional: sin cliente (p. ej. el bot de
     * ChatFlow, que solo conoce el producto) se pide directamente en público.
     *
     * @return array<string, mixed>|null null si el producto no existe
     *
     * @throws PsUpstreamException
     */
    public function productSheet(?Customer $customer, int $productId, int $productAttributeId = 0, bool $fresh = false): ?array
    {
        $cacheKey = 'helpdeskprestashop.ext.catalog.sheet.'.($customer->id ?? 'anon').'.'.$productId.'.'.$productAttributeId;

        if ($fresh) {
            Cache::forget($cacheKey);
        } elseif (is_array($cached = Cache::get($cacheKey))) {
            return $cached;
        }

        $payload = ['product_id' => $productId, 'product_attribute_id' => $productAttributeId];
        $lookup = $this->lookup($customer);
        if ($lookup !== null) {
            $payload['lookup'] = $lookup;
        }

        $data = $this->ps->callBridge('catalog.product_sheet', $payload);
        if (! is_array($data) || ! is_array($data['product'] ?? null)) {
            return null;
        }

        $data['stock']['locations'] = $this->labelLocations((array) ($data['stock']['locations'] ?? []));

        Cache::put($cacheKey, $data, (int) config('helpdeskprestashop.ext.catalog.sheet_cache_ttl', 60));

        return $data;
    }

    /**
     * Datos comparables de 2-3 productos, en el orden pedido.
     *
     * @param  array<int, int>  $productIds
     * @return array<int, array<string, mixed>>
     *
     * @throws PsUpstreamException
     */
    public function compare(array $productIds, string $lang = 'es'): array
    {
        $data = $this->ps->callBridge('catalog.product_compare', [
            'product_ids' => array_values($productIds),
            'lang' => $lang,
        ]);

        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $order = array_flip(array_values($productIds));
        usort($items, fn (array $a, array $b) => ($order[(int) ($a['id'] ?? 0)] ?? 99) <=> ($order[(int) ($b['id'] ?? 0)] ?? 99));

        return $items;
    }

    /**
     * Apunta al cliente al aviso de vuelta a stock del producto.
     *
     * @return array<string, mixed>|null respuesta del puente (subscribed=true
     *                                   u ok_semantic=false + error); null si
     *                                   el cliente no se puede identificar o
     *                                   el producto no existe
     *
     * @throws PsUpstreamException
     */
    public function subscribeStockAlert(Customer $customer, int $productId, int $productAttributeId, string $idempotencyKey): ?array
    {
        $lookup = $this->lookup($customer);
        if ($lookup === null) {
            return null;
        }

        $result = $this->ps->callBridge('catalog.stock_alert', [
            'lookup' => $lookup,
            'product_id' => $productId,
            'product_attribute_id' => $productAttributeId,
        ], $idempotencyKey);

        // La ficha cacheada dice "no apuntado": fuera, para que el botón
        // cambie al volver a pintarla.
        Cache::forget('helpdeskprestashop.ext.catalog.sheet.'.$customer->id.'.'.$productId.'.'.$productAttributeId);

        return $result;
    }

    /**
     * @return array{email?: string, external_id?: int}|null
     */
    private function lookup(?Customer $customer): ?array
    {
        if ($customer === null) {
            return null;
        }

        $externalId = $customer->externalIdFor('prestashop');
        $email = trim((string) $customer->email);

        if ($email === '' && $externalId === null) {
            return null;
        }

        return $this->ps->ownershipLookup($email !== '' ? $email : null, $externalId !== null ? (int) $externalId : null, 'catalog');
    }

    /**
     * @param  array<int, array{key?:string, units?:int}>  $locations
     * @return array<int, array{key:string, label:string, units:int}>
     */
    private function labelLocations(array $locations): array
    {
        $labels = (array) config('helpdeskprestashop.ext.catalog.locations', []);

        return array_values(array_map(fn (array $l) => [
            'key' => (string) ($l['key'] ?? ''),
            'label' => (string) ($labels[$l['key'] ?? ''] ?? ucfirst((string) ($l['key'] ?? ''))),
            'units' => (int) ($l['units'] ?? 0),
        ], $locations));
    }
}
