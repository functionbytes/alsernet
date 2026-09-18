<?php

namespace Modules\Supplier\Tests\Unit\Services\Ai;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Supplier\Services\Ai\AiApiClient;
use Tests\TestCase;

/**
 * Cubre la inyección del bloque "PASO 1 — FUENTES PRIORITARIAS DEL
 * PROVEEDOR" en el system prompt cuando se pasan preferred_source_urls, y
 * que la bandera enable_web_search realmente decide si se llama a la
 * variante con búsqueda web o a la simple (antes se ignoraba: SIEMPRE se
 * llamaba a la variante con búsqueda, cobrando de más y sin respetar el
 * toggle "Habilitar búsqueda web" del prompt).
 */
class AiApiClientTest extends TestCase
{
    use DatabaseTransactions;

    private AiApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->app->make(AiApiClient::class);
    }

    private function fakeGeminiResponse(): array
    {
        return [
            'candidates' => [[
                'content' => ['parts' => [['text' => 'Descripción generada.']]],
            ]],
            'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 50],
        ];
    }

    public function test_gemini_prompt_includes_preferred_sources_step_when_search_enabled(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->fakeGeminiResponse(), 200)]);
        config(['services.google.api_key' => 'test-key']);

        $this->client->generate('Redacta la ficha del producto X', [
            'model' => 'gemini-2.5-flash',
            'enable_web_search' => true,
            'preferred_source_urls' => ['https://proveedor.com/catalogo', 'https://proveedor.com/prensa'],
        ]);

        Http::assertSent(function ($request) {
            $text = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($text, 'PASO 1 — FUENTES PRIORITARIAS DEL PROVEEDOR')
                && str_contains($text, 'https://proveedor.com/catalogo')
                && str_contains($text, 'https://proveedor.com/prensa')
                && str_contains($text, 'PASO 2 — BÚSQUEDA GENERAL')
                && isset($request['tools']);
        });
    }

    public function test_gemini_prompt_is_unchanged_without_preferred_sources(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->fakeGeminiResponse(), 200)]);
        config(['services.google.api_key' => 'test-key']);

        $this->client->generate('Redacta la ficha del producto X', [
            'model' => 'gemini-2.5-flash',
            'enable_web_search' => true,
        ]);

        Http::assertSent(function ($request) {
            $text = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($text, 'PASO 1 — BÚSQUEDA: Busca el producto en internet')
                && ! str_contains($text, 'FUENTES PRIORITARIAS DEL PROVEEDOR');
        });
    }

    public function test_gemini_does_not_attach_search_tool_when_disabled(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->fakeGeminiResponse(), 200)]);
        config(['services.google.api_key' => 'test-key']);

        $this->client->generate('Redacta la ficha del producto X', [
            'model' => 'gemini-2.5-flash',
            'enable_web_search' => false,
        ]);

        Http::assertSent(function ($request) {
            $text = $request['systemInstruction']['parts'][0]['text'] ?? '';

            return ! isset($request['tools'])
                && ! str_contains($text, 'PASO 1');
        });
    }

    public function test_anthropic_does_not_attach_search_tool_when_disabled(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'Descripción generada.']],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
        ], 200)]);
        config(['services.anthropic.api_key' => 'test-key']);

        $this->client->generate('Redacta la ficha del producto X', [
            'model' => 'claude-3-5-sonnet',
            'enable_web_search' => false,
        ]);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return ! isset($body['tools'])
                && ! str_contains($body['system'] ?? '', 'busca el producto en internet');
        });
    }

    public function test_openai_uses_chat_completions_endpoint_when_search_disabled(): void
    {
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Descripción generada.']]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50],
        ], 200)]);
        config(['services.openai.api_key' => 'test-key']);

        $this->client->generate('Redacta la ficha del producto X', [
            'model' => 'gpt-4o-mini',
            'enable_web_search' => false,
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/chat/completions'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v1/responses'));
    }
}
