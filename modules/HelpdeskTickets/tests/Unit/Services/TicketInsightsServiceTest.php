<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\HelpdeskTickets\Services\TicketInsightsService;
use Tests\TestCase;

/**
 * Lecturas en lenguaje natural de los informes.
 *
 * Un informe con una cifra inventada es peor que un informe sin comentario:
 * las decisiones se toman sobre él. De ahí que el prompt reciba los totales ya
 * calculados y que el recuento de menciones se acote al número real de
 * comentarios analizados.
 */
class TicketInsightsServiceTest extends TestCase
{
    /** @return array<int, array<string, mixed>>|null */
    private function parseThemes(?string $raw, int $total): ?array
    {
        $method = new \ReflectionMethod(TicketInsightsService::class, 'parseThemes');
        $method->setAccessible(true);

        return $method->invoke(app(TicketInsightsService::class), $raw, $total);
    }

    /** @return array<string, mixed> */
    private function trimSummary(array $summary): array
    {
        $method = new \ReflectionMethod(TicketInsightsService::class, 'trimSummary');
        $method->setAccessible(true);

        return $method->invoke(app(TicketInsightsService::class), $summary);
    }

    public function test_it_parses_themes(): void
    {
        $raw = '[{"tema": "Envíos que tardan más de lo prometido", "menciones": 5, "ejemplo": "Llegó tres días tarde"}]';

        $themes = $this->parseThemes($raw, 12);

        $this->assertCount(1, $themes);
        $this->assertSame(5, $themes[0]['menciones']);
        $this->assertSame('Llegó tres días tarde', $themes[0]['ejemplo']);
    }

    public function test_mentions_cannot_exceed_the_comments_analysed(): void
    {
        // Un modelo que dice "12 menciones" sobre 7 comentarios convierte el
        // informe en ficción.
        $themes = $this->parseThemes('[{"tema": "Retrasos", "menciones": 12, "ejemplo": ""}]', 7);

        $this->assertSame(7, $themes[0]['menciones']);
    }

    public function test_mentions_are_at_least_one(): void
    {
        $themes = $this->parseThemes('[{"tema": "Retrasos", "menciones": 0, "ejemplo": ""}]', 7);

        $this->assertSame(1, $themes[0]['menciones']);
    }

    public function test_a_theme_without_text_is_discarded(): void
    {
        $this->assertNull($this->parseThemes('[{"tema": "  ", "menciones": 3}]', 5));
    }

    public function test_at_most_six_themes_survive(): void
    {
        $rows = array_fill(0, 12, ['tema' => 'Algo', 'menciones' => 2, 'ejemplo' => '']);

        $this->assertCount(6, $this->parseThemes(json_encode($rows), 20));
    }

    public function test_an_unparseable_answer_yields_null(): void
    {
        $this->assertNull($this->parseThemes('Los clientes se quejan de los envíos', 10));
        $this->assertNull($this->parseThemes(null, 10));
    }

    public function test_the_summary_sent_to_the_model_drops_the_heavy_keys(): void
    {
        $trimmed = $this->trimSummary([
            'totalCreated' => 340,
            'csatAvg' => 4.2,
            // Listas largas que multiplican los tokens de entrada sin cambiar
            // el comentario.
            'topAgents' => collect(range(1, 50)),
            'ratingDistribution' => collect(range(1, 5)),
        ]);

        $this->assertSame(340, $trimmed['tickets_creados']);
        $this->assertSame(4.2, $trimmed['csat_medio']);
        $this->assertArrayNotHasKey('topAgents', $trimmed);
        $this->assertArrayNotHasKey('ratingDistribution', $trimmed);
    }

    public function test_null_values_are_not_sent(): void
    {
        $trimmed = $this->trimSummary(['totalCreated' => 10]);

        // Solo lo que existe: una clave a null invita al modelo a comentarla.
        $this->assertSame(['tickets_creados' => 10], $trimmed);
    }
}
