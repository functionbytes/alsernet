<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
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
use ReflectionMethod;
use Tests\TestCase;

/**
 * Exercises the real executor's node methods directly (via reflection) with a
 * mocked items() relation, so we cover the live executor without a database.
 */
class ChatFlowNodeExecutorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function executor(): ChatFlowNodeExecutor
    {
        return new ChatFlowNodeExecutor(
            Mockery::mock(ChatFlowAiResponder::class),
            Mockery::mock(ChatFlowOrderLookup::class),
            new ChatFlowHttpRequester,
            new ChatFlowLocalizer(null),
            Mockery::mock(ChatFlowAgentService::class),
            Mockery::mock(ChatFlowHandoffSummary::class),
            new ChatFlowDocumentLink(null),
        );
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

    private function invoke(string $method, array $args): mixed
    {
        $m = new ReflectionMethod(ChatFlowNodeExecutor::class, $method);
        $m->setAccessible(true);

        return $m->invoke($this->executor(), ...$args);
    }

    public function test_collect_input_sends_the_question_and_waits(): void
    {
        $conversation = $this->conversationExpectingItem(
            fn ($a) => $a['body'] === '¿Cuál es tu número de pedido?'
                && ($a['metadata']['sent_by_chatflow'] ?? false) === true
        );

        $node = ['id' => 'n1', 'type' => 'collect_input', 'data' => ['question' => '¿Cuál es tu número de pedido?']];

        $result = $this->invoke('executeCollectInput', [$node, $this->makeSession(), $conversation]);

        $this->assertNull($result); // pauses for the customer reply
    }

    public function test_collect_input_interpolates_context_variables(): void
    {
        $conversation = $this->conversationExpectingItem(
            fn ($a) => $a['body'] === 'Gracias Ada, ¿tu email?'
        );

        $node = ['id' => 'n1', 'type' => 'collect_input', 'data' => ['question' => 'Gracias {{nombre}}, ¿tu email?']];

        $this->invoke('executeCollectInput', [$node, $this->makeSession(['nombre' => 'Ada']), $conversation]);
    }

    public function test_collect_input_without_question_sends_nothing(): void
    {
        $conversation = $this->conversationExpectingItem(fn () => true, expectCreate: false);

        $node = ['id' => 'n1', 'type' => 'collect_input', 'data' => []];

        $result = $this->invoke('executeCollectInput', [$node, $this->makeSession(), $conversation]);

        $this->assertNull($result);
    }

    public function test_transfer_assigns_conversation_to_agent_and_group(): void
    {
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

        $this->assertNull($this->invoke('executeTransfer', [$node, $session, $conversation]));
    }

    public function test_transfer_without_assignment_does_not_update_conversation(): void
    {
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

        $this->invoke('executeTransfer', [$node, $session, $conversation]);
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

        $result = $this->invoke('executeRichMessage', [$node, $this->makeSession(), $conversation]);

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

        $this->invoke('executeRichMessage', [$node, $this->makeSession(['nombre' => 'Ada']), $conversation]);
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

        $this->invoke('executeSendFile', [$node, $session, $conversation]);
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

        $result = $this->invoke('executeQuickReplies', [$node, $this->makeSession(), $conversation]);

        $this->assertNull($result);
    }
}
