<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskTickets\Services\TicketSentimentService;
use Tests\TestCase;

/**
 * Sentimiento del cliente: LLM con las listas de palabras como red.
 *
 * Lo que se protege es que la red de seguridad siga ahí (sin LLM el sistema no
 * se queda a ciegas) y que una respuesta malformada del modelo no acabe en una
 * columna que alimenta medias y avisos.
 */
class TicketSentimentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'helpdeskagents.ai_usage.enabled' => false,
            'helpdeskagents.ai_usage.daily_max_calls' => 0,
            'helpdeskagents.ai_usage.daily_max_tokens' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        cache()->forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);

        parent::tearDown();
    }

    private function configureAgent(): void
    {
        $agent = new AiAgent(['name' => 'T', 'provider' => 'anthropic', 'model' => 'm']);
        $agent->setRawAttributes(['api_key_encrypted' => 'sk-test'] + $agent->getAttributes(), true);

        cache()->put(AgentLlmService::DEFAULT_AGENT_CACHE_KEY, $agent, 300);
    }

    private function fakeAnswer(string $json): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => $json]],
        ])]);
    }

    private function service(): TicketSentimentService
    {
        return app(TicketSentimentService::class);
    }

    public function test_it_uses_the_llm_when_available(): void
    {
        $this->configureAgent();
        $this->fakeAnswer('{"sentiment": "negative", "score": -0.8}');

        $result = $this->service()->analyze('Das ist inakzeptabel, ich bin sehr verärgert.');

        $this->assertSame('negative', $result['sentiment']);
        $this->assertSame(-0.8, $result['score']);
        $this->assertSame('llm', $result['source']);
    }

    public function test_without_an_agent_it_falls_back_to_keywords(): void
    {
        cache()->forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);
        Http::fake();

        $result = $this->service()->analyze('Esto es horrible, no funciona nada');

        $this->assertSame('negative', $result['sentiment']);
        $this->assertSame('keywords', $result['source']);
        // El heurístico es gratis y local: no debe salir ninguna petición.
        Http::assertNothingSent();
    }

    public function test_a_malformed_answer_falls_back_instead_of_writing_garbage(): void
    {
        $this->configureAgent();
        $this->fakeAnswer('me parece que está enfadado');

        $result = $this->service()->analyze('Esto es horrible');

        $this->assertSame('keywords', $result['source']);
    }

    public function test_an_invented_label_is_rejected(): void
    {
        $this->configureAgent();
        // "furioso" no está en la lista cerrada: la columna solo admite
        // positive/neutral/negative.
        $this->fakeAnswer('{"sentiment": "furioso", "score": -0.9}');

        $this->assertSame('keywords', $this->service()->analyze('Texto cualquiera')['source']);
    }

    public function test_the_score_is_clamped_to_the_stored_scale(): void
    {
        $this->configureAgent();
        // Fuera de -1..1 rompería la media ya almacenada.
        $this->fakeAnswer('{"sentiment": "negative", "score": -7}');

        $this->assertSame(-1.0, $this->service()->analyze('Texto')['score']);
    }

    public function test_empty_text_is_neutral_without_calling_anything(): void
    {
        $this->configureAgent();
        Http::fake();

        $result = $this->service()->analyze('   ');

        $this->assertSame('neutral', $result['sentiment']);
        $this->assertSame('empty', $result['source']);
        Http::assertNothingSent();
    }

    /**
     * Las dos mitades del mismo motivo, en tests separados: mezclar
     * Http::fake() sin argumentos (que registra un catch-all '*') con un
     * Http::fake(['host' => ...]) en el mismo test hace que gane el catch-all.
     */
    public function test_without_an_llm_a_french_complaint_looks_neutral(): void
    {
        cache()->forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);
        Http::fake();

        // Las listas de TicketAiService solo cubren español e inglés, así que
        // un cliente furioso en francés se registraba como calmado.
        $this->assertSame('neutral', $this->service()->analyze("C'est inacceptable, je suis furieux")['sentiment']);
    }

    public function test_with_an_llm_the_same_complaint_is_detected(): void
    {
        $this->configureAgent();
        $this->fakeAnswer('{"sentiment": "negative", "score": -0.85}');

        $this->assertSame('negative', $this->service()->analyze("C'est inacceptable, je suis furieux")['sentiment']);
    }
}
