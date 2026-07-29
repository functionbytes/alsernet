<?php

namespace Modules\Supplier\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Modules\Supplier\Database\Factories\SupplierProductPriceFactory;
use Modules\Supplier\Events\SupplierProductPriceChanged;
use Modules\Supplier\Models\SupplierProductPrice;
use Tests\TestCase;

class SupplierProductPriceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('supplier_product_prices')) {
            $this->markTestSkipped('Table supplier_product_prices is not present in the test database.');
        }
    }

    public function test_factory_creates_persisted_row(): void
    {
        $price = SupplierProductPriceFactory::new()->create();

        $this->assertDatabaseHas('supplier_product_prices', ['id' => $price->id]);
        $this->assertNotEmpty($price->uid);
        $this->assertSame(26, strlen($price->uid));
    }

    public function test_calculate_final_cost_applies_chained_discounts(): void
    {
        $price = SupplierProductPriceFactory::new()->make([
            'cost' => 100,
            'discount1' => 10,
            'discount2' => 5,
        ]);

        // 100 * 0.9 * 0.95 = 85.5
        $this->assertSame(85.5, $price->calculateFinalCost());
    }

    public function test_calculate_final_cost_with_zero_discounts_returns_cost(): void
    {
        $price = SupplierProductPriceFactory::new()->make([
            'cost' => 250.5,
            'discount1' => 0,
            'discount2' => 0,
        ]);

        $this->assertSame(250.5, $price->calculateFinalCost());
    }

    public function test_scope_active_filters_inactive_rows(): void
    {
        SupplierProductPriceFactory::new()->create(['is_active' => false]);
        SupplierProductPriceFactory::new()->create(['is_active' => true]);

        $count = SupplierProductPrice::active()->count();
        // We can only assert "at least 1" since DatabaseTransactions still
        // sees other rows from the same transaction; instead assert the
        // scope filter is correct:
        $this->assertSame(0, SupplierProductPrice::active()->where('is_active', false)->count());
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_scope_current_filters_to_is_current(): void
    {
        SupplierProductPriceFactory::new()->create(['is_current' => false]);
        $current = SupplierProductPriceFactory::new()->current()->create();

        $this->assertTrue(SupplierProductPrice::current()->whereKey($current->id)->exists());
    }

    public function test_observer_dispatches_change_event_when_syncable_field_modified(): void
    {
        Event::fake([SupplierProductPriceChanged::class]);
        Cache::flush();

        $price = SupplierProductPriceFactory::new()->create([
            'cost' => 100,
            'is_active' => true,
        ]);

        $price->update(['cost' => 120]);

        Event::assertDispatched(SupplierProductPriceChanged::class);
    }

    public function test_observer_does_not_dispatch_for_non_syncable_field(): void
    {
        Event::fake([SupplierProductPriceChanged::class]);
        Cache::flush();

        $price = SupplierProductPriceFactory::new()->create();

        $price->update(['last_synced_at' => now()]);

        Event::assertNotDispatched(SupplierProductPriceChanged::class);
    }

    public function test_observer_skips_when_sync_in_progress_cache_flag_present(): void
    {
        Event::fake([SupplierProductPriceChanged::class]);

        $price = SupplierProductPriceFactory::new()->create();
        Cache::put("sync_in_progress_price_{$price->id}", true, now()->addMinute());

        $price->update(['cost' => 999]);

        Event::assertNotDispatched(SupplierProductPriceChanged::class);

        Cache::forget("sync_in_progress_price_{$price->id}");
    }
}
