<?php

namespace Modules\Supplier\Tests\Unit\Services\Sync;

use Mockery;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncStatus;
use Modules\Supplier\Services\ErpSyncService;
use Modules\Supplier\Services\ProductSyncAgent;
use Modules\Supplier\Services\SyncStatusService;
use ReflectionMethod;
use Tests\TestCase;

class ProductSyncAgentTest extends TestCase
{
    private SyncBatch $batch;

    private SyncStatus $status;

    private SyncStatusService $syncStatusService;

    private ErpSyncService $erpSyncService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batch = Mockery::mock(SyncBatch::class)->makePartial();
        $this->batch->shouldReceive('getAttribute')->with('id')->andReturn(1)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('batch_name')->andReturn('Product Batch')->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('batch_size')->andReturn(50)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('supplier_id')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('sync_type')->andReturn('product')->byDefault();
        $this->batch->shouldReceive('setAttribute')->andReturnNull()->byDefault();
        $this->batch->shouldReceive('markAsStarted')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('markAsCompleted')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('markAsFailed')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('incrementProcessedBatches')->andReturn(null)->byDefault();
        $this->batch->id = 1;

        $this->status = Mockery::mock(SyncStatus::class)->makePartial();
        $this->status->shouldReceive('getAttribute')->with('total_items')->andReturn(0)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('id')->andReturn(1)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('batch_id')->andReturn(1)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('synced_items')->andReturn(0)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('failed_items')->andReturn(0)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('skipped_items')->andReturn(0)->byDefault();
        $this->status->shouldReceive('setAttribute')->andReturnNull()->byDefault();
        $this->status->shouldReceive('fresh')->andReturnSelf()->byDefault();
        $this->status->shouldReceive('toArray')->andReturn([
            'success' => true,
            'items_processed' => 0,
            'items_failed' => 0,
            'items_skipped' => 0,
            'message' => 'completed',
        ])->byDefault();
        $this->status->id = 1;
        $this->status->total_items = 0;

        $this->syncStatusService = Mockery::mock(SyncStatusService::class);
        $this->erpSyncService = Mockery::mock(ErpSyncService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_execute_returns_success_with_zero_items(): void
    {
        $this->syncStatusService
            ->shouldReceive('startSync')
            ->once()
            ->andReturn($this->status);

        $this->syncStatusService
            ->shouldReceive('isCancellationRequested')
            ->andReturn(false);

        $this->syncStatusService
            ->shouldReceive('completeSync')
            ->once()
            ->andReturn($this->status);

        $agent = new ProductSyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $result = $agent->execute();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('items_processed', $result);
    }

    public function test_for_supplier_returns_self(): void
    {
        $agent = new ProductSyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $result = $agent->forSupplier(42);

        $this->assertSame($agent, $result);
    }

    public function test_within_date_range_returns_self(): void
    {
        $agent = new ProductSyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $result = $agent->withinDateRange('2024-01-01', '2024-12-31');

        $this->assertSame($agent, $result);
    }

    public function test_get_batch_returns_batch(): void
    {
        $agent = new ProductSyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $this->assertSame($this->batch, $agent->getBatch());
    }

    public function test_changed_fields_uses_dirty_attributes_when_present(): void
    {
        $agent = new ProductSyncAgent($this->batch, $this->syncStatusService, $this->erpSyncService);
        $method = new ReflectionMethod(ProductSyncAgent::class, 'changedFieldsFor');
        $method->setAccessible(true);

        $product = new Product(['name' => 'New name', 'description' => 'ignored']);

        $this->assertSame(['name'], $method->invoke($agent, $product));
    }

    public function test_changed_fields_falls_back_to_full_syncable_set_when_clean(): void
    {
        $agent = new ProductSyncAgent($this->batch, $this->syncStatusService, $this->erpSyncService);
        $method = new ReflectionMethod(ProductSyncAgent::class, 'changedFieldsFor');
        $method->setAccessible(true);

        $product = new Product;

        $this->assertSame(['name', 'barcode', 'recommended_price', 'is_active'], $method->invoke($agent, $product));
    }
}
