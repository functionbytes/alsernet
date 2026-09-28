<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Mockery;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAgents\Services\TicketEmbeddingService;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Services\TicketDuplicateService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

class TicketDuplicateServiceTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketDuplicateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TicketDuplicateService;
    }

    // ─── candidatesFor() (embeddings) ──────────────────────────────────────

    public function test_candidates_for_returns_empty_when_similarity_feature_disabled(): void
    {
        config(['helpdeskagents.ticket_similarity.enabled' => false]);

        $ticket = Ticket::factory()->create();

        $this->assertTrue($this->service->candidatesFor($ticket)->isEmpty());
    }

    public function test_candidates_for_returns_empty_when_no_embeddings_api_key_configured(): void
    {
        config([
            'helpdeskagents.ticket_similarity.enabled' => true,
            'helpdeskagents.embeddings.api_key' => null,
            'services.openai.key' => null,
        ]);

        $ticket = Ticket::factory()->create();

        $this->assertTrue($this->service->candidatesFor($ticket)->isEmpty());
    }

    public function test_candidates_for_maps_matches_and_flags_same_customer(): void
    {
        config(['helpdeskagents.ticket_similarity.enabled' => true]);

        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);
        $sameCustomerMatch = Ticket::factory()->create(['customer_id' => $customer->id]);
        $otherCustomerMatch = Ticket::factory()->create(['customer_id' => Customer::factory()->create()->id]);

        $embeddings = Mockery::mock(TicketEmbeddingService::class);
        $embeddings->shouldReceive('isAvailable')->andReturn(true);
        $embeddings->shouldReceive('similarTo')->once()->andReturn(collect([
            ['ticket_id' => $sameCustomerMatch->id, 'similarity' => 0.91],
            ['ticket_id' => $otherCustomerMatch->id, 'similarity' => 0.95],
        ]));
        $this->app->instance(TicketEmbeddingService::class, $embeddings);

        $candidates = $this->service->candidatesFor($ticket);

        $this->assertCount(2, $candidates);

        $sameCustomerResult = $candidates->firstWhere('ticket.id', $sameCustomerMatch->id);
        $this->assertTrue($sameCustomerResult['same_customer']);

        $otherCustomerResult = $candidates->firstWhere('ticket.id', $otherCustomerMatch->id);
        $this->assertFalse($otherCustomerResult['same_customer']);
    }

    public function test_candidates_for_excludes_closed_tickets(): void
    {
        config(['helpdeskagents.ticket_similarity.enabled' => true]);

        $ticket = Ticket::factory()->create();
        $closedMatch = Ticket::factory()->closed()->create();

        $embeddings = Mockery::mock(TicketEmbeddingService::class);
        $embeddings->shouldReceive('isAvailable')->andReturn(true);
        $embeddings->shouldReceive('similarTo')->once()->andReturn(collect([
            ['ticket_id' => $closedMatch->id, 'similarity' => 0.95],
        ]));
        $this->app->instance(TicketEmbeddingService::class, $embeddings);

        $this->assertTrue($this->service->candidatesFor($ticket)->isEmpty());
    }

    public function test_candidates_for_excludes_tickets_already_linked(): void
    {
        config(['helpdeskagents.ticket_similarity.enabled' => true]);

        $ticket = Ticket::factory()->create();
        $alreadyLinked = Ticket::factory()->create();

        TicketLink::query()->create([
            'ticket_id' => $ticket->id,
            'linked_ticket_id' => $alreadyLinked->id,
            'link_type' => 'duplicate',
        ]);

        $embeddings = Mockery::mock(TicketEmbeddingService::class);
        $embeddings->shouldReceive('isAvailable')->andReturn(true);
        $embeddings->shouldReceive('similarTo')->once()->andReturn(collect([
            ['ticket_id' => $alreadyLinked->id, 'similarity' => 0.95],
        ]));
        $this->app->instance(TicketEmbeddingService::class, $embeddings);

        $this->assertTrue($this->service->candidatesFor($ticket)->isEmpty());
    }

    // ─── candidatesByText() (no AI) ────────────────────────────────────────

    public function test_candidates_by_text_returns_empty_for_blank_subject(): void
    {
        $customer = Customer::factory()->create();

        $this->assertTrue($this->service->candidatesByText('   ', $customer->id)->isEmpty());
    }

    public function test_candidates_by_text_returns_empty_without_a_customer_id(): void
    {
        $this->assertTrue($this->service->candidatesByText('No puedo entrar a mi cuenta', null)->isEmpty());
    }

    public function test_candidates_by_text_finds_a_near_identical_recent_subject_for_the_same_customer(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'subject' => 'No puedo acceder a mi cuenta']);

        $duplicate = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Re: No puedo acceder a mi cuenta',
        ]);

        $candidates = $this->service->candidatesByText(
            'No puedo acceder a mi cuenta',
            $ticket->customer_id,
            $ticket->id,
        );

        $this->assertCount(1, $candidates);
        $this->assertSame($duplicate->id, $candidates->first()['ticket']->id);
        $this->assertTrue($candidates->first()['same_customer']);
    }

    public function test_candidates_by_text_ignores_unrelated_subjects_below_threshold(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'subject' => 'No puedo acceder a mi cuenta']);

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Consulta sobre facturación de octubre',
        ]);

        $candidates = $this->service->candidatesByText(
            'No puedo acceder a mi cuenta',
            $ticket->customer_id,
            $ticket->id,
        );

        $this->assertTrue($candidates->isEmpty());
    }

    public function test_candidates_by_text_ignores_matches_from_a_different_customer(): void
    {
        $ticket = Ticket::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'subject' => 'No puedo acceder a mi cuenta',
        ]);

        Ticket::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'subject' => 'No puedo acceder a mi cuenta',
        ]);

        $candidates = $this->service->candidatesByText(
            'No puedo acceder a mi cuenta',
            $ticket->customer_id,
            $ticket->id,
        );

        $this->assertTrue($candidates->isEmpty());
    }

    public function test_candidates_by_text_ignores_closed_tickets(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'subject' => 'No puedo acceder a mi cuenta']);

        Ticket::factory()->closed()->create([
            'customer_id' => $customer->id,
            'subject' => 'No puedo acceder a mi cuenta',
        ]);

        $candidates = $this->service->candidatesByText(
            'No puedo acceder a mi cuenta',
            $ticket->customer_id,
            $ticket->id,
        );

        $this->assertTrue($candidates->isEmpty());
    }

    public function test_candidates_by_text_excludes_the_ticket_itself(): void
    {
        $ticket = Ticket::factory()->create(['subject' => 'No puedo acceder a mi cuenta']);

        $candidates = $this->service->candidatesByText(
            'No puedo acceder a mi cuenta',
            $ticket->customer_id,
            $ticket->id,
        );

        $this->assertTrue($candidates->isEmpty());
    }
}
