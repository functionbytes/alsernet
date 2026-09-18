<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskTickets\Services\SpamClassifierService;
use Tests\TestCase;

/**
 * Clasificador de spam.
 *
 * Su falso positivo es un cliente real cuyo correo se queda fuera, así que
 * todo lo que se prueba aquí es la contención: apagado por defecto, umbral
 * alto, y una respuesta dudosa no retiene nada.
 */
class SpamClassifierServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Http::fake();
    }

    /** @return array{score: float, reason: string}|null */
    private function parse(?string $raw): ?array
    {
        $method = new \ReflectionMethod(SpamClassifierService::class, 'parse');
        $method->setAccessible(true);

        return $method->invoke(app(SpamClassifierService::class), $raw);
    }

    public function test_it_is_disabled_by_default(): void
    {
        // "Do no harm": nada retiene correo hasta que alguien lo enciende a
        // conciencia.
        $this->assertFalse(config('helpdesktickets.spam_classifier.enabled'));
        $this->assertFalse(app(SpamClassifierService::class)->enabled());
    }

    public function test_the_default_threshold_is_high(): void
    {
        // Ante la duda el correo entra: cerrar un ticket basura cuesta menos
        // que perder un pedido en cuarentena.
        $this->assertGreaterThanOrEqual(0.85, (float) config('helpdesktickets.spam_classifier.threshold'));
    }

    public function test_a_spam_verdict_is_parsed(): void
    {
        $result = $this->parse('{"spam": true, "score": 0.95, "reason": "Publicidad masiva"}');

        $this->assertSame(0.95, $result['score']);
        $this->assertSame('Publicidad masiva', $result['reason']);
    }

    public function test_a_not_spam_verdict_yields_null(): void
    {
        // Solo un `spam: true` explícito cuenta.
        $this->assertNull($this->parse('{"spam": false, "score": 0.2, "reason": "Consulta legítima"}'));
    }

    public function test_a_verdict_without_score_is_discarded(): void
    {
        $this->assertNull($this->parse('{"spam": true, "reason": "Parece spam"}'));
    }

    public function test_an_unparseable_answer_yields_null(): void
    {
        $this->assertNull($this->parse('Creo que sí es spam'));
        $this->assertNull($this->parse(null));
    }

    public function test_the_score_is_clamped(): void
    {
        $this->assertSame(1.0, $this->parse('{"spam": true, "score": 3}')['score']);
        $this->assertSame(0.0, $this->parse('{"spam": true, "score": -1}')['score']);
    }
}
