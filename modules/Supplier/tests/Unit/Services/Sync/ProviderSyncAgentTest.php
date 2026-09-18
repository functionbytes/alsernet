<?php

namespace Modules\Supplier\Tests\Unit\Services\Sync;

use Mockery;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncStatus;
use Modules\Supplier\Services\ErpSyncService;
use Modules\Supplier\Services\ProviderSyncAgent;
use Modules\Supplier\Services\SyncStatusService;
use Tests\TestCase;

class ProviderSyncAgentTest extends TestCase
{
    private SyncBatch $batch;

    private SyncStatus $status;

    private SyncStatusService $syncStatusService;

    private ErpSyncService $erpSyncService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batch = Mockery::mock(SyncBatch::class)->makePartial();
        $this->batch->shouldReceive('getAttribute')->with('id')->andReturn(2)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('batch_name')->andReturn('Provider Batch')->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('batch_size')->andReturn(100)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('supplier_id')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('sync_type')->andReturn('provider')->byDefault();
        $this->batch->shouldReceive('setAttribute')->andReturnNull()->byDefault();
        $this->batch->shouldReceive('markAsStarted')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('markAsCompleted')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('markAsFailed')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('incrementProcessedBatches')->andReturn(null)->byDefault();
        $this->batch->id = 2;

        $this->status = Mockery::mock(SyncStatus::class)->makePartial();
        $this->status->shouldReceive('getAttribute')->with('total_items')->andReturn(0)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('id')->andReturn(2)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('batch_id')->andReturn(2)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('synced_items')->andReturn(0)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('failed_items')->andReturn(0)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('skipped_items')->andReturn(0)->byDefault();
        $this->status->shouldReceive('setAttribute')->andReturnNull()->byDefault();
        $this->status->shouldReceive('fresh')->andReturnSelf()->byDefault();
        $this->status->id = 2;
        $this->status->total_items = 0;

        $this->syncStatusService = Mockery::mock(SyncStatusService::class);
        $this->erpSyncService = Mockery::mock(ErpSyncService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_for_supplier_sets_supplier_filter(): void
    {
        $agent = new ProviderSyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $result = $agent->forSupplier(10);

        $this->assertSame($agent, $result);
    }

    public function test_for_supplier_returns_self(): void
    {
        $agent = new ProviderSyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $result = $agent->forSupplier(10);

        $this->assertSame($agent, $result);
    }

    public function test_bidirectional_returns_self(): void
    {
        $agent = new ProviderSyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $result = $agent->bidirectional(false);

        $this->assertSame($agent, $result);
    }

    public function test_get_sync_status_service_returns_service(): void
    {
        $agent = new ProviderSyncAgent(
            $this->batch,
            $this->syncStatusService,
            $this->erpSyncService
        );

        $this->assertSame($this->syncStatusService, $agent->getSyncStatusService());
    }
}
