<?php

namespace Modules\HelpdeskLivechat\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\FeedCatalogDriver;
use Tests\TestCase;

/**
 * El driver de feed no toca la BD: descarga un JSON remoto (mockeado con
 * Http::fake), lo cachea y sirve búsqueda/recuperación en memoria.
 */
class FeedCatalogDriverTest extends TestCase
{
    private const FEED_URL = 'https://shop.example/feed.json';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function fakeFeed(array $products): void
    {
        Http::fake([self::FEED_URL => Http::response($products, 200)]);
    }

    private function sampleProducts(): array
    {
        return [
            ['id' => '1', 'title' => 'Zapatillas running Nike', 'price' => 90, 'description' => 'para correr'],
            ['id' => '2', 'title' => 'Botas de montaña', 'price' => 120, 'description' => 'trekking'],
            ['id' => '3', 'title' => 'Zapatillas casual', 'price' => 60, 'available' => false],
        ];
    }

    public function test_search_matches_by_title_keyword(): void
    {
        $this->fakeFeed($this->sampleProducts());

        $driver = new FeedCatalogDriver(self::FEED_URL);
        $results = $driver->search('zapatillas', 6);

        $this->assertNotEmpty($results);
        $this->assertContainsOnlyInstancesOf(CatalogProduct::class, $results);
        foreach ($results as $product) {
            $this->assertStringContainsStringIgnoringCase('zapatillas', $product->title);
        }
    }

    public function test_search_ranks_exact_id_match_first(): void
    {
        $this->fakeFeed($this->sampleProducts());

        $driver = new FeedCatalogDriver(self::FEED_URL);
        $results = $driver->search('2', 6);

        $this->assertNotEmpty($results);
        $this->assertSame('2', $results[0]->id);
    }

    public function test_search_ranks_available_before_unavailable_on_tie(): void
    {
        $this->fakeFeed($this->sampleProducts());

        $driver = new FeedCatalogDriver(self::FEED_URL);
        $results = $driver->search('zapatillas', 6);

        // Ambas "zapatillas" empatan a puntuación por título; la disponible (id 1)
        // debe ir antes que la no disponible (id 3).
        $ids = array_map(static fn (CatalogProduct $p): string => $p->id, $results);
        $this->assertLessThan(array_search('3', $ids, true), array_search('1', $ids, true));
    }

    public function test_search_respects_limit(): void
    {
        $this->fakeFeed($this->sampleProducts());

        $driver = new FeedCatalogDriver(self::FEED_URL);
        $this->assertCount(1, $driver->search('zapatillas', 1));
    }

    public function test_find_returns_product_by_id_or_null(): void
    {
        $this->fakeFeed($this->sampleProducts());

        $driver = new FeedCatalogDriver(self::FEED_URL);
        $this->assertSame('Botas de montaña', $driver->find('2')?->title);
        $this->assertNull($driver->find('999'));
    }

    public function test_empty_query_returns_no_results(): void
    {
        $this->fakeFeed($this->sampleProducts());

        $driver = new FeedCatalogDriver(self::FEED_URL);
        $this->assertSame([], $driver->search('   ', 6));
    }

    public function test_failed_feed_fetch_degrades_to_empty(): void
    {
        Http::fake([self::FEED_URL => Http::response('', 500)]);

        $driver = new FeedCatalogDriver(self::FEED_URL);
        $this->assertSame([], $driver->search('zapatillas', 6));
        $this->assertNull($driver->find('1'));
    }

    public function test_feed_is_cached_and_not_refetched(): void
    {
        $this->fakeFeed($this->sampleProducts());

        $driver = new FeedCatalogDriver(self::FEED_URL);
        $driver->search('zapatillas', 6);
        $driver->search('botas', 6);
        $driver->find('1');

        // Una sola descarga del feed pese a múltiples consultas.
        Http::assertSentCount(1);
    }
}
