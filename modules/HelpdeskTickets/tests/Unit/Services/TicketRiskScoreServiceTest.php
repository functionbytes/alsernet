<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TicketRiskScoreService;
use Tests\TestCase;

/**
 * Riesgo del ticket y su efecto sobre el escalado.
 *
 * Lo crítico es que ACORTE y nunca alargue: activar esto no puede retrasar
 * ningún escalado que ya ocurría. Un ticket sin señales debe escalar
 * exactamente cuando escalaba antes.
 */
class TicketRiskScoreServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'helpdesktickets.risk.min_score' => 0.4,
            'helpdesktickets.risk.max_reduction' => 0.25,
        ]);
    }

    private function service(): TicketRiskScoreService
    {
        return app(TicketRiskScoreService::class);
    }

    /** @return array{key: string, weight: float, detail: string}|null */
    private function sentimentFactor(?float $avg): ?array
    {
        $ticket = new Ticket;
        $ticket->customer_sentiment_avg = $avg;

        $method = new \ReflectionMethod(TicketRiskScoreService::class, 'sentimentFactor');
        $method->setAccessible(true);

        return $method->invoke($this->service(), $ticket);
    }

    private function multiplierForScore(float $score): float
    {
        $threshold = (float) config('helpdesktickets.risk.min_score');

        if ($score < $threshold) {
            return 1.0;
        }

        return max(0.25, 1.0 - ($score * 0.75));
    }

    public function test_a_calm_customer_adds_no_risk(): void
    {
        $this->assertNull($this->sentimentFactor(0.5));
        $this->assertNull($this->sentimentFactor(0.0));
        // -0.2 es el suelo: un roce puntual no es descontento.
        $this->assertNull($this->sentimentFactor(-0.2));
    }

    public function test_an_unanalysed_ticket_adds_no_risk(): void
    {
        // Sin sentimiento calculado no se supone nada.
        $this->assertNull($this->sentimentFactor(null));
    }

    public function test_an_angry_customer_adds_risk_proportionally(): void
    {
        $mild = $this->sentimentFactor(-0.5);
        $severe = $this->sentimentFactor(-1.0);

        $this->assertNotNull($mild);
        $this->assertGreaterThan($mild['weight'], $severe['weight']);
        $this->assertLessThanOrEqual(0.35, $severe['weight']);
    }

    public function test_below_the_threshold_the_deadline_is_untouched(): void
    {
        // La garantía central: sin riesgo suficiente, el plazo es el de antes.
        $this->assertSame(1.0, $this->multiplierForScore(0.0));
        $this->assertSame(1.0, $this->multiplierForScore(0.39));
    }

    public function test_the_multiplier_only_ever_shortens(): void
    {
        foreach ([0.4, 0.6, 0.8, 1.0] as $score) {
            $this->assertLessThanOrEqual(1.0, $this->multiplierForScore($score));
        }
    }

    public function test_the_deadline_never_drops_below_a_quarter(): void
    {
        // Sin suelo, un riesgo alto escalaría casi al instante y el escalado
        // dejaría de significar nada.
        $this->assertSame(0.25, $this->multiplierForScore(1.0));
        $this->assertGreaterThanOrEqual(0.25, $this->multiplierForScore(0.99));
    }

    public function test_a_ticket_with_no_signals_scores_zero(): void
    {
        $ticket = new Ticket;
        $ticket->customer_sentiment_avg = null;
        $ticket->customer_id = null;
        $ticket->sla_resolution_breached = false;
        $ticket->sla_resolution_due_at = null;
        $ticket->setRelation('items', collect());

        $result = $this->service()->score($ticket);

        $this->assertSame(0.0, $result['score']);
        $this->assertSame([], $result['factors']);
    }

    public function test_escalation_by_risk_is_disabled_by_default(): void
    {
        $this->assertFalse(config('helpdesktickets.risk.enabled'));
    }
}
