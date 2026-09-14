<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\HelpdeskChatFlow\Services\Concerns\GuardsAgainstSsrf;
use Tests\TestCase;

class GuardsAgainstSsrfTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        config(['helpdeskchatflow.http.block_private_hosts' => true]);

        $this->subject = new class
        {
            use GuardsAgainstSsrf {
                ssrfSafeCurlOptions as public;
            }
        };
    }

    public function test_rejects_non_http_scheme(): void
    {
        $this->assertNull($this->subject->ssrfSafeCurlOptions('ftp://example.com/file'));
        $this->assertNull($this->subject->ssrfSafeCurlOptions('not a url'));
        $this->assertNull($this->subject->ssrfSafeCurlOptions(''));
    }

    public function test_rejects_loopback_and_link_local_and_private_ips(): void
    {
        $this->assertNull($this->subject->ssrfSafeCurlOptions('http://127.0.0.1/admin'));
        $this->assertNull($this->subject->ssrfSafeCurlOptions('http://169.254.169.254/latest/meta-data'));
        $this->assertNull($this->subject->ssrfSafeCurlOptions('http://10.0.0.5/internal'));
        $this->assertNull($this->subject->ssrfSafeCurlOptions('http://192.168.1.10/internal'));
    }

    public function test_allows_public_ip_and_pins_the_connection(): void
    {
        $options = $this->subject->ssrfSafeCurlOptions('https://8.8.8.8/path');

        $this->assertIsArray($options);
        $this->assertArrayHasKey(CURLOPT_RESOLVE, $options);
        $this->assertSame(['8.8.8.8:443:8.8.8.8'], $options[CURLOPT_RESOLVE]);
    }

    public function test_skips_pinning_when_blocking_disabled(): void
    {
        config(['helpdeskchatflow.http.block_private_hosts' => false]);

        $this->assertSame([], $this->subject->ssrfSafeCurlOptions('http://127.0.0.1/anything'));
    }
}
