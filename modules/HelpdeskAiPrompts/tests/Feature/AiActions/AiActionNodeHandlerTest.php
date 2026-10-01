<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Mockery;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;
use Modules\HelpdeskAiPrompts\Services\Flow\AiActionNodeHandler;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\Nodes\NodeHandlerRegistry;

class AiActionNodeHandlerTest extends PanelActionsTestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $posted = [];

    private function flowSession(array $context = []): ChatFlowSession
    {
        $flow = Mockery::mock(ChatFlow::class);
        $flow->shouldReceive('childrenByParent')->andReturn(['n1' => [['id' => 'next']]]);

        return new class($flow, $context) extends ChatFlowSession
        {
            public array $updates = [];

            public function __construct(private ChatFlow $flow, private array $ctx) {}

            public function getAttribute($key): mixed
            {
                return match ($key) {
                    'chatFlow' => $this->flow,
                    'context' => $this->ctx,
                    default => null,
                };
            }

            public function setContextValues(array $values): void
            {
                $this->ctx = array_merge($this->ctx, $values);
            }

            public function getContextValue(string $key, mixed $default = null): mixed
            {
                return $this->ctx[$key] ?? $default;
            }

            public function update(array $attributes = [], array $options = []): bool
            {
                $this->updates[] = $attributes;

                return true;
            }
        };
    }

    private function conversation(bool $expectRelease = false): Conversation
    {
        $items = Mockery::mock(HasMany::class);
        $items->shouldReceive('create')->andReturnUsing(function (array $item) {
            $this->posted[] = $item;

            return null;
        });

        $conversation = Mockery::mock(Conversation::class)->makePartial();
        $conversation->id = 77;
        $conversation->shouldReceive('items')->andReturn($items);

        if ($expectRelease) {
            $conversation->shouldReceive('releaseFromBot')->once();
        } else {
            $conversation->shouldReceive('releaseFromBot')->never();
        }

        return $conversation;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function handler(array $result, ?callable $onRun = null): AiActionNodeHandler
    {
        $executor = Mockery::mock(ActionExecutor::class);
        $executor->shouldReceive('run')->once()->andReturnUsing(function (...$params) use ($result, $onRun) {
            if ($onRun) {
                $onRun(...$params);
            }

            return $result;
        });

        return new AiActionNodeHandler($executor, new ChatFlowLocalizer(null));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function node(array $data): array
    {
        return ['id' => 'n1', 'type' => 'ai_action', 'data' => $data + ['action_key' => 'documentos_pedido']];
    }

    public function test_args_are_interpolated_from_context_with_dot_paths(): void
    {
        $captured = [];
        $session = $this->flowSession(['numero_pedido' => 'A-123', 'cliente' => ['nombre' => 'Ana']]);
        $handler = $this->handler(['ok' => true, 'content' => 'x', 'status' => 'ok'], function ($key, $args, $ctx, $source) use (&$captured) {
            $captured = compact('key', 'args', 'source');
        });

        $next = $handler->handle($this->node(['args' => [
            'order_ref' => '{{numero_pedido}}',
            'nota' => 'Hola {{cliente.nombre}} ({{falta}})',
            'customer_confirmed' => true,
        ]]), $session, $this->conversation());

        $this->assertSame('next', $next);
        $this->assertSame('documentos_pedido', $captured['key']);
        $this->assertSame('flow', $captured['source']);
        $this->assertSame(['order_ref' => 'A-123', 'nota' => 'Hola Ana ()'], $captured['args']);
    }

    public function test_customer_data_is_only_passed_when_verified(): void
    {
        $base = [
            'customer_email' => 'a@b.es', 'customer_ps_id' => 5, 'customer_erp_id' => 9,
            '_trace_id' => 't-1', '_channel' => 'web', 'customer_lang' => 'en', 'conversation_id' => 42,
        ];

        $ctx = [];
        $this->handler(['ok' => true, 'content' => 'x', 'status' => 'ok'], function ($k, $a, $c) use (&$ctx) {
            $ctx = $c;
        })->handle($this->node([]), $this->flowSession($base), $this->conversation());

        $this->assertFalse($ctx['verified']);
        $this->assertNull($ctx['customer_email']);
        $this->assertNull($ctx['customer_ps_id']);
        $this->assertNull($ctx['customer_erp_id']);
        $this->assertSame(42, $ctx['conversation_id']);
        $this->assertSame('t-1', $ctx['trace_id']);
        $this->assertSame('web', $ctx['channel']);
        $this->assertSame('en', $ctx['locale']);

        foreach (['customer_identified_via_otp', 'identity_verified'] as $flag) {
            $this->handler(['ok' => true, 'content' => 'x', 'status' => 'ok'], function ($k, $a, $c) use (&$ctx) {
                $ctx = $c;
            })->handle($this->node([]), $this->flowSession($base + [$flag => true]), $this->conversation());

            $this->assertTrue($ctx['verified']);
            $this->assertSame('a@b.es', $ctx['customer_email']);
            $this->assertSame(5, $ctx['customer_ps_id']);
            $this->assertSame(9, $ctx['customer_erp_id']);
        }
    }

    public function test_json_result_is_saved_nested_and_flattened(): void
    {
        $content = json_encode(['estado' => 'enviado', 'total' => 12.5, 'lineas' => [['a' => 1]], 'ok' => 'dup']);
        $session = $this->flowSession();

        $this->handler(['ok' => true, 'content' => $content, 'status' => 'ok'])
            ->handle($this->node(['save_to' => 'pedido']), $session, $this->conversation());

        $this->assertTrue($session->getContextValue('pedido_ok'));
        $this->assertSame('ok', $session->getContextValue('pedido_status'));
        $this->assertSame('enviado', $session->getContextValue('pedido')['estado']);
        $this->assertSame('enviado', $session->getContextValue('pedido_estado'));
        $this->assertSame(12.5, $session->getContextValue('pedido_total'));
        $this->assertNull($session->getContextValue('pedido_lineas'));
        $this->assertTrue($session->getContextValue('pedido_ok'), 'un campo "ok" del JSON no pisa el booleano');
    }

    public function test_plain_text_result_is_saved_as_text_with_default_save_to(): void
    {
        $session = $this->flowSession();

        $this->handler(['ok' => true, 'content' => 'Factura enviada', 'status' => 'ok'])
            ->handle($this->node([]), $session, $this->conversation());

        $this->assertSame('Factura enviada', $session->getContextValue('accion'));
    }

    public function test_message_template_is_posted_when_enabled(): void
    {
        $session = $this->flowSession();

        $this->handler(['ok' => true, 'content' => 'Hecho', 'status' => 'ok'])
            ->handle($this->node(['show_message' => true, 'message_template' => 'Resultado: {{accion}}']), $session, $this->conversation());

        $this->assertCount(1, $this->posted);
        $this->assertSame('Resultado: Hecho', $this->posted[0]['body']);
        $this->assertSame('n1', $this->posted[0]['metadata']['flow_node_id']);
    }

    public function test_error_and_denied_continue_by_default(): void
    {
        foreach (['error', 'denied'] as $status) {
            $session = $this->flowSession();

            $next = $this->handler(['ok' => false, 'content' => 'No', 'status' => $status])
                ->handle($this->node([]), $session, $this->conversation());

            $this->assertSame('next', $next);
            $this->assertFalse($session->getContextValue('accion_ok'));
            $this->assertSame($status, $session->getContextValue('accion_status'));
            $this->assertSame([], $session->updates);
        }
    }

    public function test_on_error_handoff_transfers_to_a_human(): void
    {
        $session = $this->flowSession();

        $next = $this->handler(['ok' => false, 'content' => 'No', 'status' => 'error'])
            ->handle($this->node(['on_error' => 'handoff']), $session, $this->conversation(true));

        $this->assertNull($next);
        $this->assertSame('transferred', $session->updates[0]['status']);
        $this->assertCount(1, $this->posted);
    }

    public function test_on_error_handoff_does_not_trigger_on_success(): void
    {
        $next = $this->handler(['ok' => true, 'content' => 'x', 'status' => 'ok'])
            ->handle($this->node(['on_error' => 'handoff']), $this->flowSession(), $this->conversation());

        $this->assertSame('next', $next);
    }

    public function test_confirmation_flag_is_only_sent_when_the_variable_says_yes(): void
    {
        $cases = [
            [null, ['x' => 'Sí'], false],
            ['confirmo', ['confirmo' => 'No'], false],
            ['confirmo', [], false],
            ['confirmo', ['confirmo' => 'Sí'], true],
            ['confirmo', ['confirmo' => true], true],
            ['datos.ok', ['datos' => ['ok' => 'sí']], true],
        ];

        foreach ($cases as [$variable, $context, $expected]) {
            $args = [];
            $this->handler(['ok' => true, 'content' => 'x', 'status' => 'ok'], function ($k, $a) use (&$args) {
                $args = $a;
            })->handle($this->node(['confirmed_variable' => $variable]), $this->flowSession($context), $this->conversation());

            $this->assertSame($expected, ($args['customer_confirmed'] ?? false) === true);
        }
    }

    public function test_write_action_is_denied_without_confirmation_and_runs_with_it(): void
    {
        Http::fake(['api.example.com/*' => Http::response(['items' => [['name' => 'x']]])]);
        $this->http(['key' => 'cancelar_pedido', 'parameters' => [], 'config' => ['method' => 'GET', 'url' => 'https://api.example.com/cancel'], 'rules' => ['ownership' => 'none', 'confirm' => true]]);

        $executor = app(ActionExecutor::class);
        $handler = new AiActionNodeHandler($executor, new ChatFlowLocalizer(null));
        $node = $this->node(['action_key' => 'cancelar_pedido', 'confirmed_variable' => 'confirmo']);

        $denied = $this->flowSession(['confirmo' => 'No']);
        $handler->handle($node, $denied, $this->conversation());
        $this->assertSame('denied', $denied->getContextValue('accion_status'));

        $confirmed = $this->flowSession(['confirmo' => 'Sí']);
        $handler->handle($node, $confirmed, $this->conversation());
        $this->assertSame('ok', $confirmed->getContextValue('accion_status'));
    }

    public function test_handler_is_registered_in_the_node_registry(): void
    {
        $tagged = collect(iterator_to_array(app()->tagged(NodeHandlerRegistry::TAG)));

        $this->assertTrue($tagged->contains(fn ($h) => $h instanceof AiActionNodeHandler));
        $this->assertSame(['ai_action'], (new AiActionNodeHandler(Mockery::mock(ActionExecutor::class), new ChatFlowLocalizer(null)))->types());

        if (app()->bound(NodeHandlerRegistry::class)) {
            $this->assertContains('ai_action', ChatFlow::nodeTypes());
        }
    }

    public function test_catalog_endpoint_lists_active_actions_for_viewers_only(): void
    {
        $this->bridge();
        $this->http(['key' => 'cancelar_pedido', 'rules' => ['ownership' => 'none', 'confirm' => true]]);
        $this->bridge(['key' => 'apagada', 'is_active' => false]);

        $this->getJson(route('helpdesk-ai-prompts.actions.catalog-json'))->assertUnauthorized();

        $outsider = User::factory()->create();
        $this->actingAs($outsider)->getJson(route('helpdesk-ai-prompts.actions.catalog-json'))->assertForbidden();

        $response = $this->actingAs($this->viewer)->getJson(route('helpdesk-ai-prompts.actions.catalog-json'))->assertOk();

        $rows = collect($response->json('data'))->keyBy('key');
        $this->assertSame(['cancelar_pedido', 'documentos_pedido'], $rows->keys()->sort()->values()->all());
        $this->assertTrue($rows['cancelar_pedido']['write']);
        $this->assertFalse($rows['documentos_pedido']['write']);
        $this->assertSame(['key', 'name', 'description', 'type', 'parameters', 'write'], array_keys($rows['documentos_pedido']));
        $this->assertNotContains('customer_confirmed', array_column($rows['cancelar_pedido']['parameters'], 'name'));
    }
}
