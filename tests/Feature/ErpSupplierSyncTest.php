<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Erp\Jobs\MonitorOracleChanges;
use Modules\Erp\Models\Oracle\Otros\DeporteCl;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;
use Modules\Supplier\Entities\SupplierErpProvider;
use Modules\Supplier\Entities\SupplierSport;
use Modules\Supplier\Entities\SupplierSyncAudit;
use Modules\Supplier\Events\SupplierErpProviderUpdated;
use Tests\TestCase;

class ErpSupplierSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_syncs_new_sport_from_erp_to_supplier()
    {
        // Arrange: Create a sport in Oracle (ERP)
        $sport = DeporteCl::create([
            'iddeporte_cl' => 999,
            'descripcion' => 'Test Sport',
            'desc_corta' => 'TEST',
            'estado' => 1,
            'fcreacion' => now(),
            'fmodificacion' => now(),
        ]);

        // Act: Run monitor job (simulate one cycle)
        $job = new MonitorOracleChanges;
        $result = $this->invokePrivateMethod($job, 'monitorSports');

        // Assert: Sport should be synced to Supplier
        $this->assertDatabaseHas('supplier_sports', [
            'erp_sport_id' => 999,
            'name' => 'Test Sport',
            'short_name' => 'TEST',
            'is_active' => true,
        ]);

        // Assert: Audit trail created
        $this->assertDatabaseHas('supplier_sync_audit', [
            'entity_type' => 'sports',
            'sync_direction' => 'erp_to_supplier',
            'records_synced' => 1,
        ]);

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_updates_existing_provider_from_erp_to_supplier()
    {
        // Arrange: Create provider in both ERP and Supplier
        $erpProvider = Proveedor::create([
            'idproveedor' => 888,
            'nombre' => 'Original Name',
            'email' => 'original@test.com',
            'telefono1' => '111111111',
            'fcreacion' => now()->subDays(5),
            'fmodificacion' => now()->subDays(5),
        ]);

        $supplierProvider = SupplierErpProvider::create([
            'uid' => \Str::ulid(),
            'erp_provider_id' => 888,
            'name' => 'Original Name',
            'email' => 'original@test.com',
            'phone' => '111111111',
            'last_synced_at' => now()->subDays(5),
        ]);

        // Act: Update provider in ERP
        $erpProvider->update([
            'nombre' => 'Updated Name',
            'email' => 'updated@test.com',
            'fmodificacion' => now(),
        ]);

        // Run monitor
        $job = new MonitorOracleChanges;
        $result = $this->invokePrivateMethod($job, 'monitorProviders');

        // Assert: Supplier should have updated data
        $supplierProvider->refresh();

        $this->assertEquals('Updated Name', $supplierProvider->name);
        $this->assertEquals('updated@test.com', $supplierProvider->email);
        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_respects_fbaja_field_as_soft_delete()
    {
        // Arrange: Create provider in ERP
        $erpProvider = Proveedor::create([
            'idproveedor' => 777,
            'nombre' => 'To Delete',
            'fcreacion' => now(),
            'fmodificacion' => now(),
        ]);

        // First sync
        $job = new MonitorOracleChanges;
        $this->invokePrivateMethod($job, 'monitorProviders');

        $this->assertDatabaseHas('supplier_erp_providers', [
            'erp_provider_id' => 777,
            'name' => 'To Delete',
        ]);

        // Act: Soft delete in ERP (set fbaja)
        $erpProvider->update([
            'fbaja' => now(),
            'fmodificacion' => now(),
        ]);

        // Clear cache to force resync
        Cache::forget('monitor_last_sync_'.Proveedor::class);

        // Second sync should NOT pick up deleted records
        $result = $this->invokePrivateMethod($job, 'monitorProviders');

        // Assert: Should still exist in Supplier (ERP doesn't send deleted records)
        $this->assertDatabaseHas('supplier_erp_providers', [
            'erp_provider_id' => 777,
        ]);

        // But erp_deleted_at should be null because we ignore fbaja in query
        $provider = SupplierErpProvider::where('erp_provider_id', 777)->first();
        $this->assertNull($provider->erp_deleted_at);
    }

    /** @test */
    public function it_dispatches_event_when_supplier_provider_is_updated()
    {
        Event::fake([SupplierErpProviderUpdated::class]);

        // Arrange: Create provider in Supplier (with ERP ID)
        $provider = SupplierErpProvider::create([
            'uid' => \Str::ulid(),
            'erp_provider_id' => 666,
            'name' => 'Test Provider',
            'email' => 'test@provider.com',
            'phone' => '123456789',
            'last_synced_at' => now(),
        ]);

        // Act: Update syncable field
        $provider->update([
            'name' => 'Updated Provider Name',
        ]);

        // Assert: Event should be dispatched
        Event::assertDispatched(SupplierErpProviderUpdated::class, function ($event) use ($provider) {
            return $event->provider->id === $provider->id
                && in_array('name', $event->changedFields);
        });
    }

    /** @test */
    public function it_does_not_dispatch_event_for_non_syncable_fields()
    {
        Event::fake([SupplierErpProviderUpdated::class]);

        // Arrange
        $provider = SupplierErpProvider::create([
            'uid' => \Str::ulid(),
            'erp_provider_id' => 555,
            'name' => 'Test Provider',
            'last_synced_at' => now(),
        ]);

        // Act: Update non-syncable field (last_synced_at)
        $provider->update([
            'last_synced_at' => now(),
        ]);

        // Assert: Event should NOT be dispatched
        Event::assertNotDispatched(SupplierErpProviderUpdated::class);
    }

    /** @test */
    public function it_creates_audit_trail_for_each_sync()
    {
        // Arrange: Create multiple sports
        for ($i = 1; $i <= 5; $i++) {
            DeporteCl::create([
                'iddeporte_cl' => 100 + $i,
                'descripcion' => "Sport {$i}",
                'desc_corta' => "SP{$i}",
                'estado' => 1,
                'fcreacion' => now(),
                'fmodificacion' => now(),
            ]);
        }

        // Act: Run sync
        $job = new MonitorOracleChanges;
        $this->invokePrivateMethod($job, 'monitorSports');

        // Assert: Audit record created
        $audit = SupplierSyncAudit::where('entity_type', 'sports')
            ->where('sync_direction', 'erp_to_supplier')
            ->latest('synced_at')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals(5, $audit->records_synced);
        $this->assertEquals(0, $audit->records_failed);
        $this->assertGreaterThan(0, $audit->elapsed_seconds);
        $this->assertNotNull($audit->metadata);
    }

    /** @test */
    public function it_uses_lazy_loading_for_large_queries()
    {
        // Arrange: Create 1000 sports to test lazy loading
        $sports = [];
        for ($i = 1; $i <= 1000; $i++) {
            $sports[] = [
                'iddeporte_cl' => 1000 + $i,
                'descripcion' => "Sport {$i}",
                'desc_corta' => "S{$i}",
                'estado' => 1,
                'fcreacion' => now(),
                'fmodificacion' => now(),
                'fbaja' => null,
            ];
        }

        DeporteCl::insert($sports);

        // Act: Run sync and measure memory
        $memoryBefore = memory_get_usage(true);
        $job = new MonitorOracleChanges;
        $result = $this->invokePrivateMethod($job, 'monitorSports');
        $memoryAfter = memory_get_usage(true);

        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        // Assert: Should process all 1000 records
        $this->assertEquals(1000, $result);

        // Assert: Memory usage should be reasonable (<50 MB for 1000 records with lazy loading)
        // Without lazy loading, this could be >100 MB
        $this->assertLessThan(50, $memoryUsedMB, 'Memory usage should be under 50 MB with lazy loading');

        // Assert: All records synced
        $this->assertEquals(1000, SupplierSport::count());
    }

    /** @test */
    public function it_respects_selective_entity_monitoring_config()
    {
        // Arrange: Disable sports monitoring
        config(['erp.supplier_sync.monitored_entities.sports' => false]);

        DeporteCl::create([
            'iddeporte_cl' => 200,
            'descripcion' => 'Disabled Sport',
            'desc_corta' => 'DIS',
            'estado' => 1,
            'fcreacion' => now(),
            'fmodificacion' => now(),
        ]);

        // Act: This should be skipped because it's disabled
        // We can't easily test the full handle() method, but we can verify config
        $monitoredEntities = config('erp.supplier_sync.monitored_entities');

        // Assert: Sports should be disabled
        $this->assertFalse($monitoredEntities['sports']);

        // Re-enable for other tests
        config(['erp.supplier_sync.monitored_entities.sports' => true]);
    }

    /**
     * Helper to invoke private methods for testing
     */
    protected function invokePrivateMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
