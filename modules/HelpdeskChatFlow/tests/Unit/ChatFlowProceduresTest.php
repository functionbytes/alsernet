<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use Modules\HelpdeskChatFlow\Services\ChatFlowHandoffSummary;
use Modules\HelpdeskChatFlow\Services\ChatFlowIdentityOtp;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\ChatFlowNodeExecutor;
use Modules\HelpdeskChatFlow\Services\ChatFlowScheduler;
use Modules\HelpdeskChatFlow\Services\ChatFlowSentiment;
use Modules\HelpdeskChatFlow\Services\ChatFlowTriggerResolver;
use Modules\HelpdeskChatFlow\Services\CustomerIdentityResolver;
use Modules\HelpdeskChatFlow\Services\Input\CsatInputHandler;
use Modules\HelpdeskChatFlow\Services\Input\DocumentUploadInputHandler;
use Modules\HelpdeskChatFlow\Services\Input\IdentificationInputHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\FlowCallNodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\FlowCallRefused;
use Modules\HelpdeskChatFlow\Services\Nodes\NodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\NodeHandlerRegistry;
use Modules\HelpdeskChatFlow\Services\Support\ContextPath;
use Modules\HelpdeskChatFlow\Tests\Support\FlowSwitchingChatFlowSession;
use Modules\HelpdeskChatFlow\Tests\TestCase;

/**
 * Procedures: call_flow / return / natural end, with the return stack. No
 * database: flows are in memory and the executor is a fake that logs the
 * "message" nodes it runs.
 */
class ChatFlowProceduresTest extends TestCase
{
    /** @var array<int, string> */
    private array $log = [];

    /** @var array<int, ChatFlow> */
    private array $flows = [];

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->log = [];
        $this->flows = [];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function flow(int $id, array $nodes, string $trigger = 'procedure', string $status = 'active'): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->id = $id;
        $flow->status = $status;
        $flow->trigger_type = $trigger;
        $flow->nodes = $nodes;

        return $this->flows[$id] = $flow;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function node(string $id, string $type, ?string $parent, array $data = []): array
    {
        return ['id' => $id, 'type' => $type, 'parentId' => $parent, 'data' => $data];
    }

    /**
     * @return array<string, mixed>
     */
    private function callNode(string $id, ?string $parent, int $flowId, array $extra = []): array
    {
        return $this->node($id, 'call_flow', $parent, ['flow_id' => $flowId] + $extra);
    }

    private function engine(): ChatFlowEngine
    {
        $calls = new FlowCallNodeHandler(fn (int $id): ?ChatFlow => $this->flows[$id] ?? null);
        $log = &$this->log;

        $fake = new class($calls, $log) implements NodeHandler
        {
            public function __construct(private FlowCallNodeHandler $calls, private array &$log) {}

            public function types(): array
            {
                return ['message', 'set', 'close'];
            }

            public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string
            {
                $first = $session->chatFlow->childrenByParent()[$node['id']][0]['id'] ?? null;

                if ($node['type'] === 'message') {
                    $this->log[] = ContextPath::interpolate($node['data']['text'], $session->context ?? []);
                }

                if ($node['type'] === 'set') {
                    $session->setContextValues($node['data']['vars']);
                }

                if ($node['type'] === 'close') {
                    $session->update(['status' => 'completed']);

                    return null;
                }

                return $first;
            }
        };

        $registry = new NodeHandlerRegistry([$calls, $fake]);

        $executor = new class($registry) extends ChatFlowNodeExecutor
        {
            public function __construct(private NodeHandlerRegistry $registry) {}

            public function execute(array $node, ChatFlowSession $session): ?string
            {
                if (in_array($node['type'], ['start', 'branchItem'], true)) {
                    return $session->chatFlow->childrenByParent()[$node['id']][0]['id'] ?? null;
                }

                return $this->registry->for($node['type'])->handle($node, $session, new Conversation);
            }
        };

        return new ChatFlowEngine(
            $executor,
            Mockery::mock(ChatFlowTriggerResolver::class),
            Mockery::mock(ChatFlowAiResponder::class),
            new ChatFlowSentiment(null),
            new ChatFlowLocalizer(null),
            Mockery::mock(ChatFlowHandoffSummary::class),
            new IdentificationInputHandler(Mockery::mock(CustomerIdentityResolver::class), new ChatFlowIdentityOtp),
            new DocumentUploadInputHandler(new ChatFlowLocalizer(null)),
            new CsatInputHandler(new ChatFlowLocalizer(null)),
            new ChatFlowScheduler,
            $calls,
        );
    }

    private function msg(string $id, ?string $parent, string $text): array
    {
        return $this->node($id, 'message', $parent, ['text' => $text]);
    }

