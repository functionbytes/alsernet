<?php

namespace Modules\HelpdeskLivechat\Services\Catalog\Drivers;

use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;
use Modules\HelpdeskLivechat\Services\Catalog\Contracts\CatalogDriver;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Catálogo PrestaShop a través de la API firmada del bridge (alsernetbridge,
 * acciones product.search / product.get). Preferido sobre la lectura directa
 * de la BD (PrestashopCatalogDriver): la búsqueda, la visibilidad y los
 * precios los resuelve PrestaShop, y no hace falta abrir su BD a Laravel.
 */
final class BridgeCatalogDriver implements CatalogDriver
{
    /** find() de muchos ids es una llamada por id: se acota. */
    private const MAX_FIND_MANY = 12;

    public function __construct(
        private readonly PrestashopContextService $bridge,
        private readonly string $currency = 'EUR',
    ) {}

    public function search(string $query, int $limit = 6): array
    {
        return array_values(array_filter(array_map(
            fn (array $p): ?CatalogProduct => $this->toProduct($p),
            $this->bridge->searchProducts($query, $limit)
        )));
    }

    public function find(string $id): ?CatalogProduct
    {
        if (! ctype_digit($id)) {
            return null;
        }

        $product = $this->bridge->getProductById((int) $id);

        return $product !== null ? $this->toProduct($product) : null;
    }

    public function findMany(array $ids): array
    {
        $found = [];
        foreach (array_slice(array_values(array_unique($ids)), 0, self::MAX_FIND_MANY) as $id) {
            $product = $this->find((string) $id);
            if ($product !== null) {
                $found[$product->id] = $product;
            }
        }

        return $found;
    }

    public function related(string $id, int $limit = 4): array
    {
        // El bridge no expone relacionados; el bot recurre a la búsqueda.
        return [];
    }

    /**
     * @param  array<string, mixed>  $p  Producto normalizado por PrestashopContextService
     */
    private function toProduct(array $p): ?CatalogProduct
    {
        if (empty($p['id'])) {
            return null;
        }

        return new CatalogProduct(
            id: (string) $p['id'],
            title: (string) ($p['name'] ?? ''),
            imageUrl: $p['image'] ?? null,
            url: $p['url'] ?? null,
            // El de PrestaShop (getPriceStatic) si el bridge lo trae; si no, el aproximado.
            price: isset($p['final_price_with_tax']) ? (float) $p['final_price_with_tax']
                : (isset($p['price_with_tax']) ? (float) $p['price_with_tax'] : null),
            currency: $this->currency,
            description: $p['description'] ?? null,
            available: ($p['in_stock'] ?? true) !== false && ($p['available_for_order'] ?? true) !== false,
            idProductAttribute: (int) ($p['id_product_attribute'] ?? 0),
            hasCombinations: (bool) ($p['has_combinations'] ?? false),
        );
    }
}
