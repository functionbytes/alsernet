<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAgents\Jobs\DetectTicketLanguageJob;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

/**
 * Orden de detección de idioma del ticket.
 *
 * La vía buena es CachedTranslator (DeepL por defecto, con su fallback a
 * LibreTranslate, su circuit breaker, su cupo y su caché en BD). El LLM es el
 * ÚLTIMO recurso: pagar tokens por nombrar un idioma que DeepL ya devuelve
 * gratis en su respuesta de traducción es gasto tirado, así que lo que se
 * protege aquí es que ese orden no se invierta.
 */
class DetectTicketLanguageFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'helpdeskagents.ticket_ai.language_detection_llm_fallback' => true,
            'helpdeskagents.ai_usage.enabled' => false,
            'helpdeskagents.ai_usage.daily_max_calls' => 0,
            'helpdeskagents.ai_usage.daily_max_tokens' => 0,
        ]);

        $agent = new AiAgent(['name' => 'Test', 'provider' => 'anthropic', 'model' => 'test-model']);
        $agent->setRawAttributes(['api_key_encrypted' => 'sk-test'] + $agent->getAttributes(), true);

        cache()->put(AgentLlmService::DEFAULT_AGENT_CACHE_KEY, $agent, 300);
    }

    protected function tearDown(): void
    {
        cache()->forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);

        parent::tearDown();
    }

    /**
     * Sustituye el traductor del helpdesk por uno que devuelve $returns.
     *
     * @param  string|null  $returns  código detectado, o null si ningún proveedor respondió
     */
    private function fakeTranslator(?string $returns, ?string $expectedFeature = 'auto_incoming'): void
    {
        $mock = $this->mock(CachedTranslator::class);

        $expectation = $mock->shouldReceive('detectLanguage')->andReturn($returns);

        if ($expectedFeature !== null) {
            // El feature importa: es lo que mete esta detección en el mismo
            // cupo y el mismo reporte de consumo que el resto de la ingesta.
            $expectation->withArgs(fn ($text, $feature = null) => $feature === $expectedFeature);
        }
    }

    private function detect(string $text): ?string
    {
        $job = new DetectTicketLanguageJob(1);
        $method = new \ReflectionMethod($job, 'detectWithLlm');
        $method->setAccessible(true);

        return $method->invoke($job, $text);
    }

    private function fakeLlmAnswer(string $answer): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => $answer]],
        ])]);
    }

    // ── Orden de preferencia ────────────────────────────────────────

    public function test_the_translator_answers_first_and_the_llm_never_runs(): void
    {
        $this->fakeTranslator('fr');
        Http::fake();

        $job = new DetectTicketLanguageJob(1);
        $method = new \ReflectionMethod($job, 'detectWithTranslator');
        $method->setAccessible(true);

        $this->assertSame('fr', $method->invoke($job, 'Bonjour, ma commande nest pas arrivee.'));

        // Sin llamada al LLM: DeepL ya lo resolvió.
        Http::assertNothingSent();
    }

    public function test_the_translator_returning_nothing_leaves_the_llm_as_last_resort(): void
    {
        $this->fakeTranslator(null);

        $job = new DetectTicketLanguageJob(1);
        $method = new \ReflectionMethod($job, 'detectWithTranslator');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($job, 'Bonjour, ma commande nest pas arrivee.'));
    }

    // ── Guardas del último recurso ──────────────────────────────────

    public function test_it_returns_the_language_code(): void
    {
        $this->fakeLlmAnswer('fr');

        $this->assertSame('fr', $this->detect('Bonjour, ma commande nest pas arrivee.'));
    }

    public function test_surrounding_whitespace_and_punctuation_are_tolerated(): void
    {
        $this->fakeLlmAnswer("  FR.\n");

        $this->assertSame('fr', $this->detect('Bonjour tout le monde'));
    }

    public function test_a_chatty_answer_is_discarded_rather_than_mined_for_a_code(): void
    {
        $this->fakeLlmAnswer('El idioma es fr.');

        // Tentador aceptarlo, pero buscar un codigo DENTRO de una frase es lo
        // que hace que "No estoy seguro" se selle como noruego. Se exige la
        // respuesta entera.
        $this->assertNull($this->detect('Bonjour tout le monde'));
    }

    public function test_a_hedge_containing_a_valid_code_is_discarded(): void
    {
        // "no" es noruego en ISO 639-1: sin fail-closed, esta respuesta
        // enrutaria el ticket a quien hable noruego.
        $this->fakeLlmAnswer('No estoy seguro del idioma utilizado.');

        $this->assertNull($this->detect('Texto de prueba suficientemente largo.'));
    }

    public function test_the_fallback_can_be_switched_off(): void
    {
        config(['helpdeskagents.ticket_ai.language_detection_llm_fallback' => false]);
        Http::fake();

        $this->assertNull($this->detect('Bonjour tout le monde'));
        Http::assertNothingSent();
    }

    public function test_only_a_short_sample_of_the_text_is_sent(): void
    {
        $this->fakeLlmAnswer('es');

        $this->detect(str_repeat('palabra ', 500));

        // Esto corre en CADA ticket nuevo: mandar el mensaje entero para
        // nombrar un idioma es gasto puro.
        $sent = Http::recorded()->last()[0]->data();
        $this->assertLessThanOrEqual(400, mb_strlen($sent['messages'][0]['content']));
        $this->assertSame(5, $sent['max_tokens']);
    }
}
