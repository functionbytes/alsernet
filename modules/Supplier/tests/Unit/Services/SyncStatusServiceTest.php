<?php

namespace Modules\Supplier\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Supplier\Database\Factories\Supplier\SupplierFactory;
use Modules\Supplier\Database\Factories\Sync\SyncBatchFactory;
use Modules\Supplier\Database\Factories\Sync\SyncStatusFactory;
use Modules\Supplier\Models\Sync\SyncLog;
use Modules\Supplier\Models\Sync\SyncStatus;
use Modules\Supplier\Services\SyncStatusService;
use Tests\TestCase;

class SyncStatusServiceTest extends TestCase
{
    use DatabaseTransactions;

    private SyncStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        activity()->disableLogging();

        $this->service = $this->app->make(SyncStatusService::class);
    }

    public function test_start_sync_creates_status_record(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'pending',
        ]);

        $status = $this->service->startSync(
            batch: $batch,
            totalItems: 100,
            triggeredBy: 'test',
        );

        $this->assertInstanceOf(SyncStatus::class, $status);
        $this->assertSame(100, $status->total_items);
        $this->assertSame($batch->id, $status->batch_id);
    }

    public function test_start_sync_memoises_status_id(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'pending',
        ]);

        $status1 = $this->service->startSync(
            batch: $batch,
            totalItems: 50,
            triggeredBy: 'manual',
        );

        // Re-instantiate service should still query, but same instance should memoise
        $reflection = new \ReflectionClass($this->service);
        $prop = $reflection->getProperty('resolvedStatusIdByBatch');
        $prop->setAccessible(true);
        $memoised = $prop->getValue($this->service);

        $this->assertArrayHasKey($batch->id, $memoised);
        $this->assertSame($status1->id, $memoised[$batch->id]);
    }

    public function test_update_progress_updates_status_in_database(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'running',
        ]);

        $status = SyncStatusFactory::new()->running()->create([
            'batch_id' => $batch->id,
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'total_items' => 100,
            'synced_items' => 0,
        ]);

        $updated = $this->service->updateProgress(
            status: $status,
            syncedCount: 25,
            failedCount: 5,
            skippedCount: 2,
        );

        $this->assertSame(25, $updated->synced_items);
        $this->assertSame(5, $updated->failed_items);
        $this->assertSame(2, $updated->skipped_items);
    }

    public function test_update_progress_throttles_broadcasts(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'running',
        ]);

        $status = SyncStatusFactory::new()->running()->create([
            'batch_id' => $batch->id,
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'total_items' => 100,
            'synced_items' => 0,
        ]);

        // First call sets throttle key; subsequent call within throttle window should be skipped
        $this->service->updateProgress($status, 10, 0, 0);
        $throttleKey = "sync:broadcast:throttle:{$status->id}";

        // Throttle key should exist in cache after first update
        $this->assertTrue(Cache::has($throttleKey), 'Throttle key should be set after first updateProgress call');
    }

    public function test_is_cancellation_requested_returns_false_by_default(): void
    {
        $this->assertFalse($this->service->isCancellationRequested(999999));
    }

    public function test_request_cancellation_sets_flag(): void
    {
        $batchId = 12345;

        $this->service->requestCancellation($batchId);

        $this->assertTrue($this->service->isCancellationRequested($batchId));
    }

    public function test_log_action_creates_sync_log_record(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'running',
        ]);

        $status = SyncStatusFactory::new()->running()->create([
            'batch_id' => $batch->id,
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'total_items' => 50,
        ]);

        // Memoise the status id
        $reflection = new \ReflectionClass($this->service);
        $prop = $reflection->getProperty('resolvedStatusIdByBatch');
        $prop->setAccessible(true);
        $prop->setValue($this->service, [$batch->id => $status->id]);

        $log = $this->service->logAction(
            batch: $batch,
            entityType: 'product',
            entityId: 42,
            action: 'sync',
            result: 'success',
            message: 'Synced successfully',
            durationMs: 150,
        );

        $this->assertInstanceOf(SyncLog::class, $log);
        $this->assertSame($batch->id, $log->batch_id);
        $this->assertSame('product', $log->entity_type);
        $this->assertSame(42, $log->entity_id);
        $this->assertSame('success', $log->result);
    }

    public function test_log_action_does_not_cause_n_plus_one_on_repeated_calls(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'running',
        ]);

        $status = SyncStatusFactory::new()->running()->create([
            'batch_id' => $batch->id,
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'total_items' => 100,
        ]);

        // Prime the memoisation cache (simulates startSync having run)
        $reflection = new \ReflectionClass($this->service);
        $prop = $reflection->getProperty('resolvedStatusIdByBatch');
        $prop->setAccessible(true);
        $prop->setValue($this->service, [$batch->id => $status->id]);

        $queryCount = 0;
        \DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        for ($i = 0; $i < 10; $i++) {
            $this->service->logAction(
                batch: $batch,
                entityType: 'product',
                entityId: $i,
                action: 'sync',
                result: 'success',
            );
        }

        // Each logAction should only issue 1 INSERT query (no extra SELECT for status_id)
        // 10 logActions = at most 10 INSERT queries, not 20 (10 inserts + 10 selects)
        $this->assertLessThanOrEqual(15, $queryCount, 'Expected at most 15 queries for 10 logActions (no N+1)');
    }

    public function test_complete_sync_marks_status_as_completed(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'running',
        ]);

        $status = SyncStatusFactory::new()->running()->create([
            'batch_id' => $batch->id,
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'total_items' => 20,
            'synced_items' => 20,
        ]);

        $completed = $this->service->completeSync($status);

        $this->assertSame('completed', $completed->status);
        $this->assertNotNull($completed->completed_at);
    }

    public function test_fail_sync_marks_status_as_failed(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'running',
        ]);

        $status = SyncStatusFactory::new()->running()->create([
            'batch_id' => $batch->id,
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'total_items' => 20,
        ]);

        $failed = $this->service->failSync($status, 'Connection timeout', 'ERP_TIMEOUT');

        $this->assertSame('failed', $failed->status);
        $this->assertStringContainsString('Connection timeout', $failed->notes ?? '');
    }
}
