<?php

namespace Modules\Supplier\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Modules\Supplier\Database\Factories\Product\ProductFactory;
use Modules\Supplier\Database\Factories\Supplier\SupplierFactory;
use Modules\Supplier\Events\SupplierProductUpdated;
use Modules\Supplier\Listeners\SyncProductToErpListener;
use Modules\Supplier\Models\Sync\SyncFailure;
use Modules\Supplier\Services\ErpSyncService;
use Tests\TestCase;

class SyncProductToErpListenerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        activity()->disableLogging();
        // The listener uses a 5-minute Cache key to dedupe events. With
        // DatabaseTransactions the DB is rolled back but the cache persists,
        // so a previous run can leave a key that makes the next run a no-op.
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_calls_erp_sync_when_product_has_erp_id(): void
    {
        $supplier = SupplierFactory::new()->create();
        $product = ProductFactory::new()->create([
            'supplier_id' => $supplier->id,
            'erp_id' => random_int(900000000, 999999999),
        ]);

        $erpSyncService = Mockery::mock(ErpSyncService::class);
        $erpSyncService
            ->shouldReceive('syncProductToOracle')
            ->once()
            ->with($product, ['name']);

        $listener = new SyncProductToErpListener($erpSyncService);

        $event = new SupplierProductUpdated(
            product: $product,
            changedFields: ['name'],
            userId: 1,
            ipAddress: '127.0.0.1',
        );

        $listener->handle($event);
        $this->addToAssertionCount(1);
    }

    public function test_handle_skips_sync_when_product_has_no_erp_id(): void
    {
        $supplier = SupplierFactory::new()->create();
        $product = ProductFactory::new()->create([
            'supplier_id' => $supplier->id,
            'erp_id' => 0,
        ]);

        $erpSyncService = Mockery::mock(ErpSyncService::class);
        $erpSyncService->shouldNotReceive('syncProductToOracle');

        $listener = new SyncProductToErpListener($erpSyncService);

        $event = new SupplierProductUpdated(
            product: $product,
            changedFields: ['name'],
            userId: 1,
            ipAddress: '127.0.0.1',
        );

        $listener->handle($event);
        $this->addToAssertionCount(1);
    }

    public function test_handle_skips_sync_for_non_syncable_fields(): void
    {
        $supplier = SupplierFactory::new()->create();
        $product = ProductFactory::new()->create([
            'supplier_id' => $supplier->id,
            'erp_id' => random_int(900000000, 999999999),
        ]);

        $erpSyncService = Mockery::mock(ErpSyncService::class);
        $erpSyncService->shouldNotReceive('syncProductToOracle');

        $listener = new SyncProductToErpListener($erpSyncService);

        $event = new SupplierProductUpdated(
            product: $product,
            changedFields: ['metadata'],  // Not a syncable field
            userId: 1,
            ipAddress: '127.0.0.1',
        );

        $listener->handle($event);
        $this->addToAssertionCount(1);
    }

    public function test_failed_creates_sync_failure_with_correct_supplier_id(): void
    {
        $supplier = SupplierFactory::new()->create();
        $product = ProductFactory::new()->create([
            'supplier_id' => $supplier->id,
            'erp_id' => random_int(900000000, 999999999),
        ]);

        $erpSyncService = Mockery::mock(ErpSyncService::class);
        $listener = new SyncProductToErpListener($erpSyncService);

        $event = new SupplierProductUpdated(
            product: $product,
            changedFields: ['name', 'code'],
            userId: 1,
            ipAddress: '127.0.0.1',
        );

        $exception = new \RuntimeException('ERP connection failed');
        $listener->failed($event, $exception);

        $this->assertDatabaseHas('supplier_sync_failures', [
            'supplier_id' => $supplier->id,  // Must be supplier_id, NOT product->id
            'entity_id' => $product->id,
            'sync_type' => 'product',
            'error_message' => 'ERP connection failed',
        ]);
    }

    public function test_failed_does_not_use_product_id_as_supplier_id(): void
    {
        $supplier = SupplierFactory::new()->create();
        $product = ProductFactory::new()->create([
            'supplier_id' => $supplier->id,
            'erp_id' => random_int(900000000, 999999999),
        ]);

        $erpSyncService = Mockery::mock(ErpSyncService::class);
        $listener = new SyncProductToErpListener($erpSyncService);

        $event = new SupplierProductUpdated(
            product: $product,
            changedFields: ['name'],
            userId: 1,
            ipAddress: '127.0.0.1',
        );

        $exception = new \RuntimeException('ERP error');
        $listener->failed($event, $exception);

        $failure = SyncFailure::where('entity_id', $product->id)->first();

        $this->assertNotNull($failure);
        $this->assertNotSame($product->id, $failure->supplier_id, 'supplier_id must not equal product->id');
        $this->assertSame($supplier->id, $failure->supplier_id);
    }

    public function test_handle_skips_duplicate_processing(): void
    {
        $supplier = SupplierFactory::new()->create();
        $product = ProductFactory::new()->create([
            'supplier_id' => $supplier->id,
            'erp_id' => random_int(900000000, 999999999),
        ]);

        $event = new SupplierProductUpdated(
            product: $product,
            changedFields: ['name'],
            userId: 1,
            ipAddress: '127.0.0.1',
        );

        $erpSyncService = Mockery::mock(ErpSyncService::class);
        $erpSyncService
            ->shouldReceive('syncProductToOracle')
            ->once();

        $listener = new SyncProductToErpListener($erpSyncService);

        // First call processes
        $listener->handle($event);

        // Mark as already processing (simulates cache from first call)
        $cacheKey = 'erp_sync_product_'.md5(json_encode([
            'product_id' => $product->id,
            'changed_fields' => ['name'],
        ]));
        Cache::put($cacheKey, true, now()->addMinutes(5));

        // Second call should be skipped
        $listener->handle($event);
        $this->addToAssertionCount(1);
    }
}
