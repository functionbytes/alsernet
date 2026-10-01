<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowDocumentLink;
use Modules\HelpdeskChatFlow\Services\ChatFlowHandoffSummary;
use Modules\HelpdeskChatFlow\Services\ChatFlowHttpRequester;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\ChatFlowNodeExecutor;
use Modules\HelpdeskChatFlow\Services\ChatFlowOrderLookup;
use Modules\HelpdeskChatFlow\Services\HandoffContextNote;
use Modules\HelpdeskChatFlow\Services\Nodes\AiNodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\ConversationNodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\IntegrationNodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\MessagingNodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\NodeHandler;
use Modules\HelpdeskChatFlow\Services\Nodes\NodeHandlerRegistry;
use Modules\HelpdeskChatFlow\Services\Nodes\RichContentNodeHandler;
use Modules\HelpdeskChatFlow\Tests\TestCase;
use ReflectionMethod;

/**
 * Exercises the executor's node handlers through their public handle() with a
 * mocked items() relation, so the live node logic is covered without a database.
 */
class ChatFlowNodeExecutorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function registry(): NodeHandlerRegistry
    {
        $localizer = new ChatFlowLocalizer(null);

        return new NodeHandlerRegistry([
            new MessagingNodeHandler($localizer),
            new AiNodeHandler(Mockery::mock(ChatFlowAiResponder::class), Mockery::mock(ChatFlowAgentService::class), $localizer),
            new IntegrationNodeHandler(Mockery::mock(ChatFlowOrderLookup::class), new ChatFlowHttpRequester, $localizer),
            new RichContentNodeHandler($localizer, new ChatFlowDocumentLink(null)),
            new ConversationNodeHandler($localizer, Mockery::mock(ChatFlowHandoffSummary::class)),
        ]);
    }

    /**
     * Runs a node through the handler registered for its type.
     */
    private function handle(array $args): ?string
    {
        return $this->registry()->for($args[0]['type'])->handle(...$args);
    }

    private function makeSession(array $context = []): ChatFlowSession
    {
        $session = new ChatFlowSession;
        $session->setRawAttributes(['context' => json_encode($context)]);

        return $session;
    }

    /**
     * @param  callable(array):bool  $expectation
     */
    private function conversationExpectingItem(callable $expectation, bool $expectCreate = true): Conversation
    {
        $items = Mockery::mock(HasMany::class);
        if ($expectCreate) {
            $items->shouldReceive('create')->once()->with(Mockery::on($expectation))->andReturn(null);
        } else {
            $items->shouldReceive('create')->never();
        }

        $conversation = Mockery::mock(Conversation::class);
        $conversation->shouldReceive('items')->andReturn($items);

        return $conversation;
    }

    public function test_collect_input_sends_the_question_and_waits(): void
    {
        $conversation = $this->conversationExpectingItem(
            fn ($a) => $a['body'] === '¿Cuál es tu número de pedido?'
                && ($a['metadata']['sent_by_chatflow'] ?? false) === true
        );

        $node = ['id' => 'n1', 'type' => 'collect_input', 'data' => ['question' => '¿Cuál es tu número de pedido?']];

        $result = $this->handle([$node, $this->makeSession(), $conversation]);

        $this->assertNull($result); // pauses for the customer reply
    }

    public function test_collect_input_interpolates_context_variables(): void
    {
        $conversation = $this->conversationExpectingItem(
            fn ($a) => $a['body'] === 'Gracias Ada, ¿tu email?'
        );

        $node = ['id' => 'n1', 'type' => 'collect_input', 'data' => ['question' => 'Gracias {{nombre}}, ¿tu email?']];

        $this->handle([$node, $this->makeSession(['nombre' => 'Ada']), $conversation]);
    }

    public function test_collect_input_without_question_sends_nothing(): void
    {
        $conversation = $this->conversationExpectingItem(fn () => true, expectCreate: false);

        $node = ['id' => 'n1', 'type' => 'collect_input', 'data' => []];

        $result = $this->handle([$node, $this->makeSession(), $conversation]);

        $this->assertNull($result);
    }

    /** La nota de contexto tiene su propio test; aquí se aísla de los mocks de items(). */
    private function stubHandoffNote(): void
    {
        $note = Mockery::mock(HandoffContextNote::class);
        $note->shouldReceive('post')->once()->andReturn(true);
        $this->app->instance(HandoffContextNote::class, $note);
    }

    public function test_transfer_assigns_conversation_to_agent_and_group(): void
    {
        $this->stubHandoffNote();

        $items = Mockery::mock(HasMany::class);
        $items->shouldReceive('create')->once();

        $conversation = Mockery::mock(Conversation::class);
        $conversation->shouldReceive('items')->andReturn($items);
        $conversation->shouldReceive('releaseFromBot')->once();
        // assignTo() notifies the agent + broadcasts; the group is added on top.
        $conversation->shouldReceive('assignTo')->once()->with(5);
        $conversation->shouldReceive('update')->once()->with(['group_id' => 2]);

        $session = Mockery::mock(ChatFlowSession::class);
        $session->shouldReceive('getContextValue')->andReturn(null);
        $session->shouldReceive('flowConditions')->andReturn([]);
        $session->shouldReceive('update')->once()->with(Mockery::on(fn ($a) => ($a['status'] ?? null) === 'transferred'));

        $node = ['id' => 't', 'type' => 'transfer', 'data' => ['message' => 'Te transfiero', 'assignee_id' => 5, 'group_id' => 2]];

        $this->assertNull($this->handle([$node, $session, $conversation]));
    }

    public function test_transfer_without_assignment_does_not_update_conversation(): void
    {
        $this->stubHandoffNote();

        $items = Mockery::mock(HasMany::class);
        $items->shouldReceive('create')->once();

        $conversation = Mockery::mock(Conversation::class);
        $conversation->shouldReceive('items')->andReturn($items);
        $conversation->shouldReceive('releaseFromBot')->once();
        $conversation->shouldNotReceive('assignTo');
        $conversation->shouldNotReceive('assignToGroup');
        $conversation->shouldNotReceive('update');

        $session = Mockery::mock(ChatFlowSession::class);
        $session->shouldReceive('getContextValue')->andReturn(null);
        $session->shouldReceive('flowConditions')->andReturn([]);
        $session->shouldReceive('update')->once();

        $node = ['id' => 't', 'type' => 'transfer', 'data' => ['message' => 'Te transfiero']];

        $this->handle([$node, $session, $conversation]);
    }

    public function test_rich_message_with_multiple_cards_renders_carousel(): void
    {
        $conversation = $this->conversationExpectingItem(function ($a) {
            return count($a['metadata']['cards']) === 2
                && $a['metadata']['cards'][0]['title'] === 'Camisa'
                && $a['metadata']['cards'][1]['image_url'] === 'https://x/2.jpg'
                && str_contains($a['body'], '1. Comprar camisa')
                && str_contains($a['body'], '2. Comprar pantalón');
        });

        $node = ['id' => 'c', 'type' => 'rich_message', 'data' => [
            'title' => 'Nuestros productos',
            'options' => ['Comprar camisa', 'Comprar pantalón'],
            'cards' => [
                ['title' => 'Camisa', 'subtitle' => '29,90 €', 'image_url' => 'https://x/1.jpg', 'url' => 'https://shop/1'],
                ['title' => 'Pantalón', 'subtitle' => '39,90 €', 'image_url' => 'https://x/2.jpg'],
            ],
        ]];

        $result = $this->handle([$node, $this->makeSession(), $conversation]);

        $this->assertNull($result); // waits for the customer's numbered selection
    }

    public function test_rich_message_interpolates_card_context(): void
    {
        $conversation = $this->conversationExpectingItem(
            fn ($a) => $a['metadata']['cards'][0]['subtitle'] === 'Hola Ada'
        );

        $node = ['id' => 'c', 'type' => 'rich_message', 'data' => [
            'options' => ['A', 'B'],
            'cards' => [
                ['title' => 'Uno', 'subtitle' => 'Hola {{nombre}}'],
                ['title' => 'Dos'],
            ],
        ]];

        $this->handle([$node, $this->makeSession(['nombre' => 'Ada']), $conversation]);
    }

    public function test_send_file_creates_native_attachment_item(): void
    {
        $conversation = $this->conversationExpectingItem(function ($a) {
            return $a['attachment_urls'] === ['https://x/factura.pdf']
                && $a['metadata']['attachment']['type'] === 'document'
                && $a['metadata']['attachment']['url'] === 'https://x/factura.pdf'
                && $a['body'] === 'Aquí tienes tu factura';
        });

        $session = new ChatFlowSession;
        $session->setRawAttributes(['context' => json_encode([])]);
        $session->setRelation('chatFlow', tap(new ChatFlow, fn ($f) => $f->setRawAttributes(['nodes' => json_encode([])])));

        $node = ['id' => 'f', 'type' => 'send_file', 'data' => [
            'file_url' => 'https://x/factura.pdf', 'file_type' => 'document', 'caption' => 'Aquí tienes tu factura',
        ]];

        $this->handle([$node, $session, $conversation]);
    }

    public function test_create_ticket_does_not_call_contract_when_already_created(): void
    {
        // Idempotencia: si el contexto ya trae un ticket, no se crea otro.
        $contract = Mockery::mock(TicketServiceContract::class);
        $contract->shouldNotReceive('createFromConversation');
        $this->app->instance(TicketServiceContract::class, $contract);

        $session = new ChatFlowSession;
        $session->setRawAttributes(['context' => json_encode(['created_ticket_number' => 'TCK-2026-00001'])]);
        $session->setRelation('chatFlow', tap(new ChatFlow, fn ($f) => $f->setRawAttributes(['nodes' => json_encode([])])));

        $conversation = $this->conversationExpectingItem(fn () => true, expectCreate: false);

        $node = ['id' => 'ct', 'type' => 'create_ticket', 'data' => []];

        $this->assertNull($this->handle([$node, $session, $conversation]));
    }

    public function test_create_ticket_degrades_cleanly_when_tickets_unavailable(): void
    {
        // Si HelpdeskTickets está off el contrato devuelve null; el flujo no rompe
        // ni confirma ningún número al cliente, y continúa al siguiente nodo.
        $contract = Mockery::mock(TicketServiceContract::class);
        $contract->shouldReceive('createFromConversation')->once()->andReturn(null);
        $this->app->instance(TicketServiceContract::class, $contract);

        $session = new ChatFlowSession;
        $session->setRawAttributes(['context' => json_encode([])]);
        $session->setRelation('chatFlow', tap(new ChatFlow, fn ($f) => $f->setRawAttributes(['nodes' => json_encode([])])));

        $conversation = $this->conversationExpectingItem(fn () => true, expectCreate: false);
        $conversation->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $node = ['id' => 'ct', 'type' => 'create_ticket', 'data' => []];

        $this->assertNull($this->handle([$node, $session, $conversation]));
    }

    public function test_quick_replies_renders_numbered_prompt(): void
    {
        $conversation = $this->conversationExpectingItem(function ($a) {
            return str_contains($a['body'], '1. Ventas')
                && str_contains($a['body'], '2. Soporte')
                && str_contains($a['body'], 'Responde con el número')
                && $a['metadata']['bot_options'] === ['Ventas', 'Soporte'];
        });

        $node = ['id' => 'n1', 'type' => 'quick_replies', 'data' => ['text' => 'Elige', 'options' => ['Ventas', 'Soporte']]];

        $result = $this->handle([$node, $this->makeSession(), $conversation]);

        $this->assertNull($result);
    }

    public function test_executor_dispatches_each_type_to_its_registered_handler(): void
    {
        $conversation = Mockery::mock(Conversation::class);
        $session = new ChatFlowSession;
        $session->setRelation('conversation', $conversation);

        $handler = Mockery::mock(NodeHandler::class);
        $handler->shouldReceive('types')->andReturn(['custom_node']);
        $handler->shouldReceive('handle')->once()
            ->with(['id' => 'x', 'type' => 'custom_node'], $session, $conversation)->andReturn('next');

        $executeNode = new ReflectionMethod(ChatFlowNodeExecutor::class, 'executeNode');
        $executor = new ChatFlowNodeExecutor(new NodeHandlerRegistry([$handler]));

        $this->assertSame('next', $executeNode->invoke($executor, ['id' => 'x', 'type' => 'custom_node'], $session));
        $this->assertNull($executeNode->invoke($executor, ['id' => 'y', 'type' => 'unknown'], $session));
    }

    public function test_every_core_node_type_is_handled_or_routed(): void
    {
        $covered = [...$this->registry()->types(), ...ChatFlowNodeExecutor::ROUTING_TYPES];

        $this->assertSame([], array_values(array_diff(ChatFlow::NODE_TYPES, $covered)));
        $this->assertSame([], array_values(array_intersect($this->registry()->types(), ChatFlowNodeExecutor::ROUTING_TYPES)));
    }

    public function test_registry_rejects_two_handlers_for_the_same_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $localizer = new ChatFlowLocalizer(null);
        new NodeHandlerRegistry([new MessagingNodeHandler($localizer), new MessagingNodeHandler($localizer)]);
    }

    public function test_container_registry_covers_core_types_and_model_exposes_them(): void
    {
        $types = app(NodeHandlerRegistry::class)->types();

        $this->assertContains('ai_agent', $types);
        $this->assertContains('create_ticket', $types);
        // Los tipos del núcleo siempre; otros módulos pueden aportar los suyos
        // por el registry (p. ej. ai_action de HelpdeskAiPrompts).
        $this->assertEmpty(array_diff(ChatFlow::NODE_TYPES, ChatFlow::nodeTypes()));
        $this->assertEmpty(array_diff(ChatFlow::nodeTypes(), array_merge(ChatFlow::NODE_TYPES, $types)));
    }
}
