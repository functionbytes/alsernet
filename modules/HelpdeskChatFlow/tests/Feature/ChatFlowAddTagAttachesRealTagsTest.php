<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowDocumentLink;
use Modules\HelpdeskChatFlow\Services\ChatFlowHandoffSummary;
use Modules\HelpdeskChatFlow\Services\ChatFlowHttpRequester;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\ChatFlowNodeExecutor;
use Modules\HelpdeskChatFlow\Services\ChatFlowOrderLookup;
use Modules\HelpdeskChatFlow\Tests\Support\InMemoryChatFlowSession;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regresión: el nodo `add_tag` y la opción `add_tag` del nodo `action` solo
 * escribían `context.added_tags` (una variable interna del flujo) — no
 * quedaba ninguna etiqueta real en la conversación ni en el contacto, así
 * que no aparecía en el filtro de etiquetas de la bandeja ni en Contactos.
 */
class ChatFlowAddTagAttachesRealTagsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

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

    private function invoke(string $method, array $args): mixed
    {
        $m = new ReflectionMethod(ChatFlowNodeExecutor::class, $method);
        $m->setAccessible(true);

        return $m->invoke($this->executor(), ...$args);
    }

    private function newSession(): InMemoryChatFlowSession
    {
        return new InMemoryChatFlowSession(new ChatFlow, ['context' => []]);
    }

    public function test_add_tag_node_attaches_conversation_and_customer_tags(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $node = ['id' => 'tag1', 'type' => 'add_tag', 'data' => ['tags' => ['VIP', 'Reclamación']]];

        $this->invoke('executeAddTag', [$node, $this->newSession(), $conversation]);

        $this->assertSame(2, $conversation->conversationTags()->count());
        $this->assertDatabaseHas('helpdesk_conversation_tags', ['name' => 'VIP'], 'helpdesk');
        $this->assertSame(2, $customer->tags()->count());
        $this->assertDatabaseHas('helpdesk_customer_tags', ['name' => 'Reclamación'], 'helpdesk');
    }

    public function test_add_tag_node_is_idempotent(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $node = ['id' => 'tag1', 'type' => 'add_tag', 'data' => ['tags' => ['VIP']]];

        $this->invoke('executeAddTag', [$node, $this->newSession(), $conversation]);
        $this->invoke('executeAddTag', [$node, $this->newSession(), $conversation]);

        $this->assertSame(1, $conversation->conversationTags()->count());
    }

    public function test_action_node_add_tag_option_attaches_real_tag(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $node = ['id' => 'act1', 'type' => 'action', 'data' => ['action_type' => 'add_tag', 'tags' => ['Urgente']]];

        $this->invoke('executeAction', [$node, $this->newSession(), $conversation]);

        $this->assertSame(1, $conversation->conversationTags()->count());
        $this->assertDatabaseHas('helpdesk_conversation_tags', ['name' => 'Urgente'], 'helpdesk');
    }
}
