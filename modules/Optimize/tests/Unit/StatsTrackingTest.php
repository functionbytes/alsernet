<?php

namespace Modules\Optimize\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Optimize\Http\Middleware\CollapseWhitespace;
use Modules\Optimize\Http\Middleware\PageSpeed;
use ReflectionClass;
use Tests\TestCase;

class StatsTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        PageSpeed::resetCache();
        $this->forceEnabled(true);

        // Reset stats counters before each test
        Cache::forget('optimize.stats.requests');
        Cache::forget('optimize.stats.bytes_saved');
    }

    protected function tearDown(): void
    {
        Cache::forget('optimize.stats.requests');
        Cache::forget('optimize.stats.bytes_saved');
        PageSpeed::resetCache();
        parent::tearDown();
    }

    private function forceEnabled(bool $value): void
    {
        $ref = new ReflectionClass(PageSpeed::class);

        $enabled = $ref->getProperty('globalEnabled');
        $enabled->setAccessible(true);
        $enabled->setValue(null, $value);

        $patterns = $ref->getProperty('customSkipPatterns');
        $patterns->setAccessible(true);
        $patterns->setValue(null, []);
    }

    private function makeHtmlResponse(string $content): Response
    {
        return new Response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function test_handle_increments_requests_when_processing(): void
    {
        $request = Request::create('/page');
        $response = $this->makeHtmlResponse('<p>  two  spaces  </p>');

        (new CollapseWhitespace)->handle($request, fn () => $response);

        $this->assertEquals(1, Cache::get('optimize.stats.requests'));
    }

    public function test_handle_increments_bytes_saved_when_content_shrinks(): void
    {
        $request = Request::create('/page');
        // Extra spaces will be collapsed, reducing byte count
        $response = $this->makeHtmlResponse('<p>  lots   of   whitespace   here  </p>');

        (new CollapseWhitespace)->handle($request, fn () => $response);

        $this->assertGreaterThan(0, Cache::get('optimize.stats.bytes_saved', 0));
    }

    public function test_does_not_double_count_requests_across_multiple_middleware(): void
    {
        $request = Request::create('/page');
        $response = $this->makeHtmlResponse('<p>  two  spaces  </p>');

        // Simulate two middleware running on the same request
        $response1 = (new CollapseWhitespace)->handle($request, fn () => $response);
        (new CollapseWhitespace)->handle($request, fn () => $response1);

        $this->assertEquals(1, Cache::get('optimize.stats.requests'));
    }

    public function test_does_not_track_when_should_process_returns_false(): void
    {
        $this->forceEnabled(false);

        $request = Request::create('/page');
        $response = $this->makeHtmlResponse('<p>  two  spaces  </p>');

        (new CollapseWhitespace)->handle($request, fn () => $response);

        $this->assertNull(Cache::get('optimize.stats.requests'));
    }
}
