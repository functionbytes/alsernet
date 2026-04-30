<?php

namespace Modules\Chat\Tests\Feature\Widget;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Channels\Web;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Inboxes\Inbox;
use Tests\TestCase;

class WidgetValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;

    protected Web $webWidget;

    protected Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->inbox = Inbox::factory()->create(['account_id' => $this->account->id]);
        $this->webWidget = Web::create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'website_token' => Str::random(40),
            'widget_color' => '#1f93ff',
            'welcome_title' => 'Hello! 👋',
            'welcome_tagline' => 'How can we help you today?',
        ]);
    }

    public function test_store_conversation_validates_message_max_length(): void
    {
        $response = $this->postJson('/lc/api/conversation', [
            'website_token' => $this->webWidget->website_token,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'message' => str_repeat('a', 10001), // Exceeds 10,000 chars
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_store_conversation_validates_email_format(): void
    {
        $response = $this->postJson('/lc/api/conversation', [
            'website_token' => $this->webWidget->website_token,
            'email' => 'invalid-email',
            'name' => 'Test User',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_send_message_validates_content_required(): void
    {
        $customer = Customer::factory()->create(['account_id' => $this->account->id]);
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->postJson("/lc/api/conversation/{$conversation->id}/messages", [
            'customer_id' => $customer->id,
            'content' => '', // Empty content
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_send_message_validates_content_max_length(): void
    {
        $customer = Customer::factory()->create(['account_id' => $this->account->id]);
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->postJson("/lc/api/conversation/{$conversation->id}/messages", [
            'customer_id' => $customer->id,
            'content' => str_repeat('a', 10001), // Exceeds 10,000 chars
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_send_message_validates_content_type(): void
    {
        $customer = Customer::factory()->create(['account_id' => $this->account->id]);
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->postJson("/lc/api/conversation/{$conversation->id}/messages", [
            'customer_id' => $customer->id,
            'content' => 'Test message',
            'content_type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content_type']);
    }

    public function test_send_message_accepts_valid_content_types(): void
    {
        $customer = Customer::factory()->create(['account_id' => $this->account->id]);
        $openStatus = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Open',
            'slug' => 'open',
            'order' => 1,
        ]);
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'customer_id' => $customer->id,
            'status_id' => $openStatus->id,
        ]);

        $validTypes = ['text', 'file', 'image', 'audio', 'video'];

        foreach ($validTypes as $type) {
            $response = $this->postJson("/lc/api/conversation/{$conversation->id}/messages", [
                'customer_id' => $customer->id,
                'content' => 'Test message',
                'content_type' => $type,
            ]);

            $response->assertStatus(200);
        }
    }

    public function test_init_conversation_validates_email_format(): void
    {
        $response = $this->postJson("/api/widget/{$this->webWidget->website_token}/init", [
            'email' => 'invalid-email',
            'name' => 'Test User',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_init_conversation_validates_name_max_length(): void
    {
        $response = $this->postJson("/api/widget/{$this->webWidget->website_token}/init", [
            'email' => 'test@example.com',
            'name' => str_repeat('a', 256), // Exceeds 255 chars
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_widget_message_rate_limiting(): void
    {
        $customer = Customer::factory()->create(['account_id' => $this->account->id]);
        $openStatus = ConversationStatus::create([
            'account_id' => $this->account->id,
            'name' => 'Open',
            'slug' => 'open',
            'order' => 1,
        ]);
        $conversation = Conversation::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'customer_id' => $customer->id,
            'status_id' => $openStatus->id,
        ]);

        // Send 10 messages (the limit)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson("/lc/api/conversation/{$conversation->id}/messages", [
                'customer_id' => $customer->id,
                'content' => "Test message {$i}",
            ]);

            $response->assertStatus(200);
        }

        // 11th message should be rate limited
        $response = $this->postJson("/lc/api/conversation/{$conversation->id}/messages", [
            'customer_id' => $customer->id,
            'content' => 'Test message 11',
        ]);

        $response->assertStatus(429)
            ->assertJsonFragment(['error' => 'Too many messages sent']);
    }
}
