<?php

namespace Modules\Supplier\Tests\Unit\Services\Sync;

use Mockery;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncStatus;
use Modules\Supplier\Services\BaseSyncAgent;
use Modules\Supplier\Services\SyncStatusService;
use Tests\TestCase;

class BaseSyncAgentTest extends TestCase
{
    private SyncBatch $batch;

    private SyncStatusService $syncStatusService;

    private SyncStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batch = Mockery::mock(SyncBatch::class)->makePartial();
        $this->batch->shouldReceive('getAttribute')->with('id')->andReturn(1)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('batch_name')->andReturn('Test Batch')->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('batch_size')->andReturn(100)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('supplier_id')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('getAttribute')->with('sync_type')->andReturn('product')->byDefault();
        $this->batch->shouldReceive('setAttribute')->andReturnNull()->byDefault();
        $this->batch->shouldReceive('markAsStarted')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('markAsCompleted')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('markAsFailed')->andReturn(null)->byDefault();
        $this->batch->shouldReceive('incrementProcessedBatches')->andReturn(null)->byDefault();
        $this->batch->id = 1;

        $this->status = Mockery::mock(SyncStatus::class)->makePartial();
        $this->status->shouldReceive('getAttribute')->with('total_items')->andReturn(10)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('id')->andReturn(1)->byDefault();
        $this->status->shouldReceive('getAttribute')->with('batch_id')->andReturn(1)->byDefault();
        $this->status->shouldReceive('setAttribute')->andReturnNull()->byDefault();
        $this->status->id = 1;
        $this->status->total_items = 10;

        $this->syncStatusService = Mockery::mock(SyncStatusService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeAgent(array $items = [], bool $itemSucceeds = true): BaseSyncAgent
    {
        return new class($this->batch, $this->syncStatusService, $items, $itemSucceeds) extends BaseSyncAgent
        {
            private array $items;

            private bool $itemSucceeds;

            public function __construct(
                SyncBatch $batch,
                SyncStatusService $service,
                array $items,
                bool $itemSucceeds
            ) {
                parent::__construct($batch, $service);
                $this->items = $items;
                $this->itemSucceeds = $itemSucceeds;
            }

            public function execute(): array
            {
                $this->status = $this->syncStatusService->startSync(
                    batch: $this->batch,
                    totalItems: count($this->items),
                    triggeredBy: 'test',
                );

                $this->processBatch($this->items);

                return [
                    'success' => true,
                    'items_processed' => $this->itemsProcessed,
                    'items_failed' => $this->itemsFailed,
                    'items_skipped' => $this->itemsSkipped,
                    'message' => 'done',
                ];
            }

            protected function getSyncType(): string
            {
                return 'test';
            }

            protected function fetchItems(): iterable
            {
                return $this->items;
            }

            protected function processItem(mixed $item): bool
            {
                return $this->itemSucceeds;
            }

            public function exposeIsCancellationRequested(): bool
            {
                return $this->isCancellationRequested();
            }

            public function exposeCompleteSync(): SyncStatus
            {
                return $this->completeSync();
            }

            public function exposeFailSync(string $reason): SyncStatus
            {
                return $this->failSync($reason);
            }
        };
    }

    public function test_process_batch_increments_processed_count_on_success(): void
    {
        $items = ['item1', 'item2', 'item3'];

        $this->syncStatusService
            ->shouldReceive('startSync')
            ->once()
            ->andReturn($this->status);

        $this->syncStatusService
            ->shouldReceive('isCancellationRequested')
            ->andReturn(false);

        $this->syncStatusService
            ->shouldReceive('updateProgress')
            ->andReturn($this->status);

        $agent = $this->makeAgent($items, true);
        $result = $agent->execute();

        $this->assertSame(3, $result['items_processed']);
        $this->assertSame(0, $result['items_failed']);
    }

    public function test_process_batch_increments_failed_count_on_failure(): void
    {
        $items = ['item1', 'item2'];

        $this->syncStatusService
            ->shouldReceive('startSync')
            ->once()
            ->andReturn($this->status);

        $this->syncStatusService
            ->shouldReceive('isCancellationRequested')
            ->andReturn(false);

        $this->syncStatusService
            ->shouldReceive('updateProgress')
            ->andReturn($this->status);

        $agent = $this->makeAgent($items, false);
        $result = $agent->execute();

        $this->assertSame(0, $result['items_processed']);
        $this->assertSame(2, $result['items_failed']);
    }

    public function test_cancellation_stops_processing(): void
    {
        $items = range(1, 100);

        $this->syncStatusService
            ->shouldReceive('startSync')
            ->once()
            ->andReturn($this->status);

        $this->syncStatusService
            ->shouldReceive('isCancellationRequested')
            ->andReturn(true);

        $this->syncStatusService
            ->shouldReceive('updateProgress')
            ->andReturn($this->status);

        $agent = $this->makeAgent($items, true);
        $result = $agent->execute();

        $this->assertSame(0, $result['items_processed']);
    }

    public function test_get_progress_returns_correct_structure(): void
    {
        $this->syncStatusService
            ->shouldReceive('isCancellationRequested')
            ->andReturn(false);

        $agent = $this->makeAgent([], true);

        // Manually set status via reflection
        $reflection = new \ReflectionClass($agent);
        $prop = $reflection->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($agent, $this->status);

        $progress = $agent->getProgress();

        $this->assertArrayHasKey('total_items', $progress);
        $this->assertArrayHasKey('items_processed', $progress);
        $this->assertArrayHasKey('items_failed', $progress);
        $this->assertArrayHasKey('items_skipped', $progress);
        $this->assertArrayHasKey('progress_percentage', $progress);
        $this->assertArrayHasKey('is_cancelled', $progress);
    }

    public function test_complete_sync_calls_status_service(): void
    {
        $completedStatus = Mockery::mock(SyncStatus::class);

        $this->syncStatusService
            ->shouldReceive('completeSync')
            ->once()
            ->andReturn($completedStatus);

        $agent = $this->makeAgent([], true);

        $reflection = new \ReflectionClass($agent);
        $prop = $reflection->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($agent, $this->status);

        $result = $agent->exposeCompleteSync();

        $this->assertSame($completedStatus, $result);
    }

    public function test_fail_sync_calls_status_service(): void
    {
        $failedStatus = Mockery::mock(SyncStatus::class);

        $this->syncStatusService
            ->shouldReceive('failSync')
            ->once()
            ->with($this->status, 'Test failure', null)
            ->andReturn($failedStatus);

        $agent = $this->makeAgent([], true);

        $reflection = new \ReflectionClass($agent);
        $prop = $reflection->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($agent, $this->status);

        $result = $agent->exposeFailSync('Test failure');

        $this->assertSame($failedStatus, $result);
    }

    public function test_is_cancellation_requested_delegates_to_service(): void
    {
        $this->syncStatusService
            ->shouldReceive('isCancellationRequested')
            ->with(1)
            ->once()
            ->andReturn(true);

        $agent = $this->makeAgent([], true);

        $reflection = new \ReflectionClass($agent);
        $prop = $reflection->getProperty('status');
        $prop->setAccessible(true);
        $prop->setValue($agent, $this->status);

        $this->assertTrue($agent->exposeIsCancellationRequested());
    }

    public function test_get_batch_returns_batch(): void
    {
        $agent = $this->makeAgent();
        $this->assertSame($this->batch, $agent->getBatch());
    }

    public function test_get_status_returns_null_before_initialization(): void
    {
        $agent = $this->makeAgent();
        $this->assertNull($agent->getStatus());
    }
}
