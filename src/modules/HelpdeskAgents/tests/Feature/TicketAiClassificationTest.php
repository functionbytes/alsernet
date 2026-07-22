<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAgents\Jobs\ClassifyTicketJob;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskAgents\Services\TicketAiContextBuilder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Tests\TestCase;

class TicketAiClassificationTest extends TestCase
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

        AiAgent::factory()->default()->create([
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'parameters' => ['api_key' => 'test-key'],
        ]);

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

    private function createCategory(string $name = 'Facturación'): TicketCategory
    {
        return TicketCategory::factory()->create(['name' => $name, 'active' => true]);
    }

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::factory()->create(array_merge([
            'priority' => 'normal',
            'category_id' => null,
            'subject' => 'Cargo duplicado en mi factura',
            'description' => 'Me han cobrado dos veces el mismo pedido.',
        ], $overrides));
    }

    private function fakeLlmClassification(array $payload): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($payload)]]],
            ]),
        ]);
    }

    private function runJob(Ticket $ticket): void
    {
        (new ClassifyTicketJob($ticket->id))->handle(
            app(AgentLlmService::class),
            app(TicketAiContextBuilder::class),
        );
    }

    public function test_auto_classification_is_off_by_default(): void
    {
        $this->assertFalse((bool) config('helpdeskagents.ticket_ai.auto_classification'));

        Http::fake();

        $ticket = $this->createTicket();

        $this->runJob($ticket);

        Http::assertNothingSent();
        $this->assertNull($ticket->fresh()->category_id);
        $this->assertSame('normal', $ticket->fresh()->priority);
    }

    public function test_applies_category_and_priority_with_high_confidence_and_logs_history(): void
    {
        config()->set('helpdeskagents.ticket_ai.auto_classification', true);

        $category = $this->createCategory();
        $ticket = $this->createTicket();

        $this->fakeLlmClassification([
            'category_id' => $category->id,
            'priority' => 'high',
            'confidence' => 0.92,
        ]);

        $this->runJob($ticket);

        $fresh = $ticket->fresh();

        $this->assertSame($category->id, $fresh->category_id);
        $this->assertSame('high', $fresh->priority);
        $this->assertSame($category->id, $fresh->ai_suggested_category_id);
        $this->assertSame('high', $fresh->ai_suggested_priority);

        $history = TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('action_type', 'ai_classified')
            ->first();

        $this->assertNotNull($history, 'Expected an ai_classified history entry');
        $this->assertTrue((bool) data_get($history->metadata, 'automatic'));
        $this->assertEquals(0.92, data_get($history->metadata, 'confidence'));
    }

    public function test_low_confidence_only_stores_suggestion(): void
    {
        config()->set('helpdeskagents.ticket_ai.auto_classification', true);

        $category = $this->createCategory();
        $ticket = $this->createTicket();

        $this->fakeLlmClassification([
            'category_id' => $category->id,
            'priority' => 'urgent',
            'confidence' => 0.4,
        ]);

        $this->runJob($ticket);

        $fresh = $ticket->fresh();

        $this->assertNull($fresh->category_id);
        $this->assertSame('normal', $fresh->priority);
        $this->assertSame($category->id, $fresh->ai_suggested_category_id);
        $this->assertSame('urgent', $fresh->ai_suggested_priority);

        $this->assertSame(0, TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('action_type', 'ai_classified')
            ->count());
    }

    public function test_invented_category_id_is_discarded(): void
    {
        config()->set('helpdeskagents.ticket_ai.auto_classification', true);

        $this->createCategory();
        $ticket = $this->createTicket();

        $this->fakeLlmClassification([
            'category_id' => 999999,
            'priority' => 'urgent',
            'confidence' => 0.95,
        ]);

        $this->runJob($ticket);

        $fresh = $ticket->fresh();

        $this->assertNull($fresh->category_id);
        $this->assertNull($fresh->ai_suggested_category_id);
        $this->assertSame('urgent', $fresh->priority);
    }

    public function test_does_not_override_human_classification(): void
    {
        config()->set('helpdeskagents.ticket_ai.auto_classification', true);

        $category = $this->createCategory();
        $ticket = $this->createTicket([
            'category_id' => $category->id,
            'priority' => 'high',
        ]);

        Http::fake();

        $this->runJob($ticket);

        Http::assertNothingSent();
        $this->assertSame($category->id, $ticket->fresh()->category_id);
        $this->assertSame('high', $ticket->fresh()->priority);
    }

    public function test_invalid_llm_output_is_silent(): void
    {
        config()->set('helpdeskagents.ticket_ai.auto_classification', true);

        $this->createCategory();
        $ticket = $this->createTicket();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'No puedo clasificar esto.']]],
            ]),
        ]);

        $this->runJob($ticket);

        $fresh = $ticket->fresh();

        $this->assertNull($fresh->category_id);
        $this->assertSame('normal', $fresh->priority);
    }
}
