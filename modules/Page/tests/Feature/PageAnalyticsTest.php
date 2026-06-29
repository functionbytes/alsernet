<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageView;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure page permissions exist so policy checks don't throw
        Permission::firstOrCreate(['name' => 'page.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'page.update', 'guard_name' => 'web']);
    }

    private function createUserWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }

    // =========================================================================
    // Authorization
    // =========================================================================

    public function test_guest_cannot_view_page_analytics(): void
    {
        $page = Page::factory()->create();

        $this->getJson(route('pages.analytics.show', $page))
            ->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_view_analytics(): void
    {
        $user = User::factory()->create();
        $page = Page::factory()->create();

        $this->actingAs($user)
            ->getJson(route('pages.analytics.show', $page))
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_page_analytics(): void
    {
        $user = $this->createUserWithPermission('page.update');
        $page = Page::factory()->create();

        $this->actingAs($user)
            ->getJson(route('pages.analytics.show', $page))
            ->assertOk();
    }

    // =========================================================================
    // Response structure
    // =========================================================================

    public function test_analytics_response_has_correct_structure(): void
    {
        $user = $this->createUserWithPermission('page.update');
        $page = Page::factory()->create();

        $this->actingAs($user)
            ->getJson(route('pages.analytics.show', $page))
            ->assertOk()
            ->assertJsonStructure([
                'views_by_day',
                'views_by_device',
                'top_referrers',
                'summary' => [
                    'total_views',
                    'unique_visitors',
                    'avg_daily',
                ],
            ]);
    }

    public function test_analytics_returns_empty_data_when_no_views(): void
    {
        $user = $this->createUserWithPermission('page.update');
        $page = Page::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('pages.analytics.show', $page))
            ->assertOk();

        $this->assertEquals(0, $response->json('summary.total_views'));
        $this->assertEquals(0, $response->json('summary.unique_visitors'));
        $this->assertEmpty($response->json('views_by_day'));
        $this->assertEmpty($response->json('views_by_device'));
    }

    // =========================================================================
    // Days filter
    // =========================================================================

    public function test_analytics_days_filter_works(): void
    {
        $user = $this->createUserWithPermission('page.update');
        $page = Page::factory()->create();

        // view 40 days ago — outside 30-day window but inside 60-day window
        PageView::factory()->forPage($page)->onDate(now()->subDays(40)->toDateString())->create();
        // view 10 days ago — inside both windows
        PageView::factory()->forPage($page)->onDate(now()->subDays(10)->toDateString())->create();

        $response30 = $this->actingAs($user)
            ->getJson(route('pages.analytics.show', $page).'?days=30')
            ->assertOk();

        $response60 = $this->actingAs($user)
            ->getJson(route('pages.analytics.show', $page).'?days=60')
            ->assertOk();

        $this->assertEquals(1, $response30->json('summary.total_views'));
        $this->assertEquals(2, $response60->json('summary.total_views'));
    }

    public function test_analytics_days_capped_at_365(): void
    {
        $user = $this->createUserWithPermission('page.update');
        $page = Page::factory()->create();

        // days=9999 should be silently capped at 365, not error
        $this->actingAs($user)
            ->getJson(route('pages.analytics.show', $page).'?days=9999')
            ->assertOk()
            ->assertJsonStructure(['summary']);
    }

    // =========================================================================
    // Overview endpoint
    // =========================================================================

    public function test_guest_cannot_access_analytics_overview(): void
    {
        $this->getJson(route('pages.analytics.overview'))
            ->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_analytics_overview(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('pages.analytics.overview'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_access_analytics_overview(): void
    {
        $user = $this->createUserWithPermission('page.view');

        $this->actingAs($user)
            ->getJson(route('pages.analytics.overview'))
            ->assertOk()
            ->assertJsonStructure(['top_pages']);
    }

    public function test_overview_lists_pages_ordered_by_views(): void
    {
        $user = $this->createUserWithPermission('page.view');
        $pageA = Page::factory()->create();
        $pageB = Page::factory()->create();

        // pageA: 3 views, pageB: 1 view — pin dates to ensure they're within the window
        PageView::factory()->forPage($pageA)->onDate(now()->toDateString())->count(3)->create();
        PageView::factory()->forPage($pageB)->onDate(now()->toDateString())->count(1)->create();

        $response = $this->actingAs($user)
            ->getJson(route('pages.analytics.overview'))
            ->assertOk();

        $topPages = $response->json('top_pages');
        $this->assertEquals($pageA->id, $topPages[0]['page_id']);
        $this->assertEquals(3, $topPages[0]['views']);
    }
}
