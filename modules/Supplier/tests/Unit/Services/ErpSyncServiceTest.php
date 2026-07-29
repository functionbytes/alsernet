<?php

namespace Modules\Supplier\Tests\Unit\Services;

use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\SupplierProductPrice;
use Modules\Supplier\Services\ErpSyncService;
use Tests\TestCase;

/**
 * Compile-time signature verification for ErpSyncService.
 *
 * The service still depends on Oracle models (Articulo, ArtiprovTarifapro,
 * Proveedor) and would require an Oracle connection to run end-to-end.
 * This suite verifies that the public API surface uses the correct types
 * and field names — the previous broken state had `SupplierProduct $product`
 * (class did not exist) and `$product->erp_product_id` (column did not exist).
 */
class ErpSyncServiceTest extends TestCase
{
    public function test_sync_product_to_oracle_signature_uses_product_model(): void
    {
        $reflection = new \ReflectionMethod(ErpSyncService::class, 'syncProductToOracle');
        $params = $reflection->getParameters();

        $this->assertSame('product', $params[0]->getName());
        $this->assertSame(
            Product::class,
            $params[0]->getType()->getName()
        );

        $this->assertSame('changedFields', $params[1]->getName());
        $this->assertSame('array', $params[1]->getType()->getName());
    }

    public function test_sync_price_to_oracle_signature_uses_supplier_product_price_model(): void
    {
        $reflection = new \ReflectionMethod(ErpSyncService::class, 'syncPriceToOracle');
        $params = $reflection->getParameters();

        $this->assertSame(
            SupplierProductPrice::class,
            $params[0]->getType()->getName()
        );
    }

    public function test_sync_provider_to_oracle_signature_uses_supplier_model(): void
    {
        $reflection = new \ReflectionMethod(ErpSyncService::class, 'syncProviderToOracle');
        $params = $reflection->getParameters();

        $this->assertSame(
            Supplier::class,
            $params[0]->getType()->getName()
        );
    }

    public function test_service_does_not_reference_missing_supplier_product_class(): void
    {
        $source = file_get_contents(
            __DIR__.'/../../../app/Services/ErpSyncService.php'
        );

        $this->assertStringNotContainsString(
            'SupplierProduct $',
            $source,
            'ErpSyncService must not type-hint the non-existent SupplierProduct class.'
        );
        $this->assertStringNotContainsString(
            '->erp_product_id',
            $source,
            'ErpSyncService must read $product->erp_id (matches the Product model column).'
        );
    }
}
