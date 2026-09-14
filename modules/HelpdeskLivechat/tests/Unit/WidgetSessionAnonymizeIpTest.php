<?php

namespace Modules\HelpdeskLivechat\Tests\Unit;

use Modules\HelpdeskLivechat\Models\WidgetSession;
use PHPUnit\Framework\TestCase;

class WidgetSessionAnonymizeIpTest extends TestCase
{
    public function test_ipv4_last_octet_is_zeroed(): void
    {
        $this->assertSame('192.168.1.0', WidgetSession::anonymizeIp('192.168.1.42'));
        $this->assertSame('8.8.8.0', WidgetSession::anonymizeIp('8.8.8.8'));
        $this->assertSame('203.0.113.0', WidgetSession::anonymizeIp('203.0.113.255'));
    }

    public function test_ipv6_keeps_first_48_bits(): void
    {
        // RFC 7239: zero out the lower 80 bits, preserve /48 network prefix
        $result = WidgetSession::anonymizeIp('2001:db8:abc:def::42');

        $this->assertNotNull($result);
        $this->assertStringStartsWith('2001:db8:abc:', $result);
        $this->assertStringNotContainsString('def', $result);
    }

    public function test_ipv6_loopback_anonymized_to_zero_prefix(): void
    {
        $result = WidgetSession::anonymizeIp('::1');

        $this->assertNotNull($result);
        // ::1 has all zero bits except the last → after masking, should be all zeros
        $this->assertSame('::', $result);
    }

    public function test_null_input_returns_null(): void
    {
        $this->assertNull(WidgetSession::anonymizeIp(null));
    }

    public function test_empty_string_returns_null(): void
    {
        $this->assertNull(WidgetSession::anonymizeIp(''));
    }

    public function test_invalid_ip_returns_null(): void
    {
        $this->assertNull(WidgetSession::anonymizeIp('not-an-ip'));
        $this->assertNull(WidgetSession::anonymizeIp('999.999.999.999'));
        $this->assertNull(WidgetSession::anonymizeIp('foo.bar.baz'));
    }

    public function test_anonymization_is_idempotent(): void
    {
        $first = WidgetSession::anonymizeIp('192.168.1.42');
        $second = WidgetSession::anonymizeIp($first);

        $this->assertSame($first, $second);
        $this->assertSame('192.168.1.0', $second);
    }
}
