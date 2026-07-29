<?php

namespace Modules\Supplier\Tests\Unit\Services\Sync;

use Mockery;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncStatus;
use Modules\Supplier\Services\CategorySyncAgent;
use Modules\Supplier\Services\ErpSyncService;
use Modules\Supplier\Services\SyncStatusService;
use Tests\TestCase;

class CategorySyncAgentTest extends TestCase
{
    private SyncBatch $batch;

    private SyncStatus $status;

    private SyncStatusService $syncStatusService;

    private ErpSyncService $erpSyncService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batch = Mockery::mock(SyncBatch::class)->makePartial();
        $this->batch->shouldReceive('getAttribute')->with('id')->andReturn(3)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('batch_name')->andReturn('Category Batch')->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('batch_size')->andReturn(100)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('supplier_id')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('sync_type')->andReturn('category')->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('triggered_by')->andReturn('manual')->byDefault();
        $this->batch->shouldReceive('setAttribute')->andReturnNull()->byDefault();
        $this->batch->shouldReceive('markAsStarted')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('markAsCompleted')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('markAsFailed')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('incrementProcessedBatches')->andReturn(null)->byDefault();
        $this->batch->id = 3;

        $this->status = Mockery::mock(SyncStatus::class)->makePartial();
        $this->status->shouldReceive('getAttribute')->with('total_items')->andReturn(0)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('id')->andReturn(3)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('batch_id')->andReturn(3)->byDefault();
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
        $this->status->id = 3;
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

        $agent = new CategorySyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $result = $agent->execute();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('items_processed', $result);
        $this->assertArrayHasKey('items_failed', $result);
    }

    public function test_get_sync_status_service_returns_service(): void
    {
        $agent = new CategorySyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $this->assertSame($this->syncStatusService, $agent->getSyncStatusService());
    }

    public function test_get_batch_returns_batch(): void
    {
        $agent = new CategorySyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $this->assertSame($this->batch, $agent->getBatch());
    }
}
