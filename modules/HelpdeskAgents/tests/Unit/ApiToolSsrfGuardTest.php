<?php

namespace Modules\HelpdeskAgents\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAgents\Models\AiAgentTool;
use Modules\HelpdeskAgents\Services\ToolExecutionService;
use Tests\TestCase;

/**
 * Guard SSRF de las "API tools" de agentes IA: la URL se construye desde la
 * plantilla de la tool sustituyendo placeholders con argumentos que decide el
 * LLM, así que sin validación la tool podía alcanzar 169.254.169.254 (metadata
 * cloud), localhost o la red interna. Se ejercita el método privado por
 * reflexión para aislar las guardas de la maquinaria de sesión/logging.
 */
class ApiToolSsrfGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'helpdeskagents.tools.allow_api' => true,
            'helpdeskagents.tools.allowed_hosts' => [],
        ]);

        Http::preventStrayRequests();
        Http::fake();
    }

    private function runApiTool(string $urlTemplate, array $arguments = []): mixed
    {
        $service = new ToolExecutionService;
        $tool = new AiAgentTool(['type' => 'api', 'implementation' => $urlTemplate]);

        $method = new \ReflectionMethod($service, 'executeApiTool');
        $method->setAccessible(true);

        return $method->invoke($service, $tool, $arguments);
    }

    public function test_api_tools_are_disabled_by_default(): void
    {
        config(['helpdeskagents.tools.allow_api' => false]);

        $this->expectExceptionMessage('API tools are disabled by configuration');
        $this->runApiTool('https://api.example.com/status');
    }

    public function test_rejects_plain_http_scheme(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must use HTTPS');
        $this->runApiTool('http://api.example.com/status');
    }

    public function test_rejects_loopback_ip(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('private or reserved');
        $this->runApiTool('https://127.0.0.1/admin');
    }

    public function test_rejects_cloud_metadata_ip(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('private or reserved');
        $this->runApiTool('https://169.254.169.254/latest/meta-data/');
    }

    public function test_rejects_private_range_ips(): void
    {
        foreach (['https://192.168.1.10/x', 'https://10.0.0.5/x', 'https://172.16.0.1/x'] as $url) {
            try {
                $this->runApiTool($url);
                $this->fail("Expected RuntimeException for {$url}");
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('private or reserved', $e->getMessage(), $url);
            }
        }
    }

    public function test_rejects_host_outside_the_allowlist(): void
    {
        config(['helpdeskagents.tools.allowed_hosts' => ['api.example.com']]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not in the allowed hosts list');
        $this->runApiTool('https://evil.example.net/exfil');
    }

    public function test_rejects_template_with_a_placeholder_in_the_host(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('static host');
        $this->runApiTool('https://{host}/status', ['host' => 'evil.example.net']);
    }

    /**
     * Los argumentos del LLM se sustituyen SIEMPRE URL-encoded: un valor con
     * '/', '@' o '?' no puede escapar del path ni redirigir la request a otro
     * host. Se usa una IP pública literal para que la resolución sea
     * determinista (sin DNS) y Http::fake capture la request final.
     */
    public function test_llm_arguments_are_url_encoded_and_cannot_change_the_host(): void
    {
        config(['helpdeskagents.tools.allowed_hosts' => ['8.8.8.8']]);

        $this->runApiTool('https://8.8.8.8/lookup/{q}', ['q' => '../@evil.example.net/x?y=1']);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://8.8.8.8/lookup/')
                && str_contains($request->url(), rawurlencode('../@evil.example.net/x?y=1'));
        });
    }
}
