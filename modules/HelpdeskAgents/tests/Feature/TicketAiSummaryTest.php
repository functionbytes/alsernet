<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskAgents\Jobs\GenerateTicketSummaryJob;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskAgents\Services\TicketAiContextBuilder;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Models\TicketItem;
use Tests\TestCase;

class TicketAiSummaryTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Cache::forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function createDefaultAgent(): AiAgent
    {
        $agent = AiAgent::factory()->default()->create([
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'parameters' => ['api_key' => 'test-key'],
        ]);

        Cache::forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);

        return $agent;
    }

    private function createTicket(): Ticket
    {
        $ticket = Ticket::factory()->create(['priority' => 'normal']);

        TicketItem::query()->create([
            'ticket_id' => $ticket->id,
            'type' => 'message',
            'body' => 'Mi pedido llegó dañado y necesito una devolución.',
            'is_internal' => false,
        ]);

        return $ticket;
    }

    private function runJob(Ticket $ticket, string $trigger = 'assigned'): void
    {
        (new GenerateTicketSummaryJob($ticket->id, $trigger))->handle(
            app(AgentLlmService::class),
            app(TicketAiContextBuilder::class),
        );
    }

    private function summaryNotes(Ticket $ticket)
    {
        return TicketItem::query()
            ->where('ticket_id', $ticket->id)
            ->where('type', 'note')
            ->where('is_internal', true)
            ->get()
            ->filter(fn ($item) => (bool) data_get($item->metadata, 'ai_summary'))
            ->values();
    }

    public function test_ticket_assigned_event_queues_summary_job(): void
    {
        Queue::fake();

        $ticket = $this->createTicket();
        $agent = User::factory()->create();

        event(new TicketAssigned($ticket, $agent));

        Queue::assertPushed(
            GenerateTicketSummaryJob::class,
            fn (GenerateTicketSummaryJob $job) => $job->ticketId === $ticket->id && $job->trigger === 'assigned'
        );
    }

    public function test_escalation_history_row_queues_summary_job(): void
    {
        Queue::fake();

        $ticket = $this->createTicket();

        TicketHistory::logAction($ticket, 'escalated', null, [
            'old_priority' => 'normal',
            'new_priority' => 'high',
            'reason' => 'sla',
        ]);

        Queue::assertPushed(
            GenerateTicketSummaryJob::class,
            fn (GenerateTicketSummaryJob $job) => $job->ticketId === $ticket->id && $job->trigger === 'escalated'
        );
    }

    public function test_non_escalation_history_rows_do_not_queue_summary(): void
    {
        Queue::fake();

        $ticket = $this->createTicket();

        TicketHistory::logAction($ticket, 'assigned', null, ['assignee_id' => 1]);

        Queue::assertNotPushed(GenerateTicketSummaryJob::class);
    }

    public function test_job_stores_summary_as_internal_note(): void
    {
        $this->createDefaultAgent();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'El cliente reporta un pedido dañado y pide devolución.']]],
            ]),
        ]);

        $ticket = $this->createTicket();

        $this->runJob($ticket, 'escalated');

        $notes = $this->summaryNotes($ticket);

        $this->assertCount(1, $notes);
        $this->assertStringContainsString('[Resumen IA]', $notes[0]->body);
        $this->assertStringContainsString('pedido dañado', $notes[0]->body);
        $this->assertSame('escalated', data_get($notes[0]->metadata, 'trigger'));
    }

    public function test_job_is_silent_when_llm_fails(): void
    {
        $this->createDefaultAgent();

        Http::fake(['api.openai.com/*' => Http::response(['error' => 'boom'], 500)]);

        $ticket = $this->createTicket();

        $this->runJob($ticket);

        $this->assertCount(0, $this->summaryNotes($ticket));
    }

    public function test_job_does_nothing_without_configured_agent(): void
    {
        Http::fake();

        $ticket = $this->createTicket();

        $this->runJob($ticket);

        Http::assertNothingSent();
        $this->assertCount(0, $this->summaryNotes($ticket));
    }

    public function test_cooldown_prevents_duplicate_summaries(): void
    {
        $this->createDefaultAgent();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Resumen del caso.']]],
            ]),
        ]);

        $ticket = $this->createTicket();

        $this->runJob($ticket, 'assigned');
        $this->runJob($ticket, 'escalated');

        $this->assertCount(1, $this->summaryNotes($ticket));
    }

    public function test_feature_flag_disables_summaries(): void
    {
        config()->set('helpdeskagents.ticket_ai.summaries_enabled', false);

        $this->createDefaultAgent();

        Http::fake();

        $ticket = $this->createTicket();

        $this->runJob($ticket);

        Http::assertNothingSent();
        $this->assertCount(0, $this->summaryNotes($ticket));
    }
}
