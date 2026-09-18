<?php

namespace Modules\Supplier\Tests\Unit\Services\Sync;

use Modules\Supplier\Jobs\SyncCategoriesJob;
use Modules\Supplier\Jobs\SyncModelsJob;
use Modules\Supplier\Jobs\SyncPricesJob;
use Modules\Supplier\Jobs\SyncProductsJob;
use Modules\Supplier\Jobs\SyncProvidersJob;
use Modules\Supplier\Services\SyncCoordinatorAgent;
use Modules\Supplier\Services\SyncStatusService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pure-unit coverage for the sync-type → job-class and sync-type expansion
 * maps. Avoids the database so it runs even when the test schema is unavailable.
 */
class SyncCoordinatorAgentJobMappingTest extends TestCase
{
    private SyncCoordinatorAgent $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $statusService = $this->createMock(SyncStatusService::class);
        $this->coordinator = new SyncCoordinatorAgent($statusService);
    }

    /**
     * @dataProvider jobClassProvider
     */
    public function test_get_sync_job_class_maps_each_type(string $syncType, string $expectedJobClass): void
    {
        $method = new ReflectionMethod(SyncCoordinatorAgent::class, 'getSyncJobClass');
        $method->setAccessible(true);

        $this->assertSame($expectedJobClass, $method->invoke($this->coordinator, $syncType));
    }

    public static function jobClassProvider(): array
    {
        return [
            'product' => ['product', SyncProductsJob::class],
            'category' => ['category', SyncCategoriesJob::class],
            'price' => ['price', SyncPricesJob::class],
            'provider' => ['provider', SyncProvidersJob::class],
            'model' => ['model', SyncModelsJob::class],
        ];
    }

    public function test_get_sync_job_class_throws_for_unknown_type(): void
    {
        $method = new ReflectionMethod(SyncCoordinatorAgent::class, 'getSyncJobClass');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);

        $method->invoke($this->coordinator, 'nonsense');
    }

    public function test_determine_sync_types_supports_model_aliases(): void
    {
        $method = new ReflectionMethod(SyncCoordinatorAgent::class, 'determineSyncTypes');
        $method->setAccessible(true);

        $this->assertSame(['model'], $method->invoke($this->coordinator, 'model'));
        $this->assertSame(['model'], $method->invoke($this->coordinator, 'models'));
    }

    public function test_determine_sync_types_all_excludes_model(): void
    {
        $method = new ReflectionMethod(SyncCoordinatorAgent::class, 'determineSyncTypes');
        $method->setAccessible(true);

        $this->assertSame(['product', 'category', 'price', 'provider'], $method->invoke($this->coordinator, 'all'));
    }
}
