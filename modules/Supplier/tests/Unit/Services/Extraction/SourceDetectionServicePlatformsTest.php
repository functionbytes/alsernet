<?php

namespace Modules\Supplier\Tests\Unit\Services\Extraction;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Http;
use Modules\Supplier\Services\Extraction\SourceDetectionService;
use Tests\TestCase;

class SourceDetectionServicePlatformsTest extends TestCase
{
    private SourceDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SourceDetectionService;
    }

    /**
     * Use reflection to call a private platform detector directly,
     * injecting an Http-faked Guzzle-compatible client via the Illuminate
     * HTTP facade (the service uses Guzzle internally, so we instead call
     * the public detect() method through a mock-able sub-call approach by
     * overriding the detectXxx methods via reflection).
     *
     * Since the private detectors accept a Guzzle Client we cannot inject
     * the Laravel Http fake. Instead, we test the full detect() path using
     * a real Guzzle client pointed at fake routes via Http::fake(), noting
     * that the service uses `new Client()` internally.
     *
     * For unit-level platform detection tests we therefore use reflection
     * to directly invoke the private detector methods with a pre-configured
     * mock Guzzle client built from Http::fake() responses translated into
     * GuzzleHttp\Psr7 responses.
     */
    private function makeFakeGuzzleClient(array $responses): Client
    {
        $handler = HandlerStack::create(
            new MockHandler($responses)
        );

        return new Client(['handler' => $handler]);
    }

    private function callDetector(string $method, Client $client, string $base): ?array
    {
        $ref = new \ReflectionMethod($this->service, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->service, $client, $base);
    }

    public function test_detect_shopify_returns_shopify_api_result_on_products_response(): void
    {
        $body = json_encode(['products' => [['id' => 1, 'title' => 'Test']]]);
        $client = $this->makeFakeGuzzleClient([
            new Response(200, [], $body),
            // countShopifyProducts call
            new Response(200, [], json_encode(['products' => [['id' => 1]]])),
            new Response(200, [], json_encode(['products' => []])),
        ]);

        $result = $this->callDetector('detectShopify', $client, 'https://shop.example.com');

        $this->assertNotNull($result);
        $this->assertSame('Shopify', $result['platform']);
        $this->assertSame('api', $result['source_type']);
    }

    public function test_detect_shopify_returns_null_when_no_products_key(): void
    {
        $client = $this->makeFakeGuzzleClient([
            new Response(200, [], json_encode(['items' => []])),
        ]);

        $result = $this->callDetector('detectShopify', $client, 'https://shop.example.com');

        $this->assertNull($result);
    }

    public function test_detect_woocommerce_returns_woocommerce_api_result_on_list_response(): void
    {
        $body = json_encode([
            ['id' => 1, 'name' => 'Shirt'],
            ['id' => 2, 'name' => 'Pants'],
        ]);
        $client = $this->makeFakeGuzzleClient([
            new Response(200, ['X-WP-Total' => ['50']], $body),
        ]);

        $result = $this->callDetector('detectWooCommerce', $client, 'https://store.example.com');

        $this->assertNotNull($result);
        $this->assertSame('WooCommerce', $result['platform']);
        $this->assertSame('api', $result['source_type']);
        $this->assertSame(50, $result['stats']['total_products']);
    }

    public function test_detect_woocommerce_returns_null_when_response_is_not_list(): void
    {
        $client = $this->makeFakeGuzzleClient([
            new Response(200, [], json_encode(['count' => 5])),
        ]);

        $result = $this->callDetector('detectWooCommerce', $client, 'https://store.example.com');

        $this->assertNull($result);
    }

    public function test_detect_magento_returns_magento_api_result_on_items_response(): void
    {
        $body = json_encode(['items' => [['sku' => 'SKU-1']], 'total_count' => 42]);
        $client = $this->makeFakeGuzzleClient([
            new Response(200, [], $body),
        ]);

        $result = $this->callDetector('detectMagento', $client, 'https://magento.example.com');

        $this->assertNotNull($result);
        $this->assertSame('Magento', $result['platform']);
        $this->assertSame('api', $result['source_type']);
        $this->assertSame(42, $result['stats']['total_products']);
    }

    public function test_detect_magento_returns_null_when_items_key_missing(): void
    {
        $client = $this->makeFakeGuzzleClient([
            new Response(200, [], json_encode(['products' => []])),
        ]);

        $result = $this->callDetector('detectMagento', $client, 'https://magento.example.com');

        $this->assertNull($result);
    }

    public function test_detect_prestashop_returns_prestashop_api_result_on_products_key(): void
    {
        $body = json_encode(['products' => [['id' => 1]]]);
        $client = $this->makeFakeGuzzleClient([
            new Response(200, [], $body),
        ]);

        $result = $this->callDetector('detectPrestaShop', $client, 'https://ps.example.com');

        $this->assertNotNull($result);
        $this->assertSame('PrestaShop', $result['platform']);
        $this->assertSame('api', $result['source_type']);
    }

    public function test_detect_prestashop_returns_null_when_products_key_missing(): void
    {
        $client = $this->makeFakeGuzzleClient([
            new Response(200, [], json_encode(['catalog' => []])),
        ]);

        $result = $this->callDetector('detectPrestaShop', $client, 'https://ps.example.com');

        $this->assertNull($result);
    }

    public function test_detect_from_html_returns_shopify_fallback_on_window_shopify_signature(): void
    {
        $html = '<html><head></head><body><script>window.Shopify = {};</script></body></html>';
        $client = $this->makeFakeGuzzleClient([
            new Response(200, [], $html),
        ]);

        $ref = new \ReflectionMethod($this->service, 'detectFromHtml');
        $ref->setAccessible(true);
        $result = $ref->invoke($this->service, $client, 'https://shop.example.com', 'https://shop.example.com');

        $this->assertStringContainsString('Shopify', $result['platform']);
        $this->assertSame('website', $result['source_type']);
    }

    public function test_assert_public_url_throws_for_private_192_168_address(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $ref = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $ref->setAccessible(true);
        $ref->invoke($this->service, 'http://192.168.1.10/api/products');
    }

    public function test_assert_public_url_throws_for_localhost(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $ref = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $ref->setAccessible(true);
        $ref->invoke($this->service, 'https://localhost/shop');
    }

    public function test_assert_public_url_throws_for_non_http_scheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $ref = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $ref->setAccessible(true);
        $ref->invoke($this->service, 'ftp://example.com/products');
    }
}
