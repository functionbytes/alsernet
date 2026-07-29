<?php

namespace Modules\Supplier\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\Supplier\Database\Factories\Supplier\SupplierFactory;
use Modules\Supplier\Database\Factories\Sync\SyncBatchFactory;
use Modules\Supplier\Jobs\SyncCategoriesJob;
use Modules\Supplier\Jobs\SyncModelsJob;
use Modules\Supplier\Jobs\SyncPricesJob;
use Modules\Supplier\Jobs\SyncProductsJob;
use Modules\Supplier\Jobs\SyncProvidersJob;
use Tests\TestCase;

class SyncJobsDispatchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        activity()->disableLogging();
        Queue::fake();
    }

    public function test_sync_products_job_is_dispatched_on_sync_queue(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'pending',
        ]);

        SyncProductsJob::dispatch($batch)->onQueue('sync');

        Queue::assertPushedOn('sync', SyncProductsJob::class);
    }

    public function test_sync_categories_job_is_dispatched_on_sync_queue(): void
    {
        $batch = SyncBatchFactory::new()->create([
            'sync_type' => 'category',
            'status' => 'pending',
        ]);

        SyncCategoriesJob::dispatch($batch)->onQueue('sync');

        Queue::assertPushedOn('sync', SyncCategoriesJob::class);
    }

    public function test_sync_prices_job_is_dispatched_on_sync_queue(): void
    {
        $batch = SyncBatchFactory::new()->create([
            'sync_type' => 'price',
            'status' => 'pending',
        ]);

        SyncPricesJob::dispatch($batch)->onQueue('sync');

        Queue::assertPushedOn('sync', SyncPricesJob::class);
    }

    public function test_sync_providers_job_is_dispatched_on_sync_queue(): void
    {
        $batch = SyncBatchFactory::new()->create([
            'sync_type' => 'provider',
            'status' => 'pending',
        ]);

        SyncProvidersJob::dispatch($batch)->onQueue('sync');

        Queue::assertPushedOn('sync', SyncProvidersJob::class);
    }

    public function test_sync_products_job_carries_correct_batch(): void
    {
        $batch = SyncBatchFactory::new()->create([
            'sync_type' => 'product',
            'status' => 'pending',
        ]);

        SyncProductsJob::dispatch($batch, 42)->onQueue('sync');

        Queue::assertPushed(SyncProductsJob::class, function ($job) use ($batch) {
            return $job->batch->id === $batch->id;
        });
    }

    public function test_sync_products_job_with_supplier_id_carries_supplier(): void
    {
        $supplier = SupplierFactory::new()->create();
        $batch = SyncBatchFactory::new()->create([
            'supplier_id' => $supplier->id,
            'sync_type' => 'product',
            'status' => 'pending',
        ]);

        SyncProductsJob::dispatch($batch, $supplier->id)->onQueue('sync');

        Queue::assertPushed(SyncProductsJob::class, function ($job) use ($supplier) {
            return $job->supplierId === $supplier->id;
        });
    }

    public function test_multiple_sync_jobs_are_all_queued(): void
    {
        $batch1 = SyncBatchFactory::new()->create(['sync_type' => 'product', 'status' => 'pending']);
        $batch2 = SyncBatchFactory::new()->create(['sync_type' => 'category', 'status' => 'pending']);
        $batch3 = SyncBatchFactory::new()->create(['sync_type' => 'price', 'status' => 'pending']);

        SyncProductsJob::dispatch($batch1)->onQueue('sync');
        SyncCategoriesJob::dispatch($batch2)->onQueue('sync');
        SyncPricesJob::dispatch($batch3)->onQueue('sync');

        Queue::assertPushedOn('sync', SyncProductsJob::class);
        Queue::assertPushedOn('sync', SyncCategoriesJob::class);
        Queue::assertPushedOn('sync', SyncPricesJob::class);

        Queue::assertCount(3);
    }

    public function test_sync_batch_has_pending_status_when_dispatched(): void
    {
        $batch = SyncBatchFactory::new()->create([
            'sync_type' => 'product',
            'status' => 'pending',
        ]);

        $this->assertSame('pending', $batch->status);

        SyncProductsJob::dispatch($batch)->onQueue('sync');

        Queue::assertPushed(SyncProductsJob::class);

        // Batch status should still be pending since job hasn't run yet
        $batch->refresh();
        $this->assertSame('pending', $batch->status);
    }

    public function test_sync_models_job_defaults_to_filter_mode(): void
    {
        $batch = SyncBatchFactory::new()->create(['sync_type' => 'models', 'status' => 'pending']);

        SyncModelsJob::dispatch($batch)->onQueue('sync');

        Queue::assertPushedOn('sync', SyncModelsJob::class);
        Queue::assertPushed(SyncModelsJob::class, function (SyncModelsJob $job) {
            return $this->jobProp($job, 'mode') === 'filter'
                && $this->jobProp($job, 'erpProviderId') === null;
        });
    }

    public function test_sync_models_job_keeps_legacy_mode_when_requested(): void
    {
        $batch = SyncBatchFactory::new()->create(['sync_type' => 'models', 'status' => 'pending']);

        SyncModelsJob::dispatch($batch, 77, 10, false, 'legacy')->onQueue('sync');

        Queue::assertPushed(SyncModelsJob::class, function (SyncModelsJob $job) {
            return $this->jobProp($job, 'mode') === 'legacy'
                && $this->jobProp($job, 'erpProviderId') === 77
                && $this->jobProp($job, 'limit') === 10;
        });
    }

    public function test_sync_models_job_accepts_named_mode_argument(): void
    {
        $batch = SyncBatchFactory::new()->create(['sync_type' => 'models', 'status' => 'pending']);

        SyncModelsJob::dispatch($batch, erpProviderId: 5, mode: 'legacy')->onQueue('sync');

        Queue::assertPushed(SyncModelsJob::class, function (SyncModelsJob $job) {
            return $this->jobProp($job, 'mode') === 'legacy'
                && $this->jobProp($job, 'erpProviderId') === 5;
        });
    }

    private function jobProp(object $job, string $name): mixed
    {
        $property = new \ReflectionProperty($job, $name);
        $property->setAccessible(true);

        return $property->getValue($job);
    }
}
