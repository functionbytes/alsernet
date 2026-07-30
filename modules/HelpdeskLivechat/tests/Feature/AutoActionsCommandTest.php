<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

class AutoActionsCommandTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private ConversationStatus $open;

    private ConversationStatus $closed;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('helpdesk:conv-closed-status');

        $this->open = $this->seedOpenConversationStatus();
        $this->closed = ConversationStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#999999', 'is_open' => false, 'order' => 9]
        );
    }

    /**
     * @param  array<string, mixed>  $webOverrides
     * @return array{0: Inbox, 1: Customer}
     */
    private function context(array $webOverrides): array
    {
        $web = WebFactory::new()->create(array_merge([
            'enable_auto_transfer' => false,
            'enable_auto_inactive' => false,
            'enable_auto_close' => false,
        ], $webOverrides));

        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Web', 'is_active' => true]
        );

        return [$inbox, Customer::factory()->create()];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function conversation(Inbox $inbox, Customer $customer, int $idleMinutes, array $extra = []): Conversation
    {
        return Conversation::create(array_merge([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'channel' => 'web',
            'status_id' => $this->open->id,
            'subject' => 'Test',
            'last_message_at' => now()->subMinutes($idleMinutes),
        ], $extra));
    }

    public function test_auto_close_closes_idle_conversation(): void
    {
        [$inbox, $customer] = $this->context(['enable_auto_close' => true, 'auto_close_minutes' => 30]);
        $conversation = $this->conversation($inbox, $customer, 45);

        $this->artisan('helpdesk-livechat:process-auto-actions')->assertSuccessful();

        $conversation->refresh();
        $this->assertNotNull($conversation->closed_at);
        $this->assertSame($this->closed->id, $conversation->status_id);
    }

    public function test_does_not_close_recently_active_conversation(): void
    {
        [$inbox, $customer] = $this->context(['enable_auto_close' => true, 'auto_close_minutes' => 30]);
        $conversation = $this->conversation($inbox, $customer, 10);

        $this->artisan('helpdesk-livechat:process-auto-actions')->assertSuccessful();

        $conversation->refresh();
        $this->assertNull($conversation->closed_at);
    }

    public function test_auto_inactive_flags_idle_conversation(): void
    {
        [$inbox, $customer] = $this->context(['enable_auto_inactive' => true, 'auto_inactive_minutes' => 10]);
        $conversation = $this->conversation($inbox, $customer, 20);

        $this->artisan('helpdesk-livechat:process-auto-actions')->assertSuccessful();

        $conversation->refresh();
        $this->assertNotEmpty($conversation->metadata['inactive_since'] ?? null);
    }

    public function test_auto_transfer_unassigns_unanswered_assigned_conversation(): void
    {
        [$inbox, $customer] = $this->context(['enable_auto_transfer' => true, 'auto_transfer_minutes' => 5]);
        $agent = User::factory()->create();
        $conversation = $this->conversation($inbox, $customer, 15, ['assignee_id' => $agent->id]);

        // Last non-internal message is from the visitor → agent has not replied.
        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $customer->id,
            'type' => 'message',
            'body' => 'Hola, sigo esperando',
            'is_internal' => false,
        ]);

        $this->artisan('helpdesk-livechat:process-auto-actions')->assertSuccessful();

        $conversation->refresh();
        $this->assertNull($conversation->assignee_id);
    }

    public function test_disabled_flags_do_nothing(): void
    {
        [$inbox, $customer] = $this->context([]);
        $conversation = $this->conversation($inbox, $customer, 240);

        $this->artisan('helpdesk-livechat:process-auto-actions')->assertSuccessful();

        $conversation->refresh();
        $this->assertNull($conversation->closed_at);
        $this->assertEmpty($conversation->metadata['inactive_since'] ?? null);
    }
}
