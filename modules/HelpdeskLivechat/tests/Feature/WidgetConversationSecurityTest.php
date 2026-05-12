<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Tests\TestCase;

class WidgetConversationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::factory()->create();
        $this->conversation = Conversation::factory()->create([
            'customer_id' => $this->customer->id,
        ]);
        $this->conversation->status_id = $status->id;
        $this->conversation->save();
    }

    public function test_show_requires_customer_email(): void
    {
        $this->getJson(route('helpdesk-livechat.widget.conversation.show', $this->conversation->id))
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Unauthorized');
    }

    public function test_show_allows_owner_with_email(): void
    {
        $this->getJson(route('helpdesk-livechat.widget.conversation.show', $this->conversation->id).'?customer_email='.urlencode($this->customer->email))
            ->assertOk()
            ->assertJsonPath('data.id', $this->conversation->id);
    }

    public function test_get_messages_requires_customer_email(): void
    {
        $this->getJson(route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id))
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Unauthorized');
    }

    public function test_get_messages_allows_owner_with_email(): void
    {
        $this->getJson(route('helpdesk-livechat.widget.conversation.messages.index', $this->conversation->id).'?customer_email='.urlencode($this->customer->email))
            ->assertOk()
            ->assertJsonPath('data.messages', []);
    }
}
