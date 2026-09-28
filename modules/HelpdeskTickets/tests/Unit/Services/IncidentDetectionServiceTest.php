<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Mockery;
use Modules\HelpdeskAgents\Models\TicketEmbedding;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskAgents\Services\TicketEmbeddingService;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\IncidentDetectionService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

class IncidentDetectionServiceTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketEmbeddingService $embeddings;

    private AgentLlmService $llm;

    private IncidentDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->embeddings = Mockery::mock(TicketEmbeddingService::class);
        $this->llm = Mockery::mock(AgentLlmService::class);
        $this->service = new IncidentDetectionService($this->embeddings, $this->llm);

        config(['helpdeskagents.ticket_similarity.enabled' => true]);
    }

    private function embeddingFor(int $ticketId, array $vector): TicketEmbedding
    {
        return new TicketEmbedding([
            'ticket_id' => $ticketId,
            'embedding' => $vector,
            'vector_norm' => 1.0,
            'category_id' => null,
            'created_at' => now(),
        ]);
    }

    public function test_returns_empty_when_similarity_feature_is_disabled(): void
    {
        config(['helpdeskagents.ticket_similarity.enabled' => false]);

        $this->assertTrue($this->service->detect()->isEmpty());
    }

    public function test_returns_empty_when_embeddings_are_not_available(): void
    {
        $this->embeddings->shouldReceive('isAvailable')->once()->andReturn(false);

        $this->assertTrue($this->service->detect()->isEmpty());
    }

    public function test_returns_empty_when_fewer_recent_tickets_than_min_size(): void
    {
        $this->embeddings->shouldReceive('isAvailable')->once()->andReturn(true);
        $this->embeddings->shouldReceive('recent')->once()->andReturn(collect([
            $this->embeddingFor(1, [1, 0, 0]),
            $this->embeddingFor(2, [1, 0, 0]),
        ]));

        $result = $this->service->detect(windowMinutes: 60, minSize: 5);

        $this->assertTrue($result->isEmpty());
    }

    public function test_groups_similar_tickets_into_one_incident(): void
    {
        $tickets = Ticket::factory()->count(3)->create(['subject' => 'No llega el pedido #123']);

        $this->embeddings->shouldReceive('isAvailable')->once()->andReturn(true);
        $this->embeddings->shouldReceive('recent')->once()->andReturn(collect(
            $tickets->map(fn (Ticket $t) => $this->embeddingFor($t->id, [1, 0, 0]))->all()
        ));
        // Same vector on every pair here: cosine always "matches".
        $this->embeddings->shouldReceive('cosine')->andReturn(1.0);
        $this->llm->shouldReceive('isConfigured')->andReturn(false);

        $result = $this->service->detect(windowMinutes: 60, minSize: 3);

        $this->assertCount(1, $result);
        $this->assertSame(3, $result->first()['size']);
        $this->assertEqualsCanonicalizing($tickets->pluck('id')->all(), $result->first()['ticket_ids']);
    }

    public function test_does_not_group_dissimilar_tickets(): void
    {
        $tickets = Ticket::factory()->count(3)->create();

        $this->embeddings->shouldReceive('isAvailable')->once()->andReturn(true);
        $this->embeddings->shouldReceive('recent')->once()->andReturn(collect([
            $this->embeddingFor($tickets[0]->id, [1, 0, 0]),
            $this->embeddingFor($tickets[1]->id, [0, 1, 0]),
            $this->embeddingFor($tickets[2]->id, [0, 0, 1]),
        ]));
        // Every pair scores below the threshold: three singleton groups.
        $this->embeddings->shouldReceive('cosine')->andReturn(0.0);

        $result = $this->service->detect(windowMinutes: 60, minSize: 2);

        $this->assertTrue($result->isEmpty());
    }

    public function test_falls_back_to_the_seed_ticket_subject_when_llm_is_not_configured(): void
    {
        $tickets = Ticket::factory()->count(3)->create(['subject' => 'No llega el pedido #123']);

        $this->embeddings->shouldReceive('isAvailable')->once()->andReturn(true);
        $this->embeddings->shouldReceive('recent')->once()->andReturn(collect(
            $tickets->map(fn (Ticket $t) => $this->embeddingFor($t->id, [1, 0, 0]))->all()
        ));
        $this->embeddings->shouldReceive('cosine')->andReturn(1.0);
        $this->llm->shouldReceive('isConfigured')->once()->andReturn(false);

        $result = $this->service->detect(windowMinutes: 60, minSize: 3);

        $this->assertSame('No llega el pedido #123', $result->first()['label']);
    }
}
