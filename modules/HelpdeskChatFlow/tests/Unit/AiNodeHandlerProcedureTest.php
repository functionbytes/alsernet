<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\Nodes\AiNodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\FlowCallNodeHandler;
use Modules\HelpdeskChatFlow\Services\Simulation\CapturesBotMessages;
use Modules\HelpdeskChatFlow\Tests\Support\FlowSwitchingChatFlowSession;
use Modules\HelpdeskChatFlow\Tests\TestCase;

/**
 * ai_agent + procedimiento del caso: entra con retorno al propio nodo, no
 * reentra al volver y, si el procedimiento se rechaza, responde la IA.
 */
class AiNodeHandlerProcedureTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var array<int, ChatFlow> */
    private array $flows = [];

    private ChatFlow $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = $this->flow(1, [['id' => 'ai', 'type' => 'ai_agent', 'parentId' => null, 'data' => []]], 'keyword');
        $this->flow(7, [
            ['id' => 'ask', 'type' => 'collect_input', 'parentId' => null, 'data' => []],
        ]);

        $this->app->instance(FlowCallNodeHandler::class, new FlowCallNodeHandler(fn (int $id): ?ChatFlow => $this->flows[$id] ?? null));
    }

    private function flow(int $id, array $nodes, string $trigger = 'procedure'): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->id = $id;
        $flow->status = 'active';
        $flow->trigger_type = $trigger;
        $flow->nodes = $nodes;

        return $this->flows[$id] = $flow;
    }

    private function conversation(): Conversation
    {
        $conversation = new class extends Conversation implements CapturesBotMessages
        {
            /** @var array<int, array<string, mixed>> */
            public array $captured = [];

            public function captureBotMessage(array $item): void
            {
                $this->captured[] = $item;
            }
        };
        $conversation->setRelation('inbox', null);

        return $conversation;
    }

    private function handler(ChatFlowAgentService $agent): AiNodeHandler
    {
        return new AiNodeHandler(Mockery::mock(ChatFlowAiResponder::class), $agent, new ChatFlowLocalizer(null));
    }

    private function node(): array
    {
        return ['id' => 'ai', 'type' => 'ai_agent', 'data' => ['use_memory' => false, 'use_prompt_library' => true]];
    }

    public function test_enters_the_procedure_returning_to_its_own_node_and_marks_it_done(): void
    {
        $agent = Mockery::mock(ChatFlowAgentService::class);
        $agent->shouldReceive('run')->once()->andReturn([
            'action' => 'procedure', 'text' => '', 'used_tools' => [], 'products' => [],
            'procedure_flow_id' => 7, 'input' => ['email' => '{{customer_email}}'], 'outputs' => ['order_ref'], 'case' => 'estado_pedido',
        ]);
        $session = new FlowSwitchingChatFlowSession($this->root, ['last_input' => 'mi pedido', 'customer_email' => 'a@b.es']);

        $next = $this->handler($agent)->handle($this->node(), $session, $this->conversation());

        $this->assertSame('ask', $next);
        $this->assertSame(7, (int) $session->chat_flow_id);
        $stack = $session->getContextValue('_call_stack');
        $this->assertSame('ai', $stack[0]['return_node_id']);
        $this->assertSame(1, $stack[0]['flow_id']);
        $this->assertSame('a@b.es', $session->getContextValue('email'));
        $this->assertSame(['estado_pedido'], $session->getContextValue('_ai_procedure_done'));
        $this->assertSame('mi pedido', $session->getContextValue('_ai_procedure_question'));
    }

    public function test_on_return_it_does_not_reenter_and_uses_the_original_question_and_case(): void
    {
        $agent = Mockery::mock(ChatFlowAgentService::class);
        $agent->shouldReceive('run')->once()->withArgs(function (string $question, array $context, array $data): bool {
            return $question === 'mi pedido'
                && $context['_ai_procedure_done'] === ['estado_pedido']
                && $data['_forced_case'] === 'estado_pedido';
        })->andReturn(['action' => 'respond', 'text' => 'Listo.', 'used_tools' => [], 'products' => [], 'case' => 'estado_pedido']);

        $handler = $this->handler($agent);
        $session = new FlowSwitchingChatFlowSession($this->root, [
            'last_input' => 'A-123',
            '_ai_procedure_done' => ['estado_pedido'],
            '_ai_procedure_question' => 'mi pedido',
        ]);
        $conversation = $this->conversation();

        $next = $handler->handle($this->node(), $session, $conversation);

        $this->assertNull($next);
        $this->assertSame('Listo.', $conversation->captured[0]['body']);

        $this->assertNull($session->getContextValue('_ai_procedure_done'));
        $this->assertNull($session->getContextValue('_ai_procedure_question'));
    }

    public function test_refused_procedure_falls_back_to_normal_ai(): void
    {
        $this->flows[7]->status = 'inactive';
        $agent = Mockery::mock(ChatFlowAgentService::class);
        $agent->shouldReceive('run')->twice()->andReturnUsing(function (string $q, array $context): array {
            if (! in_array('estado_pedido', $context['_ai_procedure_done'] ?? [], true)) {
                return ['action' => 'procedure', 'text' => '', 'used_tools' => [], 'products' => [], 'procedure_flow_id' => 7, 'input' => [], 'outputs' => [], 'case' => 'estado_pedido'];
            }

            return ['action' => 'respond', 'text' => 'Respuesta IA.', 'used_tools' => [], 'products' => [], 'case' => 'estado_pedido'];
        });
        $session = new FlowSwitchingChatFlowSession($this->root, ['last_input' => 'mi pedido']);

        $conversation = $this->conversation();

        $this->handler($agent)->handle($this->node(), $session, $conversation);

        $this->assertSame('Respuesta IA.', $conversation->captured[0]['body']);
        $this->assertSame(1, (int) $session->chat_flow_id);
        $this->assertNull($session->getContextValue('_call_stack'));
        $this->assertNull($session->getContextValue('_ai_procedure_done'));
    }
}
