<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Pulse\Pulse;
use Modules\HelpdeskErp\Services\ErpContextService;
use Tests\TestCase;

/**
 * Tests for ErpContextService caching behaviour.
 *
 * Uses Feature (not Unit) to leverage the full service container and the
 * array Cache driver configured in phpunit.xml.
 */
class ErpContextServiceCacheTest extends TestCase
{
    use DatabaseTransactions;

    private ErpContextService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Stub Pulse to avoid TypeError (service passes array to Pulse::set)
        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        config(['helpdeskErp.manager_url' => 'http://manager.test']);
        config(['helpdeskErp.cache_ttl' => 600]);
        config(['helpdeskErp.miss_ttl' => 60]);
        config(['helpdeskErp.stale_grace' => 60]);

        // Resolve a fresh service instance after config override
        $this->service = $this->app->make(ErpContextService::class);
    }

    private function cacheKey(string $email): string
    {
        return 'erp_ctx_'.md5(strtolower(trim($email)));
    }

    // ── Found customer → long TTL ──────────────────────────────────────────

    public function test_found_customer_is_cached_with_full_ttl(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => 1, 'label' => 'Ana', 'surnames' => 'Test', 'email' => 'ana@example.com', 'cif' => null]],
            ]),
            '*/erp/customer/1' => Http::response(['data' => []]),
            '*/erp/customer/1/balance' => Http::response(['data' => []]),
            '*/erp/customer/1/orders*' => Http::response(['data' => [], 'meta' => ['loading' => false]]),
            '*/erp/customer/1/invoices*' => Http::response(['data' => []]),
        ]);

        $email = 'ana@example.com';
        $key = $this->cacheKey($email);

        $this->assertFalse(Cache::has($key));

        $result = $this->service->getCustomerContext($email);

        $this->assertTrue($result['customer']['found']);
        $this->assertTrue(Cache::has($key));

        $cached = Cache::get($key);
        $this->assertArrayHasKey('_ttl', $cached);
        $this->assertArrayHasKey('_cached_at', $cached);
        $this->assertSame(600, $cached['_ttl']);
    }

    // ── Not-found customer → negative (miss) TTL ──────────────────────────

    public function test_not_found_customer_is_cached_with_miss_ttl(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response(['data' => []]),
        ]);

        $email = 'nobody@example.com';
        $key = $this->cacheKey($email);

        $result = $this->service->getCustomerContext($email);

        $this->assertFalse($result['customer']['found']);
        $this->assertTrue(Cache::has($key));

        $cached = Cache::get($key);
        $this->assertSame(60, $cached['_ttl']);
        $this->assertArrayNotHasKey('_error', $cached);
    }

    // ── Connection error → short TTL ──────────────────────────────────────

    public function test_connection_error_caches_with_short_ttl(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $email = 'conn@example.com';
        $key = $this->cacheKey($email);

        $result = $this->service->getCustomerContext($email);

        // Result must carry the error marker
        $this->assertArrayHasKey('_error', $result);
        $this->assertSame('connection', $result['_error']['type']);
        $this->assertFalse($result['customer']['found']);

        // Cache entry must exist with very short TTL
        $this->assertTrue(Cache::has($key));
        $cached = Cache::get($key);
        $this->assertArrayHasKey('_error', $cached);
        $this->assertLessThanOrEqual(5, $cached['_ttl']);
    }

    // ── orders_loading = true → do NOT cache ──────────────────────────────

    public function test_orders_loading_result_is_not_cached(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => 2, 'label' => 'Bob', 'surnames' => 'Test', 'email' => 'loading@example.com', 'cif' => null]],
            ]),
            '*/erp/customer/2' => Http::response(['data' => []]),
            '*/erp/customer/2/balance' => Http::response(['data' => []]),
            '*/erp/customer/2/orders*' => Http::response(['data' => [], 'meta' => ['loading' => true, 'retry_after' => 35]]),
            '*/erp/customer/2/invoices*' => Http::response(['data' => []]),
        ]);

        $email = 'loading@example.com';
        $key = $this->cacheKey($email);

        $result = $this->service->getCustomerContext($email);

        $this->assertTrue((bool) $result['orders_loading']);
        $this->assertFalse(Cache::has($key), 'orders_loading result must not be cached');
    }

    // ── Cache hit → served from cache ─────────────────────────────────────

    public function test_second_call_is_served_from_cache_without_http_request(): void
    {
        Http::fake([
            '*/erp/customer/search*' => Http::response([
                'data' => [['id' => 3, 'label' => 'Cached', 'surnames' => 'User', 'email' => 'cached@example.com', 'cif' => null]],
            ]),
            '*/erp/customer/3' => Http::response(['data' => []]),
            '*/erp/customer/3/balance' => Http::response(['data' => []]),
            '*/erp/customer/3/orders*' => Http::response(['data' => [], 'meta' => ['loading' => false]]),
            '*/erp/customer/3/invoices*' => Http::response(['data' => []]),
        ]);

        $email = 'cached@example.com';

        // First call — fetches from manager (search + 4 pool requests: summary, balance, orders, invoices)
        $this->service->getCustomerContext($email);

        // Reset and use a fake that records new requests only
        Http::fake(['*' => Http::response([], 500)]);

        // Second call — must be served from cache, no new HTTP requests
        $result = $this->service->getCustomerContext($email);

        $this->assertTrue($result['customer']['found']);
        Http::assertNothingSent();
    }

    // ── forgetCache clears the key ─────────────────────────────────────────

    public function test_forget_cache_removes_cached_entry(): void
    {
        $email = 'forget@example.com';
        $key = $this->cacheKey($email);

        Cache::put($key, ['customer' => ['found' => true], '_cached_at' => time(), '_ttl' => 600], 600);
        $this->assertTrue(Cache::has($key));

        $this->service->forgetCache($email);

        $this->assertFalse(Cache::has($key));
    }
}
