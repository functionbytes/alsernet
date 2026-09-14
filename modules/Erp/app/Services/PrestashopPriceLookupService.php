<?php

namespace Modules\Erp\Services;

use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;

/**
 * Adaptador entre Erp y el bridge de PrestaShop (alsernetbridge), vía el
 * módulo opcional HelpdeskPrestashop — reemplaza las consultas directas a
 * la conexión Eloquent 'prestashop' que usaban ValidatePriceFromGestion y
 * SyncSpecificPrices para calcular precio/stock y listar specific_price.
 *
 * Dependencia SUAVE (class_exists()+Module), mismo patrón que
 * Modules\Document\Services\PrestashopOrderLookupService.
 */
class PrestashopPriceLookupService
{
    private const SERVICE_CLASS = 'Modules\\HelpdeskPrestashop\\Services\\PrestashopContextService';

    public function available(): bool
    {
        return class_exists(self::SERVICE_CLASS)
            && (Module::find('HelpdeskPrestashop')?->isEnabled() ?? false);
    }

    /**
     * @return array{price_with_tax: float, stock: int}|null
     */
    public function priceDetail(int $productId, int $productAttributeId, int $countryId): ?array
    {
        if (! $this->available()) {
            return null;
        }

        try {
            return app(self::SERVICE_CLASS)->getProductPriceDetail($productId, $productAttributeId, $countryId);
        } catch (\Throwable $e) {
            Log::warning('Erp: fallo consultando product.price_detail en el bridge PrestaShop', [
                'product_id' => $productId,
                'product_attribute_id' => $productAttributeId,
                'country_id' => $countryId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<int, array{id_specific_price:int, id_product:int, id_product_attribute:int, id_country:int, from:?string, to:?string, reference:?string}>
     */
    public function listSpecificPrices(string $scope = 'active', int $limit = 500): array
    {
        if (! $this->available()) {
            return [];
        }

        try {
            return app(self::SERVICE_CLASS)->listSpecificPrices($scope, $limit);
        } catch (\Throwable $e) {
            Log::warning('Erp: fallo consultando specific_price.list en el bridge PrestaShop', [
                'scope' => $scope,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
