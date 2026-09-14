<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

/**
 * queuePosition() ordenaba por id (asumía id == orden de llegada == prioridad).
 * Ahora cuenta los que van por delante en la cola: mayor prioridad, o misma
 * prioridad y created_at anterior.
 */
class QueuePositionTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private ConversationStatus $open;

    private Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->open = $this->seedOpenConversationStatus();

        $web = WebFactory::new()->create();
        $this->inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Queue Inbox', 'is_active' => true]
        );
    }

    private function queued(string $priority, int $minutesAgo, ?string $token = null): Conversation
    {
        $conversation = Conversation::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'inbox_id' => $this->inbox->id,
            'status_id' => $this->open->id,
            'assignee_id' => null,
            'priority' => $priority,
            'channel' => 'web',
            'metadata' => $token ? ['widget_pubsub_token' => $token] : [],
        ]);

        $conversation->forceFill(['created_at' => now()->subMinutes($minutesAgo)])->save();

        return $conversation;
    }

    public function test_position_reflects_priority_then_arrival_time(): void
    {
        // Mine: normal, arrived 120 min ago.
        $mine = $this->queued('normal', 120, 'mytoken');

        $this->queued('urgent', 30);   // ahead: higher priority (arrived later, still ahead)
        $this->queued('normal', 200);  // ahead: same priority, arrived earlier
        $this->queued('normal', 10);   // behind: same priority, arrived later
        $this->queued('low', 300);     // behind: lower priority even if older

        $response = $this->withHeader('X-Conversation-Token', 'mytoken')
            ->getJson(route('helpdesk-livechat.widget.conversation.queue-position', $mine->id))
            ->assertOk();

        // 2 ahead (urgent + older normal) → position 3.
        $response->assertJsonPath('data.position', 3);
        $response->assertJsonPath('data.is_assigned', false);
    }

    public function test_assigned_conversation_reports_position_zero(): void
    {
        $mine = $this->queued('normal', 60, 'mytoken2');
        $mine->update(['assignee_id' => 1]);

        $this->withHeader('X-Conversation-Token', 'mytoken2')
            ->getJson(route('helpdesk-livechat.widget.conversation.queue-position', $mine->id))
            ->assertOk()
            ->assertJsonPath('data.position', 0)
            ->assertJsonPath('data.is_assigned', true);
    }
}
