<?php

namespace Modules\Helpdesk\Tests\Feature\Workflow;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Workflow;
use Modules\Helpdesk\Models\WorkflowRun;
use Modules\Helpdesk\Services\Workflow\WorkflowEngine;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * El workflow legado "Bienvenida y etiquetado automatico" (conversation_created,
 * accion send_text) y el sistema dedicado de bienvenida/fuera-de-horario/despedida
 * (SendGreetingOnConversationCreated / RespondOffHoursOnConversationCreated /
 * SendFarewellOnConversationClosed) reaccionan al mismo evento sin coordinarse.
 * En produccion, un cliente que escribia fuera de horario recibia "Estamos fuera
 * de horario..." seguido de "en breve un agente te atendera." — dos mensajes
 * automaticos contradictorios en la misma conversacion (verificado en vivo con
 * el workflow real #13, 320 ejecuciones).
 */
class WorkflowSendTextSkipsDedicatedAutoReplyTest extends HelpdeskTestCase
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
            'channel' => 'web',
            'subject' => 'Prueba',
            'status_id' => $status->id,
        ]);
    }

    private function runSendTextWorkflow(string $trigger): WorkflowRun
    {
        $workflow = Workflow::create([
            'name' => 'WF '.$trigger,
            'trigger_type' => $trigger,
            'trigger_config' => [],
            'nodes' => [
                ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'send_text', 'value' => 'Gracias por escribirnos, en breve un agente te atendera.'], 'next' => 'n2'],
                ['id' => 'n2', 'type' => 'end'],
            ],
            'is_active' => true,
        ]);

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'conversation_id' => $this->conversation->id,
            'customer_id' => $this->conversation->customer_id,
            'status' => WorkflowRun::STATUS_RUNNING,
            'current_node_id' => 'n1',
            'context' => [],
            'started_at' => now(),
        ]);

        app(WorkflowEngine::class)->runWorkflow($run);

        return $run->fresh();
    }

    private function seedDedicatedAutoReply(string $type, string $body): void
    {
        ConversationItem::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => null,
            'type' => 'message',
            'body' => $body,
            'is_internal' => false,
            'metadata' => ['auto_reply' => $type],
        ]);
    }

    public function test_send_text_is_skipped_when_off_hours_reply_already_sent(): void
    {
        $this->seedDedicatedAutoReply('off_hours', 'Estamos fuera de horario. Te responderemos en cuanto abramos.');

        $this->runSendTextWorkflow('conversation_created');

        $this->assertDatabaseMissing('helpdesk_conversation_items', [
            'conversation_id' => $this->conversation->id,
            'body' => 'Gracias por escribirnos, en breve un agente te atendera.',
        ], 'helpdesk');
    }

    public function test_send_text_is_skipped_when_greeting_already_sent(): void
    {
        $this->seedDedicatedAutoReply('greeting', 'Hola, bienvenido.');

        $this->runSendTextWorkflow('conversation_created');

        $this->assertDatabaseMissing('helpdesk_conversation_items', [
            'conversation_id' => $this->conversation->id,
            'body' => 'Gracias por escribirnos, en breve un agente te atendera.',
        ], 'helpdesk');
    }

    public function test_send_text_is_skipped_when_farewell_already_sent_on_close(): void
    {
        $this->seedDedicatedAutoReply('farewell', 'Gracias por contactarnos. Que tengas un buen dia!');

        $this->runSendTextWorkflow('conversation_closed');

        $this->assertDatabaseMissing('helpdesk_conversation_items', [
            'conversation_id' => $this->conversation->id,
            'body' => 'Gracias por escribirnos, en breve un agente te atendera.',
        ], 'helpdesk');
    }

    public function test_send_text_still_sends_when_no_dedicated_auto_reply_exists(): void
    {
        $this->runSendTextWorkflow('conversation_created');

        $this->assertDatabaseHas('helpdesk_conversation_items', [
            'conversation_id' => $this->conversation->id,
            'body' => 'Gracias por escribirnos, en breve un agente te atendera.',
        ], 'helpdesk');
    }
}
