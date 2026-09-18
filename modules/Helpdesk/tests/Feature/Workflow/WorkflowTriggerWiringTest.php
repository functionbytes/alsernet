<?php

namespace Modules\Helpdesk\Tests\Feature\Workflow;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Jobs\RunWorkflowJob;
use Modules\Helpdesk\Listeners\TriggerWorkflowsOnConversationClosed;
use Modules\Helpdesk\Listeners\TriggerWorkflowsOnConversationCreated;
use Modules\Helpdesk\Listeners\TriggerWorkflowsOnMessageReceived;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Workflow;
use Modules\Helpdesk\Models\WorkflowRun;
use Modules\Helpdesk\Services\Workflow\WorkflowEngine;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class WorkflowTriggerWiringTest extends HelpdeskTestCase
{
    private function makeWorkflow(string $trigger, bool $active, array $overrides = []): Workflow
    {
        return Workflow::create(array_merge([
            'name' => 'WF '.$trigger,
            'trigger_type' => $trigger,
            'trigger_config' => [],
            'nodes' => [
                ['id' => 'n1', 'type' => 'end'],
            ],
            'is_active' => $active,
        ], $overrides));
    }

    public function test_conversation_created_listener_pushes_run_job_for_active_workflow(): void
    {
        Queue::fake();
        $this->makeWorkflow('conversation_created', active: true);
        $conversation = Conversation::factory()->create();

        (new TriggerWorkflowsOnConversationCreated)->handle(new ConversationCreated($conversation));

        Queue::assertPushed(RunWorkflowJob::class);
        $this->assertDatabaseHas('helpdesk_workflow_runs', [
            'conversation_id' => $conversation->id,
            'status' => WorkflowRun::STATUS_RUNNING,
        ], 'helpdesk');
    }

    public function test_conversation_created_listener_does_nothing_for_inactive_workflow(): void
    {
        Queue::fake();
        $this->makeWorkflow('conversation_created', active: false);
        $conversation = Conversation::factory()->create();

        (new TriggerWorkflowsOnConversationCreated)->handle(new ConversationCreated($conversation));

        Queue::assertNotPushed(RunWorkflowJob::class);
        $this->assertDatabaseMissing('helpdesk_workflow_runs', [
            'conversation_id' => $conversation->id,
        ], 'helpdesk');
    }

    public function test_message_received_listener_pushes_run_job_for_inbound_customer_message(): void
    {
        Queue::fake();
        $this->makeWorkflow('message_received', active: true);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $message = ConversationItem::factory()
            ->fromCustomer($customer->id)
            ->create(['conversation_id' => $conversation->id]);

        (new TriggerWorkflowsOnMessageReceived)->handle(new MessageReceived($conversation, $message));

        Queue::assertPushed(RunWorkflowJob::class);
    }

    public function test_message_received_listener_skips_agent_outbound_message(): void
    {
        Queue::fake();
        $this->makeWorkflow('message_received', active: true);
        $conversation = Conversation::factory()->create();
        $message = ConversationItem::factory()
            ->fromAgent($this->manager->id)
            ->create(['conversation_id' => $conversation->id]);

        (new TriggerWorkflowsOnMessageReceived)->handle(new MessageReceived($conversation, $message));

        Queue::assertNotPushed(RunWorkflowJob::class);
    }

    public function test_conversation_closed_listener_pushes_run_job_for_active_workflow(): void
    {
        Queue::fake();
        $this->makeWorkflow('conversation_closed', active: true);
        $conversation = Conversation::factory()->create();

        (new TriggerWorkflowsOnConversationClosed)->handle(new ConversationClosed($conversation));

        Queue::assertPushed(RunWorkflowJob::class);
    }

    public function test_engine_is_inert_when_no_active_workflows_exist(): void
    {
        // El fake se instala DESPUÉS de crear la conversación: crearla encola por
        // sí sola el broadcast y los listeners del propio modelo, que no tienen
        // nada que ver con el engine. Aislándolo, assertNothingPushed sigue
        // afirmando lo que interesa — que el listener no encola absolutamente
        // nada cuando no hay workflows activos.
        $conversation = Conversation::factory()->create();
        Queue::fake();

        (new TriggerWorkflowsOnConversationCreated)->handle(new ConversationCreated($conversation));

        Queue::assertNothingPushed();
    }

    public function test_run_job_executes_http_request_action_through_engine(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $workflow = $this->makeWorkflow('conversation_created', true, [
            'nodes' => [
                [
                    'id' => 'n1',
                    'type' => 'action',
                    'config' => [
                        'action' => 'http_request',
                        'method' => 'post',
                        'url' => 'https://example.com/webhook',
                    ],
                    'next' => 'n2',
                ],
                ['id' => 'n2', 'type' => 'end'],
            ],
        ]);

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'status' => WorkflowRun::STATUS_RUNNING,
            'current_node_id' => 'n1',
            'context' => ['conversation_id' => null],
            'started_at' => now(),
        ]);

        app(WorkflowEngine::class)->runWorkflow($run);

        Http::assertSent(fn ($request) => $request->url() === 'https://example.com/webhook');
        $this->assertSame(WorkflowRun::STATUS_COMPLETED, $run->fresh()->status);
    }
}
