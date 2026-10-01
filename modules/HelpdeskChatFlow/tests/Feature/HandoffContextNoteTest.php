<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowHandoffSummary;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\HandoffContextNote;
use Modules\HelpdeskChatFlow\Services\Nodes\AiNodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\ConversationNodeHandler;
use Modules\HelpdeskChatFlow\Tests\Support\InMemoryChatFlowSession;
use Modules\HelpdeskChatFlow\Tests\TestCase;

class HandoffContextNoteTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    /** @return array<string, mixed> */
    private function context(): array
    {
        return [
            'order_ref' => 'PED-1234',
            'customer_email' => 'juan@gmail.com',
            'customer_phone' => '+34 600 123 456',
            'talla' => '42',
            'cart_total' => '89,90 €',
            'cart_count' => 2,
            'identity_verified' => true,
            'motivo_consulta' => 'cambio de talla',
            'ai_pending_case' => 'pedidos',
            '_trace_id' => 'trace-abc',
            '_otp_hash' => 'secreto',
            'ai_pending_options' => ['a', 'b'],
            'conversation_id' => 55,
            'order_buffer' => 'xxx',
        ];
    }

    private function flowSession(array $context): InMemoryChatFlowSession
    {
        return new InMemoryChatFlowSession(new ChatFlow(['name' => 'Flujo pedidos']), ['context' => $context]);
    }

    private function notes(Conversation $conversation)
    {
        return $conversation->items()->where('is_internal', true)->get()
            ->filter(fn ($item) => ! empty($item->metadata['handoff_context']));
    }

    private function transferHandler(): ConversationNodeHandler
    {
        return new ConversationNodeHandler(new ChatFlowLocalizer(null), Mockery::mock(ChatFlowHandoffSummary::class));
    }

    public function test_transfer_creates_internal_note_with_collected_data_masked(): void
    {
        $conversation = Conversation::factory()->create();
        $node = ['id' => 't1', 'type' => 'transfer', 'data' => ['message' => '']];

        $this->transferHandler()->handle($node, $this->flowSession($this->context()), $conversation);

        $note = $this->notes($conversation)->first();
        $this->assertNotNull($note);
        $this->assertTrue($note->is_internal);
        $this->assertSame('message', $note->type);
        $this->assertStringContainsString('Motivo: '.HandoffContextNote::REASON_NODE, $note->body);
        $this->assertStringContainsString('Caso de prompt: pedidos', $note->body);
        $this->assertStringContainsString('Flujo: Flujo pedidos', $note->body);
        $this->assertStringContainsString('- Pedido: PED-1234', $note->body);
        $this->assertStringContainsString('- Email: j***@gmail.com', $note->body);
        $this->assertStringContainsString('- Talla: 42', $note->body);
        $this->assertStringContainsString('- Carrito: total 89,90 €, 2 uds.', $note->body);
        $this->assertStringContainsString('- Verificado: sí', $note->body);
        $this->assertStringContainsString('- motivo_consulta: cambio de talla', $note->body);
        $this->assertStringContainsString('- customer_phone: ********456', $note->body);
        $this->assertStringNotContainsString('juan@gmail.com', $note->body);
        $this->assertStringNotContainsString('600', $note->body);
    }

    public function test_internal_keys_are_excluded(): void
    {
        $conversation = Conversation::factory()->create();

        app(HandoffContextNote::class)->post($conversation, $this->flowSession($this->context()), HandoffContextNote::REASON_NODE);

        $body = $this->notes($conversation)->first()->body;
        foreach (['trace-abc', 'secreto', '_otp_hash', 'ai_pending_options', 'conversation_id', 'order_buffer', '_trace_id'] as $leak) {
            $this->assertStringNotContainsString($leak, $body);
        }
    }

    public function test_end_node_with_transfer_creates_note(): void
    {
        $conversation = Conversation::factory()->create();
        $node = ['id' => 'e1', 'type' => 'end', 'data' => ['action' => 'transfer_to_agent']];

        $this->transferHandler()->handle($node, $this->flowSession(['order_ref' => 'A-1']), $conversation);

        $this->assertStringContainsString(HandoffContextNote::REASON_END, $this->notes($conversation)->first()->body);
    }

    public function test_end_node_without_transfer_creates_no_note(): void
    {
        $conversation = Conversation::factory()->create();
        $node = ['id' => 'e1', 'type' => 'end', 'data' => []];

        $this->transferHandler()->handle($node, $this->flowSession(['order_ref' => 'A-1']), $conversation);

        $this->assertCount(0, $this->notes($conversation));
    }

    public function test_ai_agent_escalation_creates_note(): void
    {
        $conversation = Conversation::factory()->create();
        $agent = Mockery::mock(ChatFlowAgentService::class);
        $agent->shouldReceive('run')->once()->andReturn([
            'action' => 'escalate', 'text' => 'Te paso con un agente.', 'used_tools' => [], 'products' => [],
        ]);
        $handler = new AiNodeHandler(Mockery::mock(ChatFlowAiResponder::class), $agent, new ChatFlowLocalizer(null));

        $handler->handle(['id' => 'a1', 'type' => 'ai_agent', 'data' => ['use_memory' => false]], $this->flowSession($this->context()), $conversation);

        $note = $this->notes($conversation)->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString(HandoffContextNote::REASON_AI_ESCALATION, $note->body);
        $this->assertStringContainsString('- Email: j***@gmail.com', $note->body);
    }

    public function test_note_is_not_duplicated_within_two_minutes(): void
    {
        $conversation = Conversation::factory()->create();
        $service = app(HandoffContextNote::class);
        $session = $this->flowSession($this->context());

        $this->assertTrue($service->post($conversation, $session, HandoffContextNote::REASON_NODE));
        $this->assertFalse($service->post($conversation, $session, HandoffContextNote::REASON_AI_ESCALATION));

        $this->assertCount(1, $this->notes($conversation));
    }

    public function test_summary_is_appended_after_the_data(): void
    {
        $conversation = Conversation::factory()->create();

        app(HandoffContextNote::class)->post(
            $conversation,
            $this->flowSession($this->context()),
            HandoffContextNote::REASON_NODE,
            fn () => '- Quería cambiar la talla',
        );

        $body = $this->notes($conversation)->first()->body;
        $this->assertGreaterThan(strpos($body, 'Datos recogidos'), strpos($body, 'Resumen del bot'));
        $this->assertStringContainsString('Quería cambiar la talla', $body);
    }

    public function test_executed_actions_are_listed_when_table_exists(): void
    {
        if (! class_exists(AiActionRun::class) || ! Schema::connection('helpdesk')->hasTable('helpdesk_ai_action_runs')) {
            $this->markTestSkipped('Tabla helpdesk_ai_action_runs no disponible.');
        }

        $conversation = Conversation::factory()->create();
        AiActionRun::query()->create(['action_key' => 'order_lookup', 'source' => 'ai', 'conversation_id' => $conversation->id, 'status' => 'ok', 'created_at' => now()]);
        AiActionRun::query()->create(['action_key' => 'refund_check', 'source' => 'ai', 'trace_id' => 'trace-abc', 'status' => 'error', 'error' => 'timeout ERP', 'created_at' => now()]);

        app(HandoffContextNote::class)->post($conversation, $this->flowSession($this->context()), HandoffContextNote::REASON_ACTION_ERROR);

        $body = $this->notes($conversation)->first()->body;
        $this->assertStringContainsString("Acciones ejecutadas:\n- order_lookup (ok)", $body);
        $this->assertStringContainsString('- refund_check (error): timeout ERP', $body);
    }
}
