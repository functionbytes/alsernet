<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Mockery;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;

class PanelActionsTestEndpointTest extends PanelActionsTestCase
{
    public function test_the_executor_receives_the_simulated_context_with_source_test(): void
    {
        $action = $this->http();

        $this->mock(ActionExecutor::class)
            ->shouldReceive('run')
            ->once()
            ->with('consulta_externa', ['q' => 'botas'], Mockery::on(fn (array $ctx) => $ctx['verified'] === true && $ctx['customer_email'] === 'ana@example.com'), 'test')
            ->andReturn(['ok' => true, 'content' => '{"items":[{"name":"Bota"}]}', 'status' => 'ok']);

        $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.actions.test', $action), [
                'verified' => 1, 'customer_email' => 'ana@example.com', 'args' => ['q' => 'botas', 'otro' => 'ignorado'],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('content', '{"items":[{"name":"Bota"}]}')
            ->assertJsonStructure(['ok', 'status', 'content', 'latency_ms']);
    }

    public function test_confirmation_booleans_reach_the_executor_as_real_booleans(): void
    {
        $action = $this->bridge([
            'key' => 'reenviar',
            'config' => ['action' => 'order.send_email', 'payload' => ['order_id' => '{{order.id}}', 'type' => 'order_conf']],
            'rules' => ['ownership' => 'verified'],
        ]);

        $this->mock(ActionExecutor::class)
            ->shouldReceive('run')
            ->once()
            ->with('reenviar', ['order_ref' => '1001', 'customer_confirmed' => true], Mockery::type('array'), 'test')
            ->andReturn(['ok' => true, 'content' => 'ok', 'status' => 'ok']);

        $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.actions.test', $action), [
                'verified' => 1, 'customer_email' => 'ana@example.com', 'args' => ['order_ref' => '1001', 'customer_confirmed' => 'true'],
            ])->assertOk();
    }

    public function test_without_verification_a_verified_only_action_is_denied_with_a_clear_message(): void
    {
        $action = $this->bridge(['rules' => ['ownership' => 'verified']]);

        $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.actions.test', $action), ['verified' => 0, 'args' => ['order_ref' => '1001']])
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('status', 'denied')
            ->assertJsonPath('content', 'Para esto necesito que el cliente esté identificado. Pídele que inicie sesión o verifique su identidad.');

        $run = AiActionRun::query()->where('action_key', 'documentos_pedido')->firstOrFail();
        $this->assertSame('test', $run->source);
        $this->assertSame('denied', $run->status);
    }

    public function test_a_verified_simulation_needs_an_email(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('helpdesk-ai-prompts.actions.test', $this->http()), ['verified' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function test_inactive_and_builtin_actions_cannot_be_tested(): void
    {
        $inactive = $this->http(['is_active' => false]);
        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.test', $inactive))->assertUnprocessable();

        $builtin = AiAction::query()->create([
            'key' => 'show_cart', 'name' => 'Ver la cesta', 'description' => 'x', 'type' => 'builtin',
            'is_active' => true, 'parameters' => [], 'config' => [],
        ]);
        $this->actingAs($this->manager)->postJson(route('helpdesk-ai-prompts.actions.test', $builtin))->assertNotFound();
    }

    public function test_the_history_lists_the_runs_and_the_test_modal_params_hide_no_secrets(): void
    {
        $action = $this->http(['secrets' => ['token' => 'sekret-123'], 'config' => ['method' => 'GET', 'url' => 'https://api.example.com/x?q={{args.q}}', 'auth' => ['type' => 'bearer', 'secret' => 'token']]]);
        AiActionRun::query()->create([
            'action_key' => $action->key, 'source' => 'ai', 'status' => 'error', 'error' => 'http_status_500',
            'latency_ms' => 321, 'args_summary' => ['q' => 'botas'], 'created_at' => now(),
        ]);

        $this->actingAs($this->manager)->get(route('helpdesk-ai-prompts.actions.history', $action))
            ->assertOk()
            ->assertSee('http_status_500')
            ->assertSee('321 ms')
            ->assertDontSee('sekret-123');

        $this->actingAs($this->manager)->get(route('helpdesk-ai-prompts.index', ['tab' => 'acciones']))
            ->assertDontSee('sekret-123');
    }
}
