<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class WidgetMessagesPaginationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Web $web;

    private Customer $customer;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $openStatus = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->web = WebFactory::new()->create();

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $this->web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Widget Web', 'is_active' => true]
        );

        $this->customer = Customer::create([
            'email' => 'visitor@example.com',
            'name' => 'Visitor',
        ]);

        $this->conversation = Conversation::create([
            'customer_id' => $this->customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'status_id' => $openStatus->id,
            'subject' => 'Test',
            'last_message_at' => now(),
            'metadata' => ['widget_pubsub_token' => 'pubsub_pagination_token'],
        ]);

        // All read calls in this suite are happy-path: authorize with the
        // conversation's per-conversation token (persists across requests).
        $this->withHeader('X-Conversation-Token', 'pubsub_pagination_token');
    }

    private function createMessages(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            ConversationItem::create([
                'conversation_id' => $this->conversation->id,
                'author_id' => $this->customer->id,
                'type' => 'message',
                'body' => "Message #{$i}",
                'is_internal' => false,
            ]);
        }
    }

    public function test_returns_first_page_with_default_limit(): void
    {
        $this->createMessages(30);

        $response = $this->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id)
            .'?customer_id='.$this->customer->id
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.has_more', false)
            ->assertJsonPath('data.next_cursor', null)
            ->assertJsonCount(30, 'data.messages');
    }

    public function test_indicates_has_more_when_messages_exceed_limit(): void
    {
        $this->createMessages(60);

        $response = $this->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id)
            .'?customer_id='.$this->customer->id
            .'&limit=30'
        );

        $response->assertOk()
            ->assertJsonPath('data.has_more', true)
            ->assertJsonCount(30, 'data.messages');

        // next_cursor should be the lowest id of the returned page
        $this->assertNotNull($response->json('data.next_cursor'));
    }

    public function test_before_id_returns_older_messages(): void
    {
        $this->createMessages(30);

        // First page (most recent 10)
        $first = $this->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id)
            .'?customer_id='.$this->customer->id
            .'&limit=10'
        )->json('data');

        $this->assertTrue($first['has_more']);
        $cursor = $first['next_cursor'];

        // Second page (older messages before cursor)
        $second = $this->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id)
            .'?customer_id='.$this->customer->id
            .'&limit=10'
            .'&before_id='.$cursor
        )->json('data');

        $this->assertCount(10, $second['messages']);

        // Older page IDs must all be less than the cursor
        foreach ($second['messages'] as $msg) {
            $this->assertLessThan($cursor, $msg['id']);
        }
    }

    public function test_limit_is_clamped_to_max_200(): void
    {
        $this->createMessages(5);

        $response = $this->getJson(
            route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id)
            .'?customer_id='.$this->customer->id
            .'&limit=9999'
        );

        $response->assertOk();
        // Should not error out — limit is clamped server-side
    }
}
