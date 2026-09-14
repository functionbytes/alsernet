<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Modules\HelpdeskPrestashop\Jobs\RefreshPsContextJob;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PrestashopContextCachingTest extends TestCase
{
    use DatabaseTransactions;

    protected string $apiUrl = 'http://localhost:8090/modules/alsernetbridge/api.php';

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        config([
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
            'helpdeskprestashop.cache_ttl' => 300,
            'helpdeskprestashop.miss_ttl' => 60,
            'helpdeskprestashop.stale_grace' => 30,
            'helpdeskprestashop.http_timeout' => 10,
            'helpdeskprestashop.circuit_failure_threshold' => 5,
            'helpdeskprestashop.circuit_open_seconds' => 30,
        ]);

        Cache::flush();
    }

    private function userWithPermission(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function fakeSuccessfulApiResponse(): void
    {
        Http::fake([
            $this->apiUrl => Http::response([
                'ok' => true,
                'data' => [
                    'customer' => [
                        'found' => true,
                        'id' => 1,
                        'email' => 'juan@example.com',
                        'orders_count' => 5,
                        'last_order_at' => '2024-03-15 10:23:00',
                        'ltv' => 1523.50,
                    ],
                    'orders' => [],
                    'carts' => [],
                ],
            ], 200),
        ]);
    }

    public function test_http_error_does_not_cache_empty_response(): void
    {
        Http::fake([
            $this->apiUrl => Http::response(['ok' => false, 'error' => 'boom'], 500),
        ]);

        $user = $this->userWithPermission('helpdeskprestashop.view');
        $email = 'juan@example.com';
        $cacheKey = 'prestashop_ctx_'.md5(strtolower(trim($email)));

        $response = $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/customers/'.$email.'/context');

        $response->assertOk();
        $response->assertJsonPath('customer.found', false);
        $this->assertNull(Cache::get($cacheKey), 'Cache must not store empty context after HTTP failure.');
    }

    public function test_api_ok_false_does_not_cache_empty_response(): void
    {
        Http::fake([
            $this->apiUrl => Http::response(['ok' => false, 'error' => 'not found'], 200),
        ]);

        $user = $this->userWithPermission('helpdeskprestashop.view');
        $email = 'unknown@example.com';
        $cacheKey = 'prestashop_ctx_'.md5(strtolower(trim($email)));

        $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/customers/'.$email.'/context')
            ->assertOk();

        $this->assertNull(Cache::get($cacheKey), 'Cache must not store empty context after ok=false response.');
    }

    public function test_api_not_found_caches_miss_with_short_ttl(): void
    {
        Http::fake([
            $this->apiUrl => Http::response([
                'ok' => true,
                'data' => [
                    'customer' => ['found' => false],
                    'orders' => [],
                    'carts' => [],
                ],
            ], 200),
        ]);

        $user = $this->userWithPermission('helpdeskprestashop.view');
        $email = 'unknown@example.com';
        $cacheKey = 'prestashop_ctx_'.md5(strtolower(trim($email)));

        $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/customers/'.$email.'/context')
            ->assertOk();

        $cached = Cache::get($cacheKey);
        $this->assertNotNull($cached, 'Successful API response with found=false must be cached.');
        $this->assertSame(60, $cached['_ttl']);
        $this->assertFalse($cached['customer']['found']);
    }

    public function test_stale_cache_dispatches_background_refresh_job(): void
    {
        Queue::fake();

        $email = 'juan@example.com';
        $cacheKey = 'prestashop_ctx_'.md5(strtolower(trim($email)));

        // Seed cache with an entry old enough to fall inside the stale-grace window.
        // cache_ttl=300, stale_grace=30 → age >= 270 triggers revalidation.
        Cache::put($cacheKey, [
            'customer' => ['found' => true, 'id' => 1, 'email' => $email],
            'orders' => [],
            'carts' => [],
            '_cached_at' => time() - 280,
            '_ttl' => 300,
        ], 300);

        $this->fakeSuccessfulApiResponse();
        $user = $this->userWithPermission('helpdeskprestashop.view');

        $response = $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/customers/'.$email.'/context');

        $response->assertOk();
        $response->assertJsonPath('customer.found', true);

        Queue::assertPushed(RefreshPsContextJob::class, fn (RefreshPsContextJob $job) => true);
    }

    public function test_fresh_cache_does_not_dispatch_refresh_job(): void
    {
        Queue::fake();

        $email = 'juan@example.com';
        $cacheKey = 'prestashop_ctx_'.md5(strtolower(trim($email)));

        Cache::put($cacheKey, [
            'customer' => ['found' => true, 'id' => 1, 'email' => $email],
            'orders' => [],
            'carts' => [],
            '_cached_at' => time() - 5,
            '_ttl' => 300,
        ], 300);

        $user = $this->userWithPermission('helpdeskprestashop.view');

        $this->actingAs($user)
            ->getJson('/api/helpdeskprestashop/customers/'.$email.'/context')
            ->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_circuit_breaker_short_circuits_after_consecutive_failures(): void
    {
        Http::fake([
            $this->apiUrl => Http::response(['ok' => false, 'error' => 'boom'], 500),
        ]);

        $user = $this->userWithPermission('helpdeskprestashop.view');
        $email = 'juan@example.com';
        $url = '/api/helpdeskprestashop/customers/'.$email.'/context';

        // 5 consecutive HTTP failures should open the circuit.
        for ($i = 0; $i < 5; $i++) {
            Cache::forget('prestashop_ctx_'.md5(strtolower(trim($email))));
            $this->actingAs($user)->getJson($url)->assertOk();
        }

        Http::assertSentCount(5);

        // The 6th call should be short-circuited (no new HTTP request).
        Cache::forget('prestashop_ctx_'.md5(strtolower(trim($email))));
        $this->actingAs($user)->getJson($url)->assertOk();

        Http::assertSentCount(5);
    }
}
