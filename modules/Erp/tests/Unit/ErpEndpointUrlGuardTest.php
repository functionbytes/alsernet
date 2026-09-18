<?php

namespace Modules\Erp\Tests\Unit;

use Modules\Erp\Support\ErpEndpointUrlGuard;
use PHPUnit\Framework\TestCase;

/**
 * SSRF guard de las URLs de ERP Endpoints. A diferencia del OutboundUrlGuard
 * genérico, permite la red interna (RFC1918) donde viven los endpoints ERP,
 * pero bloquea loopback y link-local/metadata cloud.
 */
class ErpEndpointUrlGuardTest extends TestCase
{
    public function test_blocks_cloud_metadata_ip(): void
    {
        $this->assertFalse(ErpEndpointUrlGuard::isAllowed('http://169.254.169.254/latest/meta-data/'));
    }

    public function test_blocks_loopback(): void
    {
        $this->assertFalse(ErpEndpointUrlGuard::isAllowed('http://127.0.0.1:8080/'));
        $this->assertFalse(ErpEndpointUrlGuard::isAllowed('http://localhost/x'));
    }

    public function test_blocks_non_http_scheme(): void
    {
        $this->assertFalse(ErpEndpointUrlGuard::isAllowed('ftp://example.com/'));
        $this->assertFalse(ErpEndpointUrlGuard::isAllowed('file:///etc/passwd'));
    }

    public function test_blocks_empty_or_malformed(): void
    {
        $this->assertFalse(ErpEndpointUrlGuard::isAllowed(null));
        $this->assertFalse(ErpEndpointUrlGuard::isAllowed(''));
        $this->assertFalse(ErpEndpointUrlGuard::isAllowed('not-a-url'));
    }

    public function test_allows_internal_docker_network(): void
    {
        // Los endpoints ERP legítimos viven en la red interna (RFC1918).
        $this->assertTrue(ErpEndpointUrlGuard::isAllowed('http://172.20.0.5/api/erp/clientes'));
        $this->assertTrue(ErpEndpointUrlGuard::isAllowed('http://10.0.0.10/api'));
        $this->assertTrue(ErpEndpointUrlGuard::isAllowed('http://192.168.1.50/erp'));
    }

    public function test_allows_public_https(): void
    {
        $this->assertTrue(ErpEndpointUrlGuard::isAllowed('https://example.com/api'));
    }
}