    private function runFlow(ChatFlow $root, array $context = []): FlowSwitchingChatFlowSession
    {
        $session = new FlowSwitchingChatFlowSession($root, $context);
        $this->engine()->resumeAfterDelay($session, 'start');

        return $session;
    }

    public function test_call_and_return_with_input_and_output(): void
    {
        $this->flow(2, [
            $this->node('start', 'start', null),
            $this->msg('p1', 'start', 'dentro {{order}}'),
            $this->node('p2', 'set', 'p1', ['vars' => ['result' => 'enviado', 'order' => 'CAMBIADA', 'temp' => 'x']]),
            $this->node('p3', 'return', 'p2'),
            $this->msg('never', 'p3', 'no debe ejecutarse'),
        ]);
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->callNode('c', 'start', 2, ['input' => ['order' => '{{ref}}'], 'output' => ['result']]),
            $this->msg('after', 'c', 'despues'),
        ], 'conversation_start');

        $session = $this->runFlow($root, ['ref' => 'A-1', 'order' => 'previo']);

        $this->assertSame(['dentro A-1', 'despues'], $this->log);
        $this->assertSame('enviado', $session->getContextValue('result'));
        $this->assertSame('previo', $session->getContextValue('order'));
        $this->assertSame([], $session->getContextValue('_call_stack'));
        $this->assertSame(1, $session->chat_flow_id);
        $this->assertSame('completed', $session->status);
    }

    public function test_input_is_visible_inside_the_procedure(): void
    {
        $this->flow(2, [$this->node('start', 'start', null)]);
        $session = new FlowSwitchingChatFlowSession($this->flow(1, [$this->node('start', 'start', null)], 'conversation_start'));
        $handler = new FlowCallNodeHandler(fn (int $id): ?ChatFlow => $this->flows[$id] ?? null);

        $startId = $handler->enter($session, $this->flows[2], 'back', ['order' => 'A-1']);

        $this->assertSame('start', $startId);
        $this->assertSame('A-1', $session->getContextValue('order'));
        $this->assertSame(2, $session->chat_flow_id);
        $this->assertSame('back', $session->getContextValue('_call_stack')[0]['return_node_id']);
    }

    public function test_two_level_nesting_returns_step_by_step(): void
    {
        $this->flow(3, [
            $this->node('start', 'start', null),
            $this->msg('m', 'start', 'nivel 3'),
            $this->node('r', 'return', 'm'),
        ]);
        $this->flow(2, [
            $this->node('start', 'start', null),
            $this->msg('m1', 'start', 'nivel 2 antes'),
            $this->callNode('c', 'm1', 3),
            $this->msg('m2', 'c', 'nivel 2 despues'),
        ]);
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->callNode('c', 'start', 2),
            $this->msg('m', 'c', 'nivel 1 despues'),
        ], 'conversation_start');

        $session = $this->runFlow($root);

        $this->assertSame(['nivel 2 antes', 'nivel 3', 'nivel 2 despues', 'nivel 1 despues'], $this->log);
        $this->assertSame([], $session->getContextValue('_call_stack'));
        $this->assertSame(1, $session->chat_flow_id);
    }

    public function test_cycle_is_refused_and_the_flow_continues(): void
    {
        $this->flow(3, [
            $this->node('start', 'start', null),
            $this->msg('m', 'start', 'p3'),
            $this->callNode('c', 'm', 2),
            $this->msg('m2', 'c', 'p3 tras ciclo rechazado'),
        ]);
        $this->flow(2, [
            $this->node('start', 'start', null),
            $this->msg('m', 'start', 'p2'),
            $this->callNode('c', 'm', 3),
            $this->msg('m2', 'c', 'p2 al volver'),
        ]);
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->callNode('c', 'start', 2),
        ], 'conversation_start');

        $session = $this->runFlow($root);

        $this->assertSame(['p2', 'p3', 'p3 tras ciclo rechazado', 'p2 al volver'], $this->log);
        $this->assertSame('completed', $session->status);
    }

    public function test_cycle_throws_when_entering_directly(): void
    {
        $this->flow(1, [$this->node('start', 'start', null)], 'procedure');
        $session = new FlowSwitchingChatFlowSession($this->flows[1]);
        $handler = new FlowCallNodeHandler(fn (int $id): ?ChatFlow => $this->flows[$id] ?? null);

        $this->expectException(FlowCallRefused::class);
        $handler->enter($session, $this->flows[1], null);
    }

    public function test_maximum_depth_is_refused(): void
    {
        for ($id = 2; $id <= 5; $id++) {
            $this->flow($id, [
                $this->node('start', 'start', null),
                $this->msg('m', 'start', "p{$id}"),
                $this->callNode('c', 'm', $id + 1),
                $this->msg('m2', 'c', "p{$id} fin"),
            ]);
        }
        $this->flow(6, [$this->node('start', 'start', null), $this->msg('m', 'start', 'p6')]);
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->callNode('c', 'start', 2),
        ], 'conversation_start');

        $this->runFlow($root);

        // 1 -> 2 -> 3 -> 4 (3 frames); 4 -> 5 is refused and 4 continues.
        $this->assertSame(['p2', 'p3', 'p4', 'p4 fin', 'p3 fin', 'p2 fin'], $this->log);
    }

    public function test_refused_call_with_handoff_transfers_and_attributes_session_to_root_flow(): void
    {
        $this->flow(2, [
            $this->node('start', 'start', null),
            $this->callNode('c', 'start', 99, ['on_missing' => 'handoff']),
            $this->msg('m', 'c', 'no debe salir'),
        ]);
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->callNode('c', 'start', 2),
        ], 'conversation_start');

        $session = $this->runFlow($root);

        $this->assertSame([], $this->log);
        $this->assertSame('transferred', $session->status);
        $this->assertSame(1, $session->chat_flow_id);
        Event::assertDispatched(ChatFlowCompleted::class);
    }

    public function test_natural_end_of_the_procedure_returns_to_the_caller(): void
    {
        $this->flow(2, [
            $this->node('start', 'start', null),
            $this->msg('m', 'start', 'procedimiento'),
        ]);
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->callNode('c', 'start', 2),
            $this->msg('after', 'c', 'caller'),
        ], 'conversation_start');

        $session = $this->runFlow($root);

        $this->assertSame(['procedimiento', 'caller'], $this->log);
        $this->assertSame(1, $session->chat_flow_id);
        $this->assertSame('completed', $session->status);
    }

    public function test_branch_without_else_inside_procedure_returns_to_the_caller(): void
    {
        $this->flow(2, [
            $this->node('start', 'start', null),
            $this->node('b', 'branches', 'start'),
            $this->node('bi', 'branchItem', 'b', ['conditions' => [['variable' => 'x', 'operator' => 'equals', 'value' => 'nunca']]]),
        ]);
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->callNode('c', 'start', 2),
            $this->msg('after', 'c', 'caller'),
        ], 'conversation_start');

        $this->runFlow($root);

        $this->assertSame(['caller'], $this->log);
    }

    public function test_close_inside_a_procedure_finishes_everything(): void
    {
        $this->flow(2, [
            $this->node('start', 'start', null),
            $this->node('x', 'close', 'start'),
        ]);
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->callNode('c', 'start', 2),
            $this->msg('after', 'c', 'no debe salir'),
        ], 'conversation_start');

        $session = $this->runFlow($root);

        $this->assertSame([], $this->log);
        $this->assertSame('completed', $session->status);
        $this->assertSame(1, $session->chat_flow_id);
        Event::assertDispatched(ChatFlowCompleted::class, fn (ChatFlowCompleted $e) => $e->session->chat_flow_id === 1);
    }

    public function test_return_without_a_caller_finishes_the_session(): void
    {
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->node('r', 'return', 'start'),
            $this->msg('m', 'r', 'no debe salir'),
        ], 'conversation_start');

        $session = $this->runFlow($root);

        $this->assertSame([], $this->log);
        $this->assertSame('completed', $session->status);
    }

    public function test_call_procedure_enters_and_comes_back_to_the_given_node(): void
    {
        $this->flow(2, [
            $this->node('start', 'start', null),
            $this->msg('m', 'start', 'procedimiento {{order}}'),
        ]);
        $root = $this->flow(1, [
            $this->node('start', 'start', null),
            $this->msg('back', null, 'de vuelta en la IA'),
        ], 'conversation_start');

        $session = new FlowSwitchingChatFlowSession($root, ['order' => 'previo']);
        $this->engine()->callProcedure($session, $this->flows[2], 'back', ['order' => 'A-9']);

        $this->assertSame(['procedimiento A-9', 'de vuelta en la IA'], $this->log);
        $this->assertSame('previo', $session->getContextValue('order'));
        $this->assertSame(1, $session->chat_flow_id);
    }

    public function test_call_procedure_refuses_a_flow_that_is_not_a_procedure(): void
    {
        $normal = $this->flow(2, [$this->node('start', 'start', null)], 'conversation_start');
        $root = $this->flow(1, [$this->node('start', 'start', null)], 'conversation_start');

        $this->expectException(FlowCallRefused::class);
        $this->engine()->callProcedure(new FlowSwitchingChatFlowSession($root), $normal, 'start');
    }

    public function test_procedures_are_never_triggered_automatically(): void
    {
        $resolver = new ChatFlowTriggerResolver;

        $this->assertNull($resolver->resolve(new Conversation, 'procedure'));
        $this->assertContains('procedure', ChatFlow::TRIGGER_TYPES);
        $this->assertContains('call_flow', ChatFlow::NODE_TYPES);
        $this->assertContains('return', ChatFlow::NODE_TYPES);
    }
}
