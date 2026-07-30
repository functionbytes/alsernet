<?php

namespace Modules\HelpdeskAgents\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlmConnectionTesterService
{
    public function test(array $config): void
    {
        match ($config['provider']) {
            'openai' => $this->testOpenAI($config),
            'anthropic' => $this->testAnthropic($config),
            'gemini' => $this->testGemini($config),
            'local' => $this->testLocal($config),
            default => throw new \InvalidArgumentException('Provider no soportado: '.$config['provider']),
        };
    }

    private function testOpenAI(array $config): void
    {
        $apiKey = $config['api_key'] ?? setting('openai_api_key');

        if (! $apiKey) {
            throw new \RuntimeException('API key no configurada para OpenAI');
        }

        $response = Http::withToken($apiKey)
            ->timeout(10)
            ->get('https://api.openai.com/v1/models/'.$config['model']);

        $this->ensureOk($response, 'openai', $config['model']);
    }

    private function testAnthropic(array $config): void
    {
        $apiKey = $config['api_key'] ?? setting('anthropic_api_key');

        if (! $apiKey) {
            throw new \RuntimeException('API key no configurada para Anthropic');
        }

        $response = Http::withHeader('x-api-key', $apiKey)
            ->withHeader('anthropic-version', '2023-06-01')
            ->timeout(10)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $config['model'],
                'max_tokens' => 100,
                'messages' => [['role' => 'user', 'content' => 'test']],
            ]);

        $this->ensureOk($response, 'anthropic', $config['model']);
    }

    private function testGemini(array $config): void
    {
        $apiKey = $config['api_key'] ?? setting('gemini_api_key');

        if (! $apiKey) {
            throw new \RuntimeException('API key no configurada para Gemini');
        }

        $response = Http::withHeader('x-goog-api-key', $apiKey)
            ->timeout(10)
            ->get('https://generativelanguage.googleapis.com/v1/models/'.$config['model']);

        $this->ensureOk($response, 'gemini', $config['model']);
    }

    private function testLocal(array $config): void
    {
        $baseUrl = $config['base_url'] ?? 'http://localhost:11434';

        $this->assertSafeLocalUrl($baseUrl);

        $response = Http::timeout(10)->withoutRedirecting()->get($baseUrl.'/api/tags');

        if (! $response->ok()) {
            Log::warning('LlmConnectionTester: Ollama unreachable', [
                'provider' => 'local',
                'base_url' => $baseUrl,
                'status' => $response->status(),
            ]);
            throw new \RuntimeException('No se pudo conectar con Ollama en '.$baseUrl);
        }

        $modelExists = collect($response->json('models', []))->contains('name', $config['model']);

        if (! $modelExists) {
            throw new \RuntimeException('Modelo '.$config['model'].' no encontrado en Ollama');
        }
    }

    /**
     * SSRF guard para el LLM local (Ollama). A diferencia del OutboundUrlGuard
     * genérico, PERMITE loopback y RFC1918 porque un LLM self-hosted vive ahí
     * (default http://localhost:11434); solo bloquea esquemas no http(s) y el
     * rango link-local (169.254.0.0/16, fe80::/10) que incluye el endpoint de
     * metadata cloud 169.254.169.254 — nunca un Ollama y objetivo típico de SSRF.
     *
     * Si config('helpdeskagents.local_llm_allowed_hosts') no está vacío, actúa
     * de allowlist estricta: solo esos hosts pueden usarse como base_url.
     */
    private function assertSafeLocalUrl(string $baseUrl): void
    {
        $parts = parse_url($baseUrl);
        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || empty($parts['host'])) {
            throw new \RuntimeException('La URL del LLM local debe ser http(s) válida.');
        }

        $host = strtolower(trim($parts['host'], '[]'));

        $allowedHosts = array_map(
            fn (string $h): string => strtolower($h),
            (array) config('helpdeskagents.local_llm_allowed_hosts', [])
        );

        if ($allowedHosts !== [] && ! in_array($host, $allowedHosts, true)) {
            throw new \RuntimeException('El host del LLM local no está en la lista de hosts permitidos.');
        }

        foreach ($this->resolveHost($parts['host']) as $ip) {
            if ($this->isLinkLocal($ip)) {
                throw new \RuntimeException('La URL del LLM local apunta a una dirección no permitida.');
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveHost(string $host): array
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        return gethostbynamel($host) ?: [];
    }

    private function isLinkLocal(string $ip): bool
    {
        if (str_starts_with($ip, '169.254.')) {
            return true;
        }

        $lower = strtolower($ip);

        return str_starts_with($lower, 'fe8')
            || str_starts_with($lower, 'fe9')
            || str_starts_with($lower, 'fea')
            || str_starts_with($lower, 'feb');
    }

    private function ensureOk(Response $response, string $provider, string $model): void
    {
        if ($response->ok()) {
            return;
        }

        $message = $this->extractProviderError($response, $provider);

        Log::warning('LlmConnectionTester: provider returned error', [
            'provider' => $provider,
            'model' => $model,
            'status' => $response->status(),
            'body_excerpt' => str($response->body())->limit(500)->value(),
        ]);

        throw new \RuntimeException(sprintf(
            '%s respondió con %d: %s',
            ucfirst($provider),
            $response->status(),
            $message
        ));
    }

    private function extractProviderError(Response $response, string $provider): string
    {
        $json = $response->json();

        return match ($provider) {
            'openai', 'anthropic' => $json['error']['message'] ?? 'sin detalle',
            'gemini' => $json['error']['message'] ?? 'sin detalle',
            default => 'sin detalle',
        };
    }
}
