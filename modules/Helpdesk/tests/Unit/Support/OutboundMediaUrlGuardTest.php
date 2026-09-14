<?php

namespace Modules\Helpdesk\Tests\Unit\Support;

use Modules\Helpdesk\Support\OutboundMediaUrlGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OutboundMediaUrlGuardTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function blockedUrlProvider(): array
    {
        return [
            'metadata cloud (link-local)' => ['http://169.254.169.254/latest/meta-data/'],
            'loopback ipv4' => ['http://127.0.0.1/x'],
            'loopback ipv6' => ['http://[::1]/x'],
            'this-host 0.0.0.0' => ['http://0.0.0.0/x'],
            'rfc1918 10/8 (red interna docker)' => ['http://10.0.0.5/redis'],
            'rfc1918 172.16/12' => ['http://172.16.0.1/x'],
            'rfc1918 192.168/16' => ['http://192.168.253.8/oracle'],
            'esquema no http' => ['ftp://185.199.108.153/x'],
            'esquema file' => ['file:///etc/passwd'],
            'gopher' => ['gopher://127.0.0.1:6379/x'],
        ];
    }

    #[DataProvider('blockedUrlProvider')]
    public function test_bloquea_urls_internas_y_esquemas_peligrosos(string $url): void
    {
        $this->assertFalse(OutboundMediaUrlGuard::isAllowed($url));
    }

    /**
     * @return array<string, array{0: ?string}>
     */
    public static function invalidUrlProvider(): array
    {
        return [
            'null' => [null],
            'vacio' => [''],
            'sin host' => ['/relative/path'],
            'sin esquema' => ['example.com/img.jpg'],
        ];
    }

    #[DataProvider('invalidUrlProvider')]
    public function test_bloquea_urls_invalidas(?string $url): void
    {
        $this->assertFalse(OutboundMediaUrlGuard::isAllowed($url));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function allowedUrlProvider(): array
    {
        return [
            'ip publica http' => ['http://8.8.8.8/img.jpg'],
            'ip publica https' => ['https://1.1.1.1/media/photo.png'],
        ];
    }

    #[DataProvider('allowedUrlProvider')]
    public function test_permite_ips_publicas(string $url): void
    {
        $this->assertTrue(OutboundMediaUrlGuard::isAllowed($url));
    }
}
