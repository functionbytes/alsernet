<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\HelpdeskTickets\Services\TicketQualityReviewService;
use Tests\TestCase;

/**
 * Revisión de calidad por muestreo.
 *
 * Esto pone nota al trabajo de una persona, así que lo que se protege es que
 * ninguna salida rara del modelo acabe convertida en una puntuación: fuera de
 * rango, ejes inventados o respuestas sin JSON no producen revisión.
 */
class TicketQualityReviewServiceTest extends TestCase
{
    /** @return array<string, mixed>|null */
    private function parse(?string $raw): ?array
    {
        $method = new \ReflectionMethod(TicketQualityReviewService::class, 'parse');
        $method->setAccessible(true);

        return $method->invoke(app(TicketQualityReviewService::class), $raw);
    }

    public function test_it_parses_a_complete_review(): void
    {
        $raw = '{"score": 4, "dimensions": {"resolucion": 5, "precision": 4, "tono": 4, "claridad": 3},
                 "summary": "Se resolvió bien", "issues": ["Podría haber citado el plazo"]}';

        $review = $this->parse($raw);

        $this->assertSame(4, $review['score']);
        $this->assertSame(5, $review['dimensions']['resolucion']);
        $this->assertSame(['Podría haber citado el plazo'], $review['issues']);
    }

    public function test_scores_out_of_range_are_clamped(): void
    {
        $this->assertSame(5, $this->parse('{"score": 9}')['score']);
        $this->assertSame(1, $this->parse('{"score": 0}')['score']);
        $this->assertSame(1, $this->parse('{"score": -3}')['score']);
    }

    public function test_invented_dimensions_are_dropped(): void
    {
        $raw = '{"score": 4, "dimensions": {"resolucion": 5, "simpatia": 5, "karma": 2}}';

        $dimensions = $this->parse($raw)['dimensions'];

        // Lista cerrada: los ejes los fija el sistema, no el modelo.
        $this->assertSame(['resolucion' => 5], $dimensions);
    }

    public function test_dimension_values_are_clamped_too(): void
    {
        $raw = '{"score": 3, "dimensions": {"tono": 99, "claridad": -4}}';

        $dimensions = $this->parse($raw)['dimensions'];

        $this->assertSame(5, $dimensions['tono']);
        $this->assertSame(1, $dimensions['claridad']);
    }

    public function test_an_empty_issues_list_becomes_null(): void
    {
        // Sin nada que señalar es el caso NORMAL, no un fallo.
        $this->assertNull($this->parse('{"score": 4, "issues": []}')['issues']);
        $this->assertNull($this->parse('{"score": 4}')['issues']);
    }

    public function test_issues_are_capped(): void
    {
        $issues = json_encode(array_fill(0, 12, 'algo que mejorar'));

        $this->assertCount(5, $this->parse('{"score": 3, "issues": '.$issues.'}')['issues']);
    }

    public function test_a_review_without_a_score_is_discarded(): void
    {
        // Sin nota no hay revisión: el resto sin la puntuación no mide nada.
        $this->assertNull($this->parse('{"summary": "Bien atendido"}'));
    }

    public function test_a_non_json_answer_is_discarded(): void
    {
        $this->assertNull($this->parse('Me parece que el agente lo hizo bien'));
        $this->assertNull($this->parse(null));
    }

    public function test_it_is_disabled_by_default(): void
    {
        $this->assertFalse(config('helpdesktickets.quality_review.enabled'));
        $this->assertFalse(app(TicketQualityReviewService::class)->isAvailable());
    }
}
