<?php

namespace Modules\Supplier\Tests\Unit\Traits;

use Modules\Supplier\Traits\ValidatesPublicUrl;
use Tests\TestCase;

class ValidatesPublicUrlTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class
        {
            use ValidatesPublicUrl;

            public function check(string $url): void
            {
                $this->assertUrlIsPublic($url);
            }

            public function checkHost(string $host): void
            {
                $this->assertHostIsPublic($host);
            }

            public function isPublic(string $url): bool
            {
                return $this->urlIsPublic($url);
            }
        };
    }

    /**
     * @return list<array{string}>
     */
    public static function blockedUrls(): array
    {
        return [
            ['http://127.0.0.1/api'],
            ['http://localhost/shop'],
            ['https://localhost:8080/shop'],
            ['http://host.docker.internal/api'],
            ['http://metadata.google.internal/computeMetadata/v1/'],
            ['http://169.254.169.254/latest/meta-data/'],
            ['http://169.254.0.1/'],
            ['http://10.0.0.1/api'],
            ['http://192.168.1.50/catalog'],
            ['http://172.16.0.1/products'],
            ['http://[::1]/api'],
            ['http://[fc00::1]/api'],
            ['http://[fe80::1]/api'],
            ['ftp://example.com/products'],
            ['file:///etc/passwd'],
        ];
    }

    /**
     * @dataProvider blockedUrls
     */
    public function test_assert_url_is_public_rejects_internal_targets(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->check($url);
    }

    /**
     * @dataProvider blockedUrls
     */
    public function test_url_is_public_returns_false_for_internal_targets(string $url): void
    {
        $this->assertFalse($this->subject->isPublic($url));
    }

    public function test_assert_url_is_public_allows_public_ip(): void
    {
        $this->subject->check('http://8.8.8.8/');

        $this->assertTrue(true);
    }

    public function test_assert_host_is_public_rejects_private_host(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->checkHost('127.0.0.1');
    }

    public function test_assert_host_is_public_rejects_blocked_hostname(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->checkHost('host.docker.internal');
    }

    public function test_assert_host_is_public_allows_public_ip(): void
    {
        $this->subject->checkHost('8.8.8.8');

        $this->assertTrue(true);
    }
}
