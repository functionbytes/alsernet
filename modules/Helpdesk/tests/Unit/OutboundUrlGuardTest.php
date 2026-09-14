<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Support\OutboundUrlGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic SSRF guard tests — IP literals only, so no DNS/network is needed.
 */
class OutboundUrlGuardTest extends TestCase
{
    #[DataProvider('blockedUrls')]
    public function test_blocks_unsafe_urls(?string $url): void
    {
        $this->assertFalse(OutboundUrlGuard::isSafe($url));
    }

    #[DataProvider('safeUrls')]
    public function test_allows_public_http_urls(string $url): void
    {
        $this->assertTrue(OutboundUrlGuard::isSafe($url));
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    public static function blockedUrls(): array
    {
        return [
            ['http://169.254.169.254/latest/meta-data/'], // cloud metadata (link-local)
            ['http://127.0.0.1/admin'],                   // loopback
            ['http://10.0.0.1/'],                         // private
            ['http://192.168.1.1/'],                      // private
            ['http://172.16.0.1/'],                       // private
            ['http://[::1]/'],                            // IPv6 loopback
            ['ftp://8.8.8.8/'],                           // non-http scheme
            ['file:///etc/passwd'],                       // file scheme
            ['not-a-url'],                                // unparseable
            [''],                                         // empty
            [null],                                       // null
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function safeUrls(): array
    {
        return [
            ['http://8.8.8.8/hook'],
            ['https://1.1.1.1/webhook'],
        ];
    }
}
