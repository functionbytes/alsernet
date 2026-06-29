<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Page\Jobs\ScanPagePerformanceJob;
use Modules\Page\Models\Page;
use Modules\Page\Models\PagePerformanceMetric;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure page permissions exist so policy checks don't throw
        Permission::firstOrCreate(['name' => 'page.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'page.update', 'guard_name' => 'web']);
    }

    private function createUserWithPermission(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    // =========================================================================
    // Scan — POST /pages/{page}/performance/scan
    // =========================================================================

    public function test_scan_dispatches_two_jobs_for_mobile_and_desktop(): void
    {
        $user = $this->createUserWithPermission('page.update');
        $page = Page::factory()->create();

        Queue::fake();

        $this->actingAs($user)
            ->postJson(route('pages.performance.scan', $page))
            ->assertOk()
            ->assertJsonStructure(['message']);

        Queue::assertPushed(ScanPagePerformanceJob::class, 2);
    }

    public function test_scan_returns_success_message(): void
    {
        $user = $this->createUserWithPermission('page.update');
        $page = Page::factory()->create();

        Queue::fake();

        $response = $this->actingAs($user)
            ->postJson(route('pages.performance.scan', $page))
            ->assertOk();

        $this->assertNotEmpty($response->json('message'));
    }

    public function test_guest_cannot_scan_performance(): void
    {
        $page = Page::factory()->create();

        Queue::fake();

        $this->postJson(route('pages.performance.scan', $page))
            ->assertUnauthorized();

        Queue::assertNotPushed(ScanPagePerformanceJob::class);
    }

    public function test_unauthorized_user_cannot_scan_performance(): void
    {
        $user = User::factory()->create();
        $page = Page::factory()->create();

        Queue::fake();

        $this->actingAs($user)
            ->postJson(route('pages.performance.scan', $page))
            ->assertForbidden();

        Queue::assertNotPushed(ScanPagePerformanceJob::class);
    }

    // =========================================================================
    // Show — GET /pages/{page}/performance
    // =========================================================================

    public function test_show_returns_null_when_no_metrics_exist(): void
    {
        $user = $this->createUserWithPermission('page.view');
        $page = Page::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('pages.performance.show', $page))
            ->assertOk();

        $this->assertNull($response->json('mobile'));
        $this->assertNull($response->json('desktop'));
    }

    public function test_show_returns_latest_mobile_and_desktop_metrics(): void
    {
        $user = $this->createUserWithPermission('page.view');
        $page = Page::factory()->create();

        // Create older metrics first, then newer ones
        PagePerformanceMetric::factory()->forPage($page)->mobile()->create(['performance_score' => 40]);
        PagePerformanceMetric::factory()->forPage($page)->desktop()->create(['performance_score' => 50]);

        $latestMobile = PagePerformanceMetric::factory()->forPage($page)->mobile()->create(['performance_score' => 85]);
        $latestDesktop = PagePerformanceMetric::factory()->forPage($page)->desktop()->create(['performance_score' => 92]);

        $response = $this->actingAs($user)
            ->getJson(route('pages.performance.show', $page))
            ->assertOk();

        $this->assertEquals($latestMobile->id, $response->json('mobile.id'));
        $this->assertEquals($latestDesktop->id, $response->json('desktop.id'));
    }

    public function test_guest_cannot_view_performance(): void
    {
        $page = Page::factory()->create();

        $this->getJson(route('pages.performance.show', $page))
            ->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_view_performance(): void
    {
        $user = User::factory()->create();
        $page = Page::factory()->create();

        $this->actingAs($user)
            ->getJson(route('pages.performance.show', $page))
            ->assertForbidden();
    }

    // =========================================================================
    // Top — GET /pages/performance/top
    // =========================================================================

    public function test_top_returns_best_performing_pages(): void
    {
        $user = $this->createUserWithPermission('page.view');

        $pageA = Page::factory()->create();
        $pageB = Page::factory()->create();
        $pageC = Page::factory()->create();

        PagePerformanceMetric::factory()->forPage($pageA)->mobile()->create(['performance_score' => 95]);
        PagePerformanceMetric::factory()->forPage($pageB)->mobile()->create(['performance_score' => 70]);
        PagePerformanceMetric::factory()->forPage($pageC)->mobile()->create(['performance_score' => 40]);

        $response = $this->actingAs($user)
            ->getJson(route('pages.performance.top'))
            ->assertOk();

        $scores = collect($response->json())->pluck('performance_score')->map(fn ($s) => (int) $s)->toArray();
        $this->assertEquals([95, 70, 40], $scores);
    }

    public function test_top_returns_only_latest_metric_per_page(): void
    {
        $user = $this->createUserWithPermission('page.view');
        $page = Page::factory()->create();

        // Old metric with high score
        PagePerformanceMetric::factory()->forPage($page)->mobile()->create(['performance_score' => 95]);
        // Latest metric with lower score — this one should appear
        $latest = PagePerformanceMetric::factory()->forPage($page)->mobile()->create(['performance_score' => 60]);

        $response = $this->actingAs($user)
            ->getJson(route('pages.performance.top'))
            ->assertOk();

        $this->assertCount(1, $response->json());
        $this->assertEquals($latest->id, $response->json('0.id'));
    }

    public function test_guest_cannot_access_top_performance(): void
    {
        $this->getJson(route('pages.performance.top'))
            ->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_top_performance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('pages.performance.top'))
            ->assertForbidden();
    }
}
