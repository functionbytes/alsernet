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

        $response = Http::timeout(10)->get($baseUrl.'/api/tags');

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
