<?php

namespace Modules\Supplier\Tests\Unit\Services\Extraction;

use Modules\Supplier\Services\Extraction\SourceDetectionService;
use Tests\TestCase;

class SourceDetectionServiceTest extends TestCase
{
    private SourceDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SourceDetectionService;
    }

    public function test_assert_public_url_rejects_127_0_0_1(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $method = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $method->setAccessible(true);
        $method->invoke($this->service, 'http://127.0.0.1/products');
    }

    public function test_assert_public_url_rejects_link_local_169_254(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $method = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $method->setAccessible(true);
        $method->invoke($this->service, 'http://169.254.169.254/latest/meta-data/');
    }

    public function test_assert_public_url_rejects_host_docker_internal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $method = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $method->setAccessible(true);
        $method->invoke($this->service, 'http://host.docker.internal/api');
    }

    public function test_assert_public_url_rejects_localhost(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $method = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $method->setAccessible(true);
        $method->invoke($this->service, 'https://localhost/shop');
    }

    public function test_assert_public_url_rejects_rfc1918_10_x(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $method = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $method->setAccessible(true);
        $method->invoke($this->service, 'http://10.0.0.1/api/products');
    }

    public function test_assert_public_url_rejects_rfc1918_192_168(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $method = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $method->setAccessible(true);
        $method->invoke($this->service, 'http://192.168.1.100/catalog');
    }

    public function test_assert_public_url_rejects_rfc1918_172_16(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $method = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $method->setAccessible(true);
        $method->invoke($this->service, 'http://172.16.0.1/products');
    }

    public function test_assert_public_url_rejects_non_http_scheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $method = new \ReflectionMethod($this->service, 'assertPublicUrl');
        $method->setAccessible(true);
        $method->invoke($this->service, 'ftp://example.com/products');
    }

    public function test_is_blocked_hostname_returns_true_for_localhost(): void
    {
        $method = new \ReflectionMethod($this->service, 'isBlockedHostname');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, 'localhost'));
    }

    public function test_is_blocked_hostname_returns_true_for_host_docker_internal(): void
    {
        $method = new \ReflectionMethod($this->service, 'isBlockedHostname');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, 'host.docker.internal'));
    }

    public function test_is_blocked_hostname_returns_false_for_public_domain(): void
    {
        $method = new \ReflectionMethod($this->service, 'isBlockedHostname');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->service, 'example.com'));
    }

    public function test_is_private_ip_returns_true_for_127_0_0_1(): void
    {
        $method = new \ReflectionMethod($this->service, 'isPrivateIp');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, '127.0.0.1'));
    }

    public function test_is_private_ip_returns_true_for_10_x(): void
    {
        $method = new \ReflectionMethod($this->service, 'isPrivateIp');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, '10.0.0.5'));
    }

    public function test_is_private_ip_returns_true_for_192_168(): void
    {
        $method = new \ReflectionMethod($this->service, 'isPrivateIp');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, '192.168.1.1'));
    }

    public function test_is_private_ip_returns_false_for_public_ip(): void
    {
        $method = new \ReflectionMethod($this->service, 'isPrivateIp');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->service, '8.8.8.8'));
    }

    public function test_normalise_url_adds_https_prefix_when_missing(): void
    {
        $method = new \ReflectionMethod($this->service, 'normaliseUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'example.com/products');

        $this->assertStringStartsWith('https://', $result);
    }

    public function test_normalise_url_removes_trailing_slash(): void
    {
        $method = new \ReflectionMethod($this->service, 'normaliseUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'https://example.com/');

        $this->assertSame('https://example.com', $result);
    }

    public function test_extract_base_url_returns_scheme_and_host(): void
    {
        $method = new \ReflectionMethod($this->service, 'extractBaseUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'https://example.com/products/page/1');

        $this->assertSame('https://example.com', $result);
    }
}
