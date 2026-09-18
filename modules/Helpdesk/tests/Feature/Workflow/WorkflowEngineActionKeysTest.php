<?php

namespace Modules\Helpdesk\Tests\Feature\Workflow;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Workflow;
use Modules\Helpdesk\Models\WorkflowRun;
use Modules\Helpdesk\Services\Workflow\WorkflowEngine;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Las acciones de un nodo se guardan siempre como {"action": "...", "value":
 * "..."} — así lo hacen los tres workflows reales de la BD (Bienvenida,
 * Escalado por SLA, Encuesta de satisfacción) — pero send_text/set_status/
 * set_priority/assign_user leían text/status_id/priority/user_id: claves que
 * ningún nodo real usa nunca. Ejecutar cualquiera de estas acciones era un
 * no-op silencioso, sin error ni log, con el WorkflowRun terminando
 * "completed" igualmente.
 */
class WorkflowEngineActionKeysTest extends HelpdeskTestCase
{
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $customer = Customer::factory()->create();

        $this->conversation = Conversation::create([
            'customer_id' => $customer->id,
            'channel' => 'email',
            'subject' => 'Prueba',
            'status_id' => $status->id,
        ]);
    }

    private function runNodes(array $nodes): WorkflowRun
    {
        $workflow = Workflow::create([
            'name' => 'WF acciones',
            'trigger_type' => 'conversation_created',
            'trigger_config' => [],
            'nodes' => $nodes,
            'is_active' => true,
        ]);

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'conversation_id' => $this->conversation->id,
            'customer_id' => $this->conversation->customer_id,
            'status' => WorkflowRun::STATUS_RUNNING,
            'current_node_id' => $nodes[0]['id'],
            'context' => [],
            'started_at' => now(),
        ]);

        app(WorkflowEngine::class)->runWorkflow($run);

        return $run->fresh();
    }

    public function test_send_text_reads_the_value_key(): void
    {
        $this->runNodes([
            ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'send_text', 'value' => 'Gracias por escribirnos.'], 'next' => 'n2'],
            ['id' => 'n2', 'type' => 'end'],
        ]);

        // 'direction' no es columna real de helpdesk_conversation_items —
        // actionSendText() la escribe, pero Eloquent la descarta en
        // silencio; es así desde antes de este arreglo, ajeno a él.
        $this->assertDatabaseHas('helpdesk_conversation_items', [
            'conversation_id' => $this->conversation->id,
            'body' => 'Gracias por escribirnos.',
        ], 'helpdesk');
    }

    /** El bug real: antes de arreglar la clave, esto creaba un mensaje con body=''. */
    public function test_send_text_no_longer_creates_an_empty_message(): void
    {
        $this->runNodes([
            ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'send_text', 'value' => 'Hola'], 'next' => 'n2'],
            ['id' => 'n2', 'type' => 'end'],
        ]);

        $this->assertDatabaseMissing('helpdesk_conversation_items', [
            'conversation_id' => $this->conversation->id,
            'body' => '',
        ], 'helpdesk');
    }

    public function test_set_priority_reads_the_value_key(): void
    {
        $this->runNodes([
            ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'set_priority', 'value' => 'high'], 'next' => 'n2'],
            ['id' => 'n2', 'type' => 'end'],
        ]);

        $this->assertSame('high', $this->conversation->fresh()->priority);
    }

    public function test_set_status_reads_the_value_key(): void
    {
        $closed = ConversationStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#71717a', 'is_open' => false, 'is_default' => false, 'order' => 2]
        );

        $this->runNodes([
            ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'set_status', 'value' => $closed->id], 'next' => 'n2'],
            ['id' => 'n2', 'type' => 'end'],
        ]);

        $this->assertSame($closed->id, $this->conversation->fresh()->status_id);
    }

    public function test_assign_user_reads_the_value_key(): void
    {
        $agent = User::factory()->create();

        $this->runNodes([
            ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'assign_user', 'value' => $agent->id], 'next' => 'n2'],
            ['id' => 'n2', 'type' => 'end'],
        ]);

        $this->assertSame($agent->id, $this->conversation->fresh()->assignee_id);
    }

    /** El bug real: 'value' trae un NOMBRE de etiqueta, no el 'tag_id' numérico que se leía antes. */
    public function test_add_tag_reads_the_value_key_as_tag_name(): void
    {
        $this->runNodes([
            ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'add_tag', 'value' => 'nuevo-contacto'], 'next' => 'n2'],
            ['id' => 'n2', 'type' => 'end'],
        ]);

        $tag = ConversationTag::where('name', 'nuevo-contacto')->first();

        $this->assertNotNull($tag);
        $this->assertTrue($this->conversation->fresh()->conversationTags->contains($tag->id));
    }

    public function test_send_text_marks_the_message_as_an_automated_reply(): void
    {
        $this->runNodes([
            ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'send_text', 'value' => 'Gracias por escribirnos.'], 'next' => 'n2'],
            ['id' => 'n2', 'type' => 'end'],
        ]);

        $item = $this->conversation->items()->where('body', 'Gracias por escribirnos.')->first();

        $this->assertNotNull($item);
        $this->assertSame('workflow', data_get($item->metadata, 'auto_reply'));
    }
}
