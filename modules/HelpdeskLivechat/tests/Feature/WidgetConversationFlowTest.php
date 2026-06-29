<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class WidgetConversationFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Web $web;

    private ConversationStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openStatus = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->web = WebFactory::new()->create();
    }

    public function test_widget_can_create_new_conversation(): void
    {
        Event::fake([ConversationCreated::class]);

        $response = $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $this->web->website_token,
            'email' => 'visitor@example.com',
            'name' => 'Test Visitor',
            'message' => 'Hola',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['conversation_id', 'customer_id', 'reused']]);

        $this->assertDatabaseHas('helpdesk_conversations', [
            'channel' => 'web',
        ]);

        Event::assertDispatched(ConversationCreated::class);
    }

    public function test_widget_reuses_open_conversation_for_same_customer(): void
    {
        Event::fake([ConversationCreated::class]);

        // First request — creates a new conversation
        $first = $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $this->web->website_token,
            'email' => 'repeat@example.com',
            'message' => 'Primera vez',
        ]);

        $first->assertOk();
        $firstConversationId = $first->json('data.conversation_id');

        // Second request with same email — should reuse
        $second = $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $this->web->website_token,
            'email' => 'repeat@example.com',
            'message' => 'Segunda vez',
        ]);

        $second->assertOk()
            ->assertJsonPath('data.reused', true)
            ->assertJsonPath('data.conversation_id', $firstConversationId);
    }

    public function test_widget_can_send_message_to_existing_conversation(): void
    {
        $customer = Customer::factory()->create(['email' => 'sender@example.com']);

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $this->web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Widget Test', 'is_active' => true]
        );

        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'metadata' => ['widget_pubsub_token' => 'pubsub_flow_send_token'],
        ]);

        $response = $this->withHeaders(['X-Conversation-Token' => 'pubsub_flow_send_token'])->postJson(
            route('helpdesk-livechat.widget.conversation.messages.send', $conversation->id),
            [
                'content' => 'Hola, necesito ayuda',
                'customer_email' => $customer->email,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['message_id', 'created_at']]);

        $this->assertDatabaseHas('helpdesk_conversation_items', [
            'conversation_id' => $conversation->id,
            'type' => 'message',
        ]);
    }

    public function test_get_messages_eager_loads_relations(): void
    {
        $customer = Customer::factory()->create(['email' => 'query@example.com']);

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $this->web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Widget Test', 'is_active' => true]
        );

        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'metadata' => ['widget_pubsub_token' => 'pubsub_flow_query_token'],
        ]);

        ConversationItem::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
            'author_id' => $customer->id,
            'is_internal' => false,
        ]);

        DB::connection('helpdesk')->enableQueryLog();

        $this->withHeaders(['X-Conversation-Token' => 'pubsub_flow_query_token'])->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $conversation->id)
                .'?customer_email='.urlencode($customer->email)
        )->assertOk();

        $queries = DB::connection('helpdesk')->getQueryLog();

        // Should use at most 3 queries: 1 to resolve customer, 1 to fetch conversation, 1 to fetch items
        $this->assertLessThanOrEqual(3, count($queries), 'Too many queries — possible N+1 detected');

        DB::connection('helpdesk')->disableQueryLog();
    }
}
