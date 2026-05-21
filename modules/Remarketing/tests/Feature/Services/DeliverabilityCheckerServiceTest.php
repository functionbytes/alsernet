<?php

namespace Modules\Remarketing\Tests\Feature\Services;

use Modules\Remarketing\Services\DeliverabilityCheckerService;
use Tests\TestCase;

class DeliverabilityCheckerServiceTest extends TestCase
{
    private DeliverabilityCheckerService $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new DeliverabilityCheckerService;
    }

    // ─── SSRF / internal host guard ───────────────────────────────────────────

    public function test_check_returns_all_false_for_localhost(): void
    {
        $result = $this->checker->check('localhost');

        $this->assertFalse($result['spf']);
        $this->assertFalse($result['dkim']);
        $this->assertFalse($result['dmarc']);
    }

    public function test_check_returns_all_false_for_private_ipv4(): void
    {
        // 10.0.0.1 is RFC-1918 private range
        $result = $this->checker->check('10.0.0.1');

        $this->assertFalse($result['spf']);
        $this->assertFalse($result['dkim']);
        $this->assertFalse($result['dmarc']);
    }

    public function test_check_returns_all_false_for_loopback_ip(): void
    {
        $result = $this->checker->check('127.0.0.1');

        $this->assertFalse($result['spf']);
        $this->assertFalse($result['dkim']);
        $this->assertFalse($result['dmarc']);
    }

    public function test_check_returns_all_false_for_link_local_ip(): void
    {
        // 169.254.x.x is APIPA / link-local — reserved range
        $result = $this->checker->check('169.254.1.1');

        $this->assertFalse($result['spf']);
        $this->assertFalse($result['dkim']);
        $this->assertFalse($result['dmarc']);
    }

    // ─── result shape ─────────────────────────────────────────────────────────

    public function test_check_returns_expected_keys(): void
    {
        $result = $this->checker->check('localhost');

        $this->assertArrayHasKey('spf', $result);
        $this->assertArrayHasKey('dkim', $result);
        $this->assertArrayHasKey('dmarc', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('spf', $result['details']);
        $this->assertArrayHasKey('dkim', $result['details']);
        $this->assertArrayHasKey('dmarc', $result['details']);
    }

    public function test_details_contain_selector_for_dkim(): void
    {
        $result = $this->checker->check('localhost');

        $this->assertArrayHasKey('selector', $result['details']['dkim']);
        $this->assertEquals('rmk', $result['details']['dkim']['selector']);
    }

    // ─── real domain (network may be unavailable in CI — asserting shape only) ─

    public function test_check_real_domain_returns_bool_values(): void
    {
        $result = $this->checker->check('google.com');

        $this->assertIsBool($result['spf']);
        $this->assertIsBool($result['dkim']);
        $this->assertIsBool($result['dmarc']);
    }
}
