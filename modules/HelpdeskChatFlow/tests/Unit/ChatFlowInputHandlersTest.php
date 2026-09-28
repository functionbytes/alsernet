<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowIdentityOtp;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\CustomerIdentityResolver;
use Modules\HelpdeskChatFlow\Services\Input\DocumentUploadInputHandler;
use Modules\HelpdeskChatFlow\Services\Input\IdentificationInputHandler;
use Modules\HelpdeskChatFlow\Tests\Support\InMemoryChatFlowSession;
use Modules\HelpdeskChatFlow\Tests\TestCase;

/**
 * Handlers extracted from ChatFlowEngine: they reply on the node and return the
 * node the engine continues from (null = keep waiting / ended).
 */
class ChatFlowInputHandlersTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @return array{0: Conversation, 1: array<int, string>}
     */
    private function conversationCapturingBodies(): array
    {
        $bodies = new \ArrayObject;
        $items = Mockery::mock(HasMany::class);
        $items->shouldReceive('create')->andReturnUsing(function ($a) use ($bodies) {
            $bodies[] = $a['body'];

            return null;
        });
        $conversation = Mockery::mock(Conversation::class)->makePartial();
        $conversation->shouldReceive('items')->andReturn($items);

        return [$conversation, $bodies];
    }

    private function docsFlow(): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'docs', 'type' => 'request_documents', 'parentId' => null, 'data' => ['doc_types' => ['dni_frontal', 'factura']]],
            ['id' => 'after', 'type' => 'message', 'parentId' => 'docs', 'data' => ['text' => 'ok']],
        ];

        return $flow;
    }

    public function test_documents_pick_then_file_then_last_file_continues(): void
    {
        [$conversation, $bodies] = $this->conversationCapturingBodies();
        $session = new InMemoryChatFlowSession($this->docsFlow(), ['conversation' => $conversation]);
        $handler = new DocumentUploadInputHandler(new ChatFlowLocalizer(null));
        $node = $this->docsFlow()->nodes[0];

        // "2" announces the invoice and waits for its file.
        $this->assertNull($handler->handle($session, $node, '2', []));
        $this->assertSame('factura', $session->getContextStore()['_announced_doc_docs']);

        // The file goes to the announced doc; one still pending → keep waiting.
        $this->assertNull($handler->handle($session, $node, '', ['https://f/1.pdf']));
        $this->assertSame(['factura' => 'https://f/1.pdf'], $session->getContextStore()['_doc_uploads_docs']);

        // Last file (no pick → first pending) → continue to the child node.
        $this->assertSame('after', $handler->handle($session, $node, '', ['https://f/2.jpg']));
        $this->assertSame(['factura', 'dni_frontal'], $session->getContextStore()['uploaded_docs']);
        $this->assertStringContainsString('Todos los documentos recibidos', $bodies[count($bodies) - 1]);
    }

    public function test_documents_out_of_range_pick_explains_and_waits(): void
    {
        [$conversation, $bodies] = $this->conversationCapturingBodies();
        $session = new InMemoryChatFlowSession($this->docsFlow(), ['conversation' => $conversation]);
        $handler = new DocumentUploadInputHandler(new ChatFlowLocalizer(null));

        $this->assertNull($handler->handle($session, $this->docsFlow()->nodes[0], '7', []));
        $this->assertStringContainsString('no corresponde', $bodies[0]);
    }

    public function test_identification_failure_continues_through_else_branch(): void
    {
        Event::fake();

        $flow = new ChatFlow;
        $flow->nodes = [
            ['id' => 'id', 'type' => 'identify_customer', 'parentId' => null, 'data' => ['max_attempts' => 1]],
            ['id' => 'br', 'type' => 'branches', 'parentId' => 'id', 'data' => []],
            ['id' => 'yes', 'type' => 'branchItem', 'parentId' => 'br', 'data' => ['conditions' => []]],
            ['id' => 'else', 'type' => 'branchItem', 'parentId' => 'br', 'data' => ['isElse' => true]],
            ['id' => 'anon', 'type' => 'message', 'parentId' => 'else', 'data' => ['text' => 'Seguimos sin identificar']],
        ];

        $resolver = Mockery::mock(CustomerIdentityResolver::class);
        $resolver->shouldReceive('resolve')->once()->andReturn(null);
        $session = new InMemoryChatFlowSession($flow);

        $next = (new IdentificationInputHandler($resolver, new ChatFlowIdentityOtp))->handle($session, $flow->nodes[0], 'nadie');

        $this->assertSame('anon', $next);
        $this->assertFalse($session->getContextStore()['customer_identified']);
        $this->assertSame([], $session->updates, 'Con rama else no se transfiere.');
        Event::assertNotDispatched(ChatFlowCompleted::class);
    }
}
