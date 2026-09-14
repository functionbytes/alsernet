<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Services;

use Modules\HelpdeskAgents\Services\TicketEmbeddingService;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * Matemática y criterio de la similitud entre tickets.
 *
 * El detalle que decide si esto funciona no es el coseno, que es aritmética,
 * sino QUÉ texto se vectoriza: incluir las respuestas del agente haría que
 * todos los tickets se parecieran entre sí, porque el equipo responde siempre
 * con las mismas plantillas.
 */
class TicketEmbeddingServiceTest extends TestCase
{
    private function service(): TicketEmbeddingService
    {
        return app(TicketEmbeddingService::class);
    }

    public function test_identical_vectors_score_one(): void
    {
        $v = [0.1, 0.5, -0.3];
        $service = $this->service();

        $this->assertEqualsWithDelta(1.0, $service->cosine($v, $service->norm($v), $v, $service->norm($v)), 0.0001);
    }

    public function test_orthogonal_vectors_score_zero(): void
    {
        $service = $this->service();
        $a = [1.0, 0.0];
        $b = [0.0, 1.0];

        $this->assertEqualsWithDelta(0.0, $service->cosine($a, $service->norm($a), $b, $service->norm($b)), 0.0001);
    }

    public function test_similar_vectors_score_high(): void
    {
        $service = $this->service();
        $a = [0.9, 0.1, 0.2];
        $b = [0.88, 0.12, 0.19];

        $this->assertGreaterThan(0.99, $service->cosine($a, $service->norm($a), $b, $service->norm($b)));
    }

    public function test_vectors_of_different_length_score_zero(): void
    {
        $service = $this->service();

        // Distinto modelo de embeddings: los espacios no son comparables y su
        // coseno no significa nada.
        $this->assertSame(0.0, $service->cosine([1.0, 2.0], 2.23, [1.0, 2.0, 3.0], 3.74));
    }

    public function test_a_zero_norm_scores_zero_instead_of_dividing_by_zero(): void
    {
        $service = $this->service();

        $this->assertSame(0.0, $service->cosine([0.0, 0.0], 0.0, [1.0, 1.0], 1.41));
    }

    public function test_the_norm_is_the_euclidean_length(): void
    {
        $this->assertEqualsWithDelta(5.0, $this->service()->norm([3.0, 4.0]), 0.0001);
    }

    public function test_the_indexed_text_is_the_subject_plus_the_customer_message(): void
    {
        $ticket = new Ticket;
        $ticket->subject = 'Mi pedido no ha llegado';
        $ticket->description = 'Lo pedí hace dos semanas.';
        // Sin items cargados cae a la descripción, que es el mismo texto que
        // escribió el cliente.
        $ticket->setRelation('items', collect());

        $text = $this->service()->textFor($ticket);

        $this->assertStringContainsString('Mi pedido no ha llegado', $text);
        $this->assertStringContainsString('Lo pedí hace dos semanas', $text);
    }

    public function test_it_reports_unavailable_without_an_embeddings_key(): void
    {
        config(['helpdeskagents.embeddings.api_key' => null, 'services.openai.key' => '']);

        // El servicio se reconstruye para que EmbeddingService relea la config.
        app()->forgetInstance(TicketEmbeddingService::class);

        $this->assertFalse(app(TicketEmbeddingService::class)->isAvailable());
    }
}
