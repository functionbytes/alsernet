<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\HelpdeskPrestashop\Events\PsPriceDropped;
use Tests\TestCase;

/**
 * getCategories() cachea con una clave versionada (ps.categories.v{n}.{lang}).
 * Un cambio de catálogo (bajada de precio / vuelta de stock) sube la versión vía
 * el listener InvalidateCatalogCache, dejando huérfanas todas las variantes de
 * idioma de una vez.
 */
class CatalogCacheInvalidationTest extends TestCase
{
    public function test_price_dropped_event_bumps_the_catalog_cache_version(): void
    {
        Cache::forget('ps.catalog.version');

        PsPriceDropped::dispatch(['product_id' => 5]);

        $this->assertSame(2, (int) Cache::get('ps.catalog.version'), 'La bajada de precio debe invalidar el catálogo.');
    }

    public function test_back_in_stock_event_bumps_the_catalog_cache_version(): void
    {
        Cache::forget('ps.catalog.version');

        PsBackInStock::dispatch(['product_id' => 9]);
        PsBackInStock::dispatch(['product_id' => 9]);

        // Dos eventos → dos bumps (2, luego 3).
        $this->assertSame(3, (int) Cache::get('ps.catalog.version'));
    }
}
