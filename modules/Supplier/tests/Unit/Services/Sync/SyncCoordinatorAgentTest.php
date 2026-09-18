<?php

namespace Modules\Supplier\Tests\Unit\Services\Sync;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\Supplier\Jobs\SyncCategoriesJob;
use Modules\Supplier\Jobs\SyncPricesJob;
use Modules\Supplier\Jobs\SyncProductsJob;
use Modules\Supplier\Jobs\SyncProvidersJob;
use Modules\Supplier\Services\SyncCoordinatorAgent;
use Modules\Supplier\Services\SyncStatusService;
use Tests\TestCase;

class SyncCoordinatorAgentTest extends TestCase
{
    use DatabaseTransactions;

    private SyncStatusService $syncStatusService;

    private SyncCoordinatorAgent $coordinator;

    protected function setUp(): void
    {
        parent::setUp();
        activity()->disableLogging();

        $this->syncStatusService = $this->app->make(SyncStatusService::class);
        $this->coordinator = new SyncCoordinatorAgent($this->syncStatusService);
    }

    public function test_coordinate_sync_with_product_type_dispatches_product_job(): void
    {
        Queue::fake();

        $result = $this->coordinator->coordinateSync(
            syncType: 'product',
            triggeredBy: 'test',
        );

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['batch_id']);
        $this->assertContains('product', $result['sync_types']);

        Queue::assertPushedOn('sync', SyncProductsJob::class);
    }

    public function test_coordinate_sync_with_all_dispatches_four_job_types(): void
    {
        Queue::fake();

        $result = $this->coordinator->coordinateSync(
            syncType: 'all',
            triggeredBy: 'test',
        );

        $this->assertTrue($result['success']);
        $this->assertCount(4, $result['sync_types']);
        $this->assertSame(4, $result['agents_started']);

        Queue::assertPushedOn('sync', SyncProductsJob::class);
        Queue::assertPushedOn('sync', SyncCategoriesJob::class);
        Queue::assertPushedOn('sync', SyncPricesJob::class);
        Queue::assertPushedOn('sync', SyncProvidersJob::class);
    }

    public function test_coordinate_sync_creates_coordinator_batch(): void
    {
        Queue::fake();

        $result = $this->coordinator->coordinateSync(
            syncType: 'category',
            triggeredBy: 'manual',
        );

        $this->assertTrue($result['success']);

        $batch = $this->coordinator->getCoordinatorBatch();
        $this->assertNotNull($batch);
        $this->assertSame('category', $batch->sync_type);
        $this->assertSame('manual', $batch->triggered_by);
    }

    public function test_determine_sync_types_translates_all_to_four_types(): void
    {
        Queue::fake();

        $result = $this->coordinator->coordinateSync(syncType: 'all', triggeredBy: 'test');

        $this->assertSame(['product', 'category', 'price', 'provider'], $result['sync_types']);
    }

    public function test_determine_sync_types_returns_single_type_for_product(): void
    {
        Queue::fake();

        $result = $this->coordinator->coordinateSync(syncType: 'product', triggeredBy: 'test');

        $this->assertSame(['product'], $result['sync_types']);
    }

    public function test_determine_sync_types_returns_single_type_for_category(): void
    {
        Queue::fake();

        $result = $this->coordinator->coordinateSync(syncType: 'category', triggeredBy: 'test');

        $this->assertSame(['category'], $result['sync_types']);
    }

    public function test_coordinate_sync_returns_failure_for_unknown_type(): void
    {
        Queue::fake();

        $result = $this->coordinator->coordinateSync(
            syncType: 'unknown_type_xyz',
            triggeredBy: 'test',
        );

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['agents_started']);
    }

    public function test_record_agent_result_stores_result(): void
    {
        $this->coordinator->recordAgentResult('product', [
            'success' => true,
            'items_processed' => 10,
        ]);

        $results = $this->coordinator->getAgentResults();

        $this->assertArrayHasKey('product', $results);
        $this->assertTrue($results['product']['success']);
        $this->assertSame(10, $results['product']['items_processed']);
    }

    public function test_handle_batch_completion_returns_started_agents_count(): void
    {
        Queue::fake();

        $result = $this->coordinator->coordinateSync(
            syncType: 'price',
            triggeredBy: 'schedule',
        );

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['agents_started']);

        Queue::assertPushedOn('sync', SyncPricesJob::class);
    }
}
