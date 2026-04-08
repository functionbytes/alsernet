<?php

namespace Modules\Optimize\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Optimize\Http\Middleware\OptimizeResponseCache;
use Tests\TestCase;

class OptimizeResponseCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // Pre-seed Setting cache to avoid DB queries (array store has no settings table)
        cache()->put('setting_optimize.response_cache_ttl', '60', now()->addMinutes(10));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    private function makeHtmlResponse(string $content = '<p>hello</p>', int $status = 200): Response
    {
        return new Response($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function runMiddleware(Request $request, Response $response): Response
    {
        return (new OptimizeResponseCache)->handle($request, fn () => $response);
    }

    // -------------------------------------------------------------------------
    // Cache miss — stores and returns the response
    // -------------------------------------------------------------------------

    public function test_cache_miss_returns_original_response(): void
    {
        $request = Request::create('/page');
        $response = $this->makeHtmlResponse('<p>content</p>');

        $result = $this->runMiddleware($request, $response);

        $this->assertSame('<p>content</p>', $result->getContent());
    }

    public function test_cache_miss_stores_response_content(): void
    {
        $request = Request::create('/page');
        $this->runMiddleware($request, $this->makeHtmlResponse('<p>stored</p>'));

        $key = 'optimize.cache.'.md5($request->fullUrl().'|');
        $cached = Cache::tags(['optimize.response'])->get($key);

        $this->assertSame('<p>stored</p>', $cached);
    }

    // -------------------------------------------------------------------------
    // Cache hit — returns cached response
    // -------------------------------------------------------------------------

    public function test_cache_hit_returns_cached_content(): void
    {
        $request = Request::create('/page');
        $key = 'optimize.cache.'.md5($request->fullUrl().'|');
        Cache::tags(['optimize.response'])->put($key, '<p>cached</p>', 60);

        $result = $this->runMiddleware($request, $this->makeHtmlResponse('<p>fresh</p>'));

        $this->assertSame('<p>cached</p>', $result->getContent());
    }

    public function test_cache_hit_adds_hit_header(): void
    {
        $request = Request::create('/page');
        $key = 'optimize.cache.'.md5($request->fullUrl().'|');
        Cache::tags(['optimize.response'])->put($key, '<p>cached</p>', 60);

        $result = $this->runMiddleware($request, $this->makeHtmlResponse('<p>fresh</p>'));

        $this->assertSame('HIT', $result->headers->get('X-Optimize-Cache'));
    }

    // -------------------------------------------------------------------------
    // Bypass conditions
    // -------------------------------------------------------------------------

    public function test_bypasses_non_get_requests(): void
    {
        $request = Request::create('/page', 'POST');
        $response = $this->makeHtmlResponse('<p>post</p>');

        $result = $this->runMiddleware($request, $response);

        // Non-GET requests must not be cached
        $key = 'optimize.cache.'.md5($request->fullUrl().'|');
        $this->assertNull(Cache::tags(['optimize.response'])->get($key));
        $this->assertSame('<p>post</p>', $result->getContent());
    }

    public function test_bypasses_setting_routes(): void
    {
        $request = Request::create('/setting/optimize');
        $this->runMiddleware($request, $this->makeHtmlResponse('<p>settings</p>'));

        $key = 'optimize.cache.'.md5($request->fullUrl().'|');
        $this->assertNull(Cache::tags(['optimize.response'])->get($key));
    }

    public function test_bypasses_json_accept_requests(): void
    {
        $request = Request::create('/page', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->runMiddleware($request, $this->makeHtmlResponse());

        $key = 'optimize.cache.'.md5($request->fullUrl().'|');
        $this->assertNull(Cache::tags(['optimize.response'])->get($key));
    }

    public function test_does_not_cache_non_html_responses(): void
    {
        $request = Request::create('/api/data');
        $response = new Response('{"key":"val"}', 200, ['Content-Type' => 'application/json']);

        $this->runMiddleware($request, $response);

        $key = 'optimize.cache.'.md5($request->fullUrl().'|');
        $this->assertNull(Cache::tags(['optimize.response'])->get($key));
    }

    public function test_does_not_cache_non_200_responses(): void
    {
        $request = Request::create('/page');
        $response = $this->makeHtmlResponse('<p>not found</p>', 404);

        $this->runMiddleware($request, $response);

        $key = 'optimize.cache.'.md5($request->fullUrl().'|');
        $this->assertNull(Cache::tags(['optimize.response'])->get($key));
    }
}
