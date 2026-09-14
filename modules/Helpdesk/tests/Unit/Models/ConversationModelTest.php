<?php

namespace Modules\Helpdesk\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Tests\TestCase;

class ConversationModelTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_fillable_includes_expected_fields(): void
    {
        $conversation = new Conversation;
        $fillable = $conversation->getFillable();

        $this->assertContains('subject', $fillable);
        $this->assertContains('customer_id', $fillable);
        $this->assertContains('priority', $fillable);
        $this->assertNotContains('deleted_at', $fillable);
    }

    public function test_scope_search_does_not_override_previous_filters(): void
    {
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $customer = Customer::factory()->create(['name' => 'John Doe']);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Help needed',
        ]);
        $conversation->status_id = $status->id;
        $conversation->save();

        $results = Conversation::query()
            ->where('subject', 'like', '%NonExistent%')
            ->search('John')
            ->get();

        $this->assertCount(0, $results);
    }

    public function test_get_time_to_first_response_returns_positive_minutes(): void
    {
        $conversation = new Conversation;
        $conversation->created_at = now()->subMinutes(30);
        $conversation->first_response_at = now()->subMinutes(10);

        $minutes = $conversation->getTimeToFirstResponse();

        $this->assertEquals(20, $minutes);
    }

    public function test_get_duration_returns_positive_minutes(): void
    {
        $conversation = new Conversation;
        $conversation->created_at = now()->subMinutes(60);
        $conversation->closed_at = now()->subMinutes(15);

        $minutes = $conversation->getDuration();

        $this->assertEquals(45, $minutes);
    }
}
