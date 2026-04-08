<?php

namespace Modules\Page\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageView;
use Modules\Page\Services\PageAnalyticsService;
use Tests\TestCase;

class PageAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PageAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PageAnalyticsService;
    }

    // =========================================================================
    // viewsByDay
    // =========================================================================

    public function test_views_by_day_returns_empty_array_when_no_views(): void
    {
        $page = Page::factory()->create();

        $result = $this->service->viewsByDay($page, 30);

        $this->assertEmpty($result);
    }

    public function test_views_by_day_groups_by_date(): void
    {
        $page = Page::factory()->create();

        PageView::factory()->forPage($page)->onDate(now()->subDays(2)->toDateString())->count(3)->create();
        PageView::factory()->forPage($page)->onDate(now()->subDays(1)->toDateString())->count(2)->create();

        $result = $this->service->viewsByDay($page, 30);

        $this->assertCount(2, $result);
        $this->assertEquals(3, $result[0]['views']);
        $this->assertEquals(2, $result[1]['views']);
    }

    public function test_views_by_day_excludes_views_outside_the_date_range(): void
    {
        $page = Page::factory()->create();

        // 60 days ago — outside 30-day window
        PageView::factory()->forPage($page)->onDate(now()->subDays(60)->toDateString())->create();
        // 5 days ago — inside
        PageView::factory()->forPage($page)->onDate(now()->subDays(5)->toDateString())->create();

        $result = $this->service->viewsByDay($page, 30);

        $this->assertCount(1, $result);
    }

    public function test_views_by_day_result_has_correct_keys(): void
    {
        $page = Page::factory()->create();

        PageView::factory()->forPage($page)->onDate(now()->subDay()->toDateString())->create();

        $result = $this->service->viewsByDay($page, 7);

        $this->assertArrayHasKey('date', $result[0]);
        $this->assertArrayHasKey('views', $result[0]);
        $this->assertIsInt($result[0]['views']);
    }

    // =========================================================================
    // summary
    // =========================================================================

    public function test_summary_returns_zeroes_when_no_views(): void
    {
        $page = Page::factory()->create();

        $result = $this->service->summary($page, 30);

        $this->assertEquals(0, $result['total_views']);
        $this->assertEquals(0, $result['unique_visitors']);
        $this->assertEquals(0.0, $result['avg_daily']);
    }

    public function test_summary_counts_total_views_correctly(): void
    {
        $page = Page::factory()->create();

        PageView::factory()->forPage($page)->onDate(now()->toDateString())->count(5)->create();

        $result = $this->service->summary($page, 30);

        $this->assertEquals(5, $result['total_views']);
    }

    public function test_summary_counts_unique_visitors_by_ip_hash(): void
    {
        $page = Page::factory()->create();

        $sharedIpHash = hash('sha256', '10.0.0.1');
        $today = now()->toDateString();

        // 2 rows with same ip_hash (1 unique) + 1 row with different ip_hash
        PageView::factory()->forPage($page)->onDate($today)->create(['ip_hash' => $sharedIpHash]);
        PageView::factory()->forPage($page)->onDate($today)->create(['ip_hash' => $sharedIpHash]);
        PageView::factory()->forPage($page)->onDate($today)->create(['ip_hash' => hash('sha256', '10.0.0.2')]);

        $result = $this->service->summary($page, 30);

        $this->assertEquals(3, $result['total_views']);
        $this->assertEquals(2, $result['unique_visitors']);
    }

    public function test_summary_calculates_avg_daily(): void
    {
        $page = Page::factory()->create();

        // Place all views on today so they are guaranteed inside the window
        PageView::factory()->forPage($page)->onDate(now()->toDateString())->count(30)->create();

        $result = $this->service->summary($page, 30);

        $this->assertEquals(1.0, $result['avg_daily']);
    }

    // =========================================================================
    // topReferrers
    // =========================================================================

    public function test_top_referrers_excludes_null_referrer_domains(): void
    {
        $page = Page::factory()->create();
        $today = now()->toDateString();

        PageView::factory()->forPage($page)->withoutReferrer()->onDate($today)->count(5)->create();
        PageView::factory()->forPage($page)->withReferrer('https://google.com/search')->onDate($today)->count(2)->create();

        $result = $this->service->topReferrers($page, 30);

        $this->assertCount(1, $result);
        $this->assertEquals('google.com', $result[0]['domain']);
        $this->assertEquals(2, $result[0]['views']);
    }

    public function test_top_referrers_orders_by_views_descending(): void
    {
        $page = Page::factory()->create();
        $today = now()->toDateString();

        PageView::factory()->forPage($page)->withReferrer('https://twitter.com')->onDate($today)->count(1)->create();
        PageView::factory()->forPage($page)->withReferrer('https://google.com/q=test')->onDate($today)->count(5)->create();

        $result = $this->service->topReferrers($page, 30);

        $this->assertEquals('google.com', $result[0]['domain']);
        $this->assertEquals('twitter.com', $result[1]['domain']);
    }

    public function test_top_referrers_returns_empty_array_when_no_referrers(): void
    {
        $page = Page::factory()->create();

        PageView::factory()->forPage($page)->withoutReferrer()->onDate(now()->toDateString())->count(3)->create();

        $result = $this->service->topReferrers($page, 30);

        $this->assertEmpty($result);
    }

    // =========================================================================
    // viewsByDevice
    // =========================================================================

    public function test_views_by_device_returns_counts_per_device_type(): void
    {
        $page = Page::factory()->create();
        $today = now()->toDateString();

        PageView::factory()->forPage($page)->desktop()->onDate($today)->count(4)->create();
        PageView::factory()->forPage($page)->mobile()->onDate($today)->count(3)->create();
        PageView::factory()->forPage($page)->tablet()->onDate($today)->count(1)->create();

        $result = $this->service->viewsByDevice($page, 30);

        $byDevice = collect($result)->keyBy('device');

        $this->assertEquals(4, $byDevice['desktop']['views']);
        $this->assertEquals(3, $byDevice['mobile']['views']);
        $this->assertEquals(1, $byDevice['tablet']['views']);
    }

    public function test_views_by_device_only_includes_devices_with_views(): void
    {
        $page = Page::factory()->create();

        PageView::factory()->forPage($page)->desktop()->onDate(now()->toDateString())->count(2)->create();
        // no mobile or tablet views

        $result = $this->service->viewsByDevice($page, 30);

        $this->assertCount(1, $result);
        $this->assertEquals('desktop', $result[0]['device']);
    }

    public function test_views_by_device_result_has_correct_keys(): void
    {
        $page = Page::factory()->create();

        PageView::factory()->forPage($page)->mobile()->onDate(now()->toDateString())->create();

        $result = $this->service->viewsByDevice($page, 30);

        $this->assertArrayHasKey('device', $result[0]);
        $this->assertArrayHasKey('views', $result[0]);
        $this->assertIsInt($result[0]['views']);
    }

    // =========================================================================
    // topPages
    // =========================================================================

    public function test_top_pages_orders_by_views_descending(): void
    {
        $pageA = Page::factory()->create();
        $pageB = Page::factory()->create();

        // Pin dates to today to guarantee they fall within any window
        PageView::factory()->forPage($pageA)->onDate(now()->toDateString())->count(10)->create();
        PageView::factory()->forPage($pageB)->onDate(now()->toDateString())->count(3)->create();

        $result = $this->service->topPages(30);

        $this->assertEquals($pageA->id, $result[0]['page_id']);
        $this->assertEquals(10, $result[0]['views']);
        $this->assertEquals($pageB->id, $result[1]['page_id']);
    }

    public function test_top_pages_returns_title_and_slug(): void
    {
        $page = Page::factory()->create(['title' => 'Mi Pagina', 'slug' => 'mi-pagina']);

        PageView::factory()->forPage($page)->count(2)->create();

        $result = $this->service->topPages(30);

        $this->assertEquals('Mi Pagina', $result[0]['title']);
        $this->assertEquals('mi-pagina', $result[0]['slug']);
    }
}
