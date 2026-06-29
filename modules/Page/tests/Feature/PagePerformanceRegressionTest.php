<?php

namespace Modules\Page\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PagePerformanceRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'page.view', 'guard_name' => 'web']);
    }

    public function test_get_pages_does_not_load_content_column(): void
    {
        Page::factory()->create([
            'title' => 'Test Page',
            'content' => '<p>Very long content that should not be loaded in listings</p>',
            'status' => PageStatus::Published->value,
        ]);

        $service = app(PageService::class);
        $pages = $service->getPages([]);

        $page = $pages->first();

        $this->assertNotNull($page);
        $this->assertArrayNotHasKey('content', $page->getAttributes());
    }

    public function test_get_trashed_pages_does_not_load_content_column(): void
    {
        Page::factory()->create([
            'title' => 'Trashed Page',
            'content' => '<p>Very long content that should not be loaded in listings</p>',
        ])->delete();

        $service = app(PageService::class);
        $pages = $service->getTrashedPages([]);

        $page = $pages->first();

        $this->assertNotNull($page);
        $this->assertArrayNotHasKey('content', $page->getAttributes());
    }

    public function test_homepage_does_not_trigger_n_plus_one_on_translations(): void
    {
        Page::factory()->published()->create([
            'template' => 'homepage',
            'title' => 'Home',
        ]);

        DB::enableQueryLog();

        $this->get(route('page.home'))->assertOk();

        $queries = DB::getQueryLog();

        // Expect: pages query + translations query + localeModel query + settings queries
        // Should be well under 10 queries total.
        $this->assertLessThan(
            10,
            count($queries),
            'Homepage generated too many queries (possible N+1): '.collect($queries)->pluck('query')->implode("\n")
        );
    }
}
