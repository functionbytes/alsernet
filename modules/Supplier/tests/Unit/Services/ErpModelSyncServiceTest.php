<?php

namespace Modules\Supplier\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Supplier\Database\Factories\Product\ProductAttributeFactory;
use Modules\Supplier\Database\Factories\Product\ProductFactory;
use Modules\Supplier\Exceptions\BudgetExceededException;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductAttribute;
use Modules\Supplier\Services\Integrations\ErpModelSyncService;
use Tests\TestCase;

class ErpModelSyncServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfTablesMissing(array $tables): void
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table {$table} is not present in the test database.");
            }
        }
    }

    /**
     * Builds a response payload that matches the /detailed endpoint format exactly.
     */
    private function detailedPayload(int $erpId, array $overrides = []): array
    {
        return array_replace_recursive([
            'success' => true,
            'data' => [
                'id' => $erpId,
                'code' => 'TST'.str_pad($erpId, 6, '0', STR_PAD_LEFT),
                'name' => 'Producto Smoke Test '.$erpId,
                'description' => 'Descripción CLOB del modelo '.$erpId,
                'available' => 1,
                'web' => 2,
                'categorie' => [
                    'id' => 77701,
                    'description' => 'Categoría Test',
                    'description_short' => 'Cat',
                    'available' => true,
                    'sport' => [
                        'id' => 77801,
                        'description' => 'Deporte Test',
                        'description_short' => 'Dep',
                        'available' => true,
                    ],
                ],
                'supplier' => [
                    'id' => 88801,
                    'name' => 'Proveedor Test',
                    'cif' => null,
                    'email' => null,
                    'available' => true,
                ],
                'product_attributes' => [
                    [
                        'id' => 99901,
                        'code' => 'ART001',
                        'name' => 'Artículo Test',
                        'reference' => 'REF001',
                        'ean13' => '1234567890123',
                        'upc' => null,
                        'available' => 1,
                        'web' => 1,
                        'categorie' => null,
                        'supplier' => [],
                        'color' => null,
                        'talla' => null,
                    ],
                ],
            ],
        ], $overrides);
    }

    // ── retryModelFromErp ────────────────────────────────────────────────────

    public function test_retry_model_from_erp_creates_product_on_success(): void
    {
        $this->skipIfTablesMissing(['supplier_products', 'supplier_product_attributes']);

        $erpId = 999991;

        Http::fake([
            '*' => Http::response($this->detailedPayload($erpId), 200),
        ]);

        $service = $this->app->make(ErpModelSyncService::class);
        $result = $service->retryModelFromErp($erpId);

        $this->assertTrue($result['success'], 'Expected success=true, got: '.($result['error'] ?? 'none'));

        $product = Product::where('erp_id', $erpId)->first();
        $this->assertNotNull($product);
        $this->assertSame('TST'.str_pad($erpId, 6, '0', STR_PAD_LEFT), $product->code);
        $this->assertSame(2, $product->metadata['web_status'] ?? null);
        $this->assertSame('Descripción CLOB del modelo '.$erpId, $product->metadata['erp_description'] ?? null);
    }

    public function test_retry_model_from_erp_returns_failure_on_http_error(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $service = $this->app->make(ErpModelSyncService::class);
        $result = $service->retryModelFromErp(999992);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['error']);
    }

    public function test_retry_model_from_erp_returns_failure_when_erp_success_false(): void
    {
        Http::fake([
            '*' => Http::response(['success' => false], 200),
        ]);

        $service = $this->app->make(ErpModelSyncService::class);
        $result = $service->retryModelFromErp(999993);

        $this->assertFalse($result['success']);
    }

    // ── syncAttributesInline orphan cleanup ──────────────────────────────────

    public function test_sync_attributes_inline_soft_deletes_orphan_attributes(): void
    {
        $this->skipIfTablesMissing(['supplier_products', 'supplier_product_attributes']);

        $product = ProductFactory::new()->create(['erp_id' => 999994]);
        $kept = ProductAttributeFactory::new()->create(['product_id' => $product->id, 'erp_id' => 11111]);
        $orphan = ProductAttributeFactory::new()->create(['product_id' => $product->id, 'erp_id' => 22222]);

        $reflection = new \ReflectionClass(ErpModelSyncService::class);
        $method = $reflection->getMethod('syncAttributesInline');
        $method->setAccessible(true);

        $service = $this->app->make(ErpModelSyncService::class);
        $method->invoke($service, $product, [
            [
                'id' => 11111, 'code' => 'A1', 'name' => 'Art 1',
                'reference' => '', 'ean13' => null,
                'available' => 1, 'web' => 1, 'categorie' => null, 'supplier' => [],
            ],
        ]);

        $this->assertNull(ProductAttribute::find($orphan->id), 'Orphan should be soft-deleted');
        $this->assertNotNull(ProductAttribute::find($kept->id), 'Kept attribute should still exist');
    }

    public function test_sync_attributes_inline_does_not_soft_delete_when_no_incoming_ids(): void
    {
        $this->skipIfTablesMissing(['supplier_products', 'supplier_product_attributes']);

        $product = ProductFactory::new()->create(['erp_id' => 999997]);
        $attr = ProductAttributeFactory::new()->create(['product_id' => $product->id, 'erp_id' => 33333]);

        $reflection = new \ReflectionClass(ErpModelSyncService::class);
        $method = $reflection->getMethod('syncAttributesInline');
        $method->setAccessible(true);

        $service = $this->app->make(ErpModelSyncService::class);
        // Pass an attribute without 'id' key — no incoming erp IDs collected → no deletion
        $method->invoke($service, $product, [
            ['code' => 'A1', 'name' => 'Art 1', 'available' => 1],
        ]);

        $this->assertNotNull(ProductAttribute::find($attr->id), 'Attribute should not be deleted when no erp IDs in incoming data');
    }

    // ── BudgetExceededException ──────────────────────────────────────────────

    public function test_budget_exceeded_exception_has_correct_properties(): void
    {
        $ex = new BudgetExceededException(
            provider: 'openai',
            window: 'daily',
            usage: 9.50,
            limit: 10.00,
        );

        $this->assertSame('openai', $ex->provider);
        $this->assertSame('daily', $ex->window);
        $this->assertEqualsWithDelta(9.50, $ex->usage, 0.001);
        $this->assertEqualsWithDelta(10.00, $ex->limit, 0.001);
        $this->assertStringContainsString('openai', $ex->getMessage());
        $this->assertStringContainsString('daily', $ex->getMessage());
    }

    public function test_budget_exceeded_exception_renders_402_for_json_requests(): void
    {
        $ex = new BudgetExceededException(
            provider: 'anthropic',
            window: 'monthly',
            usage: 50.00,
            limit: 50.00,
        );

        $request = Request::create('/api/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = $ex->render($request);

        $this->assertNotNull($response);
        $this->assertSame(402, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertSame('budget_exceeded', $body['error']);
        $this->assertSame('anthropic', $body['provider']);
        $this->assertSame('monthly', $body['window']);
    }

    public function test_budget_exceeded_exception_returns_null_for_non_json_non_api_requests(): void
    {
        $ex = new BudgetExceededException('openai', 'daily', 5.0, 5.0);
        $request = Request::create('/panel/settings', 'GET');
        $this->assertNull($ex->render($request));
    }

    // ── metadata fields in upsertProduct ────────────────────────────────────

    public function test_upsert_product_captures_erp_description_in_metadata(): void
    {
        $this->skipIfTablesMissing(['supplier_products']);

        $erpId = 999995;

        Http::fake([
            '*' => Http::response($this->detailedPayload($erpId, [
                'data' => ['description' => 'Texto CLOB largo desde Oracle', 'web' => 1],
            ]), 200),
        ]);

        $service = $this->app->make(ErpModelSyncService::class);
        $result = $service->retryModelFromErp($erpId);
        $this->assertTrue($result['success'], $result['error'] ?? '');

        $product = Product::where('erp_id', $erpId)->first();
        $this->assertSame('Texto CLOB largo desde Oracle', $product->metadata['erp_description'] ?? null);
        $this->assertSame(1, $product->metadata['web_status'] ?? null);
    }

    public function test_upsert_product_preserves_existing_metadata_keys(): void
    {
        $this->skipIfTablesMissing(['supplier_products']);

        $erpId = 999996;
        ProductFactory::new()->create([
            'erp_id' => $erpId,
            'metadata' => ['custom_key' => 'preserved_value'],
        ]);

        Http::fake([
            '*' => Http::response($this->detailedPayload($erpId, [
                'data' => ['description' => 'Nueva descripción', 'web' => 0],
            ]), 200),
        ]);

        $service = $this->app->make(ErpModelSyncService::class);
        $result = $service->retryModelFromErp($erpId);
        $this->assertTrue($result['success'], $result['error'] ?? '');

        $product = Product::where('erp_id', $erpId)->first();
        $this->assertSame('preserved_value', $product->metadata['custom_key'] ?? null, 'Existing metadata key must be preserved');
        $this->assertSame('Nueva descripción', $product->metadata['erp_description'] ?? null);
    }
}
