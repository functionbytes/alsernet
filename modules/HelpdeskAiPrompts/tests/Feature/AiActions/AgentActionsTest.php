<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use App\Models\User;
use Mockery\MockInterface;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;

class AgentActionsTest extends PanelActionsTestCase
{
    private User $agent;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['helpdesk.conversations.view-all', 'helpdesk.ai-prompts.agent-actions']);

        $customer = Customer::factory()->create(['email' => 'cliente@example.com']);
        $this->conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'assignee_id' => $this->agent->id,
            'metadata' => ['order_reference' => 'ABCDEF123'],
        ]);
    }

    private function runUrl(?Conversation $conversation = null): string
    {
        return route('helpdesk-ai-prompts.agent-actions.run', $conversation ?? $this->conversation);
    }

    /**
     * @param  array<string, mixed>  $captured
     */
    private function mockExecutor(array &$captured): void
    {
        $this->mock(ActionExecutor::class, function (MockInterface $mock) use (&$captured) {
            $mock->shouldReceive('run')->once()->andReturnUsing(function ($key, $args, $ctx, $source) use (&$captured) {
                $captured = compact('key', 'args', 'ctx', 'source');

                return ['ok' => true, 'content' => '{"invoices":[{"number":"F1"}]}', 'status' => 'ok'];
            });
        });
    }

    public function test_permission_is_required(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');

        $this->actingAs($user)->getJson(route('helpdesk-ai-prompts.agent-actions.index', $this->conversation))->assertForbidden();
        $this->actingAs($user)->postJson($this->runUrl(), ['action_key' => 'x'])->assertForbidden();
    }

    public function test_a_conversation_the_agent_cannot_see_is_forbidden(): void
    {
        $other = User::factory()->create();
        $other->givePermissionTo(['helpdesk.conversations.view-assigned-only', 'helpdesk.ai-prompts.agent-actions']);
        $this->bridge();

        $this->actingAs($other)->getJson(route('helpdesk-ai-prompts.agent-actions.index', $this->conversation))->assertForbidden();
        $this->actingAs($other)->postJson($this->runUrl(), ['action_key' => 'documentos_pedido'])->assertForbidden();

        $outsider = User::factory()->create();
        $outsider->givePermissionTo(['helpdesk.conversations.view', 'helpdesk.ai-prompts.agent-actions']);
        $this->actingAs($outsider)->postJson($this->runUrl(), ['action_key' => 'documentos_pedido'])->assertForbidden();
    }

    public function test_it_lists_active_actions_with_context(): void
    {
        $this->bridge();
        $this->http(['is_active' => false]);

        $this->actingAs($this->agent)
            ->getJson(route('helpdesk-ai-prompts.agent-actions.index', $this->conversation))
            ->assertOk()
            ->assertJsonCount(1, 'actions')
            ->assertJsonPath('actions.0.key', 'documentos_pedido')
            ->assertJsonPath('actions.0.write', false)
            ->assertJsonPath('context.customer_email', 'cliente@example.com')
            ->assertJsonPath('context.identity_verified', false)
            ->assertJsonPath('context.order_ref', 'ABCDEF123');
    }

    public function test_it_runs_with_source_agent_and_unverified_context(): void
    {
        $this->bridge();
        $captured = [];
        $this->mockExecutor($captured);

        $this->actingAs($this->agent)
            ->postJson($this->runUrl(), ['action_key' => 'documentos_pedido', 'args' => ['order_ref' => 'ABCDEF123', 'email' => 'cliente@example.com']])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame('agent', $captured['source']);
        $this->assertFalse($captured['ctx']['verified']);
        $this->assertNull($captured['ctx']['customer_email']);
        $this->assertSame($this->agent->id, $captured['ctx']['user_id']);
        $this->assertSame($this->conversation->id, $captured['ctx']['conversation_id']);
    }

    public function test_verified_only_with_metadata_or_the_agent_checkbox(): void
    {
        $this->bridge();

        $captured = [];
        $this->mockExecutor($captured);
        $this->actingAs($this->agent)
            ->postJson($this->runUrl(), ['action_key' => 'documentos_pedido', 'args' => ['order_ref' => 'A1'], 'identity_confirmed' => true])
            ->assertOk();
        $this->assertTrue($captured['ctx']['verified']);
        $this->assertTrue($captured['ctx']['agent_verified']);
        $this->assertSame('cliente@example.com', $captured['ctx']['customer_email']);

        $this->conversation->update(['metadata' => ['identity_verified' => true]]);
        $captured = [];
        $this->mockExecutor($captured);
        $this->actingAs($this->agent)
            ->postJson($this->runUrl(), ['action_key' => 'documentos_pedido', 'args' => ['order_ref' => 'A1']])
            ->assertOk();
        $this->assertTrue($captured['ctx']['verified']);
        $this->assertFalse($captured['ctx']['agent_verified']);
    }

    public function test_write_actions_need_explicit_confirmation(): void
    {
        $this->bridge([
            'key' => 'cancelar_pedido',
            'rules' => ['ownership' => 'verified', 'confirm' => true],
        ]);
        $this->mock(ActionExecutor::class)->shouldNotReceive('run');

        $this->actingAs($this->agent)
            ->postJson($this->runUrl(), ['action_key' => 'cancelar_pedido', 'args' => ['order_ref' => 'A1']])
            ->assertUnprocessable();
    }

    public function test_the_real_executor_logs_the_run_with_the_agent(): void
    {
        $this->bridge(['rules' => ['ownership' => 'verified', 'requires_verified' => true]]);

        $this->actingAs($this->agent)
            ->postJson($this->runUrl(), ['action_key' => 'documentos_pedido', 'args' => ['order_ref' => 'A1']])
            ->assertOk()
            ->assertJsonPath('status', 'denied');

        $run = AiActionRun::query()->where('action_key', 'documentos_pedido')->firstOrFail();
        $this->assertSame('agent', $run->source);
        $this->assertSame($this->agent->id, $run->user_id);
        $this->assertSame($this->conversation->id, $run->conversation_id);
        $this->assertFalse($run->agent_verified);
    }
}
