<?php

namespace Modules\HelpdeskAgents\Tests\Unit;

use Modules\HelpdeskAgents\Services\LlmConnectionTesterService;
use Tests\TestCase;

/**
 * SSRF guard del "LLM local" (Ollama). A diferencia del guard genérico, PERMITE
 * loopback y RFC1918 (un Ollama self-hosted vive ahí) y solo bloquea esquemas no
 * http(s) y el rango link-local 169.254.0.0/16 (incluye el metadata cloud
 * 169.254.169.254). Se ejercita el método privado por reflexión.
 */
class LlmLocalUrlGuardTest extends TestCase
{
    private function assertGuard(string $url): void
    {
        $method = new \ReflectionMethod(LlmConnectionTesterService::class, 'assertSafeLocalUrl');
        $method->setAccessible(true);
        $method->invoke(new LlmConnectionTesterService, $url);
    }

    public function test_blocks_cloud_metadata_ip(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->assertGuard('http://169.254.169.254/api/tags');
    }

    public function test_blocks_link_local_range(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->assertGuard('http://169.254.10.20:11434');
    }

    public function test_blocks_non_http_scheme(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->assertGuard('file:///etc/passwd');
    }

    public function test_blocks_malformed_url(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->assertGuard('not-a-url');
    }

    public function test_allows_loopback(): void
    {
        $this->assertGuard('http://localhost:11434');
        $this->assertGuard('http://127.0.0.1:11434');
        $this->addToAssertionCount(1);
    }

    public function test_allows_private_lan_ip(): void
    {
        // Un Ollama en un box con GPU de la LAN es un destino legítimo.
        $this->assertGuard('http://192.168.1.50:11434');
        $this->assertGuard('http://10.0.0.10:11434');
        $this->addToAssertionCount(1);
    }
}
