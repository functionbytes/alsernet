<?php

namespace Modules\Optimize\Tests\Unit;

use Illuminate\Http\Request;
use Modules\Optimize\Http\Middleware\InsertDNSPrefetch;
use Tests\TestCase;

class InsertDNSPrefetchTest extends TestCase
{
    private InsertDNSPrefetch $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new InsertDNSPrefetch;
    }

    public function test_injects_dns_prefetch_for_external_host(): void
    {
        $this->app->instance('request', Request::create('http://localhost/page'));

        $html = '<html><head></head><body><img src="https://cdn.example.com/img.png"></body></html>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('dns-prefetch', $result);
        $this->assertStringContainsString('cdn.example.com', $result);
    }

    public function test_injects_preconnect_for_external_host(): void
    {
        $this->app->instance('request', Request::create('http://localhost/page'));

        $html = '<html><head></head><body><img src="https://cdn.example.com/img.png"></body></html>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('preconnect', $result);
    }

    public function test_does_not_inject_for_current_host(): void
    {
        $this->app->instance('request', Request::create('http://cdn.example.com/page'));

        $html = '<html><head></head><body><img src="https://cdn.example.com/img.png"></body></html>';
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString('dns-prefetch', $result);
    }

    public function test_deduplicates_same_host(): void
    {
        $this->app->instance('request', Request::create('http://localhost/page'));

        $html = '<html><head></head><body>'
            .'<img src="https://cdn.example.com/a.png">'
            .'<img src="https://cdn.example.com/b.png">'
            .'</body></html>';
        $result = $this->middleware->apply($html);

        // Only one dns-prefetch and one preconnect tag injected, regardless of how
        // many times the host appears in the HTML
        $this->assertSame(1, substr_count($result, 'rel="dns-prefetch"'));
        $this->assertSame(1, substr_count($result, 'rel="preconnect"'));
    }

    public function test_strips_port_from_host_comparison(): void
    {
        $this->app->instance('request', Request::create('http://myapp.test/page'));

        $html = '<html><head></head><body><img src="https://cdn.example.com:8080/img.png"></body></html>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('cdn.example.com', $result);
    }

    public function test_returns_unchanged_when_no_external_hosts(): void
    {
        $this->app->instance('request', Request::create('http://myapp.test/page'));

        $html = '<html><head></head><body><p>no external links</p></body></html>';
        $this->assertSame($html, $this->middleware->apply($html));
    }

    public function test_does_not_inject_already_present_prefetch_host(): void
    {
        $this->app->instance('request', Request::create('http://localhost/page'));
        $html = '<html><head><link rel="dns-prefetch" href="//cdn.example.com"></head><body><img src="https://cdn.example.com/img.png"></body></html>';
        $result = $this->middleware->apply($html);
        // Should not inject an additional dns-prefetch tag since one already exists
        $this->assertSame(1, substr_count($result, 'rel="dns-prefetch"'));
    }
}
