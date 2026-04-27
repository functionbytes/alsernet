<?php

namespace Modules\Optimize\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Optimize\Http\Middleware\CollapseWhitespace;
use Modules\Optimize\Http\Middleware\PageSpeed;
use ReflectionClass;
use Tests\TestCase;

/**
 * Tests for PageSpeed::shouldProcess() conditions.
 *
 * We avoid hitting the database by injecting the enabled state directly into
 * the static cache via reflection. The integration between PageSpeed and
 * Setting::get is tested implicitly by the Feature/Controller tests.
 */
class PageSpeedShouldProcessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        PageSpeed::resetCache();
    }

    protected function tearDown(): void
    {
        PageSpeed::resetCache();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Force the per-request enabled cache without touching the database. */
    private function forceEnabled(bool $value, array $customPatterns = []): void
    {
        $ref = new ReflectionClass(PageSpeed::class);

        $enabled = $ref->getProperty('globalEnabled');
        $enabled->setAccessible(true);
        $enabled->setValue(null, $value);

        $patterns = $ref->getProperty('customSkipPatterns');
        $patterns->setAccessible(true);
        $patterns->setValue(null, $customPatterns);
    }

    private function makeHtmlResponse(string $content = '<p>hello world</p>'): Response
    {
        return new Response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function runMiddleware(Request $request, Response $response): Response
    {
        return (new CollapseWhitespace)->handle($request, fn () => $response);
    }

    // -------------------------------------------------------------------------
    // Master toggle
    // -------------------------------------------------------------------------

    public function test_skips_when_optimization_disabled(): void
    {
        $this->forceEnabled(false);

        $html = '<p>  spaced  content  </p>';
        $result = $this->runMiddleware(Request::create('/page'), $this->makeHtmlResponse($html));

        $this->assertSame($html, $result->getContent());
    }

    public function test_processes_when_optimization_enabled(): void
    {
        $this->forceEnabled(true);

        $result = $this->runMiddleware(
            Request::create('/page'),
            $this->makeHtmlResponse('<p>  two  spaces  </p>')
        );

        $this->assertStringNotContainsString('  ', $result->getContent());
    }

    // -------------------------------------------------------------------------
    // Route exclusions
    // -------------------------------------------------------------------------

    public function test_skips_setting_routes(): void
    {
        $this->forceEnabled(true);

        $html = '<p>  spaced  </p>';
        $result = $this->runMiddleware(
            Request::create('/setting/optimize'),
            $this->makeHtmlResponse($html)
        );

        $this->assertSame($html, $result->getContent());
    }

    public function test_skips_setting_wildcard_routes(): void
    {
        $this->forceEnabled(true);

        $html = '<p>  spaced  </p>';
        $result = $this->runMiddleware(
            Request::create('/setting/anything/nested'),
            $this->makeHtmlResponse($html)
        );

        $this->assertSame($html, $result->getContent());
    }

    // -------------------------------------------------------------------------
    // Content-Type exclusions
    // -------------------------------------------------------------------------

    public function test_skips_json_content_type(): void
    {
        $this->forceEnabled(true);

        $json = '{"key":"value"}';
        $response = new Response($json, 200, ['Content-Type' => 'application/json']);
        $result = $this->runMiddleware(Request::create('/api/data'), $response);

        $this->assertSame($json, $result->getContent());
    }

    public function test_skips_xml_content_type(): void
    {
        $this->forceEnabled(true);

        $xml = '<root><item>1</item></root>';
        $response = new Response($xml, 200, ['Content-Type' => 'application/xml']);
        $result = $this->runMiddleware(Request::create('/feed'), $response);

        $this->assertSame($xml, $result->getContent());
    }

    public function test_processes_html_content_type(): void
    {
        $this->forceEnabled(true);

        $result = $this->runMiddleware(
            Request::create('/page'),
            $this->makeHtmlResponse('<p>  spaces  </p>')
        );

        $this->assertStringNotContainsString('  ', $result->getContent());
    }

    // -------------------------------------------------------------------------
    // JSON request (Accept: application/json)
    // -------------------------------------------------------------------------

    public function test_skips_json_accept_header(): void
    {
        $this->forceEnabled(true);

        $html = '<p>  spaced  </p>';
        $request = Request::create('/page', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $result = $this->runMiddleware($request, $this->makeHtmlResponse($html));

        $this->assertSame($html, $result->getContent());
    }

    // -------------------------------------------------------------------------
    // Custom skip patterns
    // -------------------------------------------------------------------------

    public function test_skips_request_matching_custom_pattern(): void
    {
        $this->forceEnabled(true, ['my-secret/*', 'admin/reports']);

        $html = '<p>  spaced  </p>';
        $result = $this->runMiddleware(
            Request::create('/my-secret/page'),
            $this->makeHtmlResponse($html)
        );

        $this->assertSame($html, $result->getContent());
    }

    public function test_processes_request_not_matching_custom_pattern(): void
    {
        $this->forceEnabled(true, ['my-secret/*']);

        $result = $this->runMiddleware(
            Request::create('/public/page'),
            $this->makeHtmlResponse('<p>  spaces  </p>')
        );

        $this->assertStringNotContainsString('  ', $result->getContent());
    }

    // -------------------------------------------------------------------------
    // Processes non-excluded pages
    // -------------------------------------------------------------------------

    public function test_processes_regular_page(): void
    {
        $this->forceEnabled(true);

        $result = $this->runMiddleware(
            Request::create('/dashboard'),
            $this->makeHtmlResponse('<div>  hello   world  </div>')
        );

        $this->assertStringNotContainsString('  ', $result->getContent());
    }
}
