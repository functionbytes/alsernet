<?php

namespace Modules\Helpdesk\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_unread_conversations_count_uses_is_open(): void
    {
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);
        $conversation->status_id = $status->id;
        $conversation->save();

        $count = $customer->getUnreadConversationsCount();

        $this->assertEquals(1, $count);
    }

    public function test_scope_search_does_not_override_previous_filters(): void
    {
        Customer::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        Customer::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);

        $results = Customer::query()
            ->where('name', 'like', '%NonExistent%')
            ->search('alice')
            ->get();

        $this->assertCount(0, $results);
    }
}
