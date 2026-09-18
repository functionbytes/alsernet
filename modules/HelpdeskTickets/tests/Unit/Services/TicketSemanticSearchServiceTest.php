<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\HelpdeskAgents\Services\TicketEmbeddingService;
use Modules\HelpdeskTickets\Services\TicketSemanticSearchService;
use Tests\TestCase;

/**
 * Búsqueda semántica de tickets.
 *
 * Complementa al buscador literal, no lo sustituye, y lo que se protege es
 * justo eso: que las consultas que la gente hace la mitad de las veces —un
 * número de ticket, una referencia— sigan yendo por el camino exacto en vez de
 * gastar una llamada para devolver ruido.
 */
class TicketSemanticSearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['helpdeskagents.ticket_similarity.enabled' => true]);
    }

    private function service(): TicketSemanticSearchService
    {
        return app(TicketSemanticSearchService::class);
    }

    public function test_a_ticket_number_is_left_to_the_literal_search(): void
    {
        // Un vector no aporta nada aquí: el LIKE lo encuentra exacto.
        $this->assertSame([], $this->service()->search('TCK-2026-00123'));
    }

    public function test_a_reference_like_token_is_left_to_the_literal_search(): void
    {
        $this->assertSame([], $this->service()->search('REF/2026-ABC#4'));
    }

    public function test_a_short_query_is_left_to_the_literal_search(): void
    {
        $this->assertSame([], $this->service()->search('pago'));
        $this->assertSame([], $this->service()->search('no funciona'));
    }

    public function test_it_does_nothing_while_similarity_is_disabled(): void
    {
        config(['helpdeskagents.ticket_similarity.enabled' => false]);

        $this->assertFalse($this->service()->isAvailable());
        $this->assertSame([], $this->service()->search('el cliente no podía pagar con su tarjeta'));
    }

    public function test_it_needs_an_embeddings_key(): void
    {
        config([
            'helpdeskagents.embeddings.api_key' => null,
            'services.openai.key' => '',
        ]);

        app()->forgetInstance(TicketSemanticSearchService::class);
        app()->forgetInstance(TicketEmbeddingService::class);

        $this->assertFalse(app(TicketSemanticSearchService::class)->isAvailable());
    }
}
