<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class ConversationCardChipsAndPresenceTest extends HelpdeskTestCase
{
    private User $otherAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otherAgent = User::factory()->create();
        $this->otherAgent->assignRole('super-settings');
    }

    public function test_card_exposes_status_channel_label_and_assignee(): void
    {
        $this->manager->forceFill(['firstname' => 'Ana', 'lastname' => 'Lopez'])->save();
        $conversation = $this->makeConversation(['channel' => 'facebook', 'assignee_id' => $this->manager->id]);

        $card = $this->card($conversation);

        $this->assertSame(['name' => 'Open', 'slug' => 'open', 'color' => '#13C672'], $card['status']);
        $this->assertSame('Messenger', $card['channelLabel']);
        $this->assertSame(
            ['id' => $this->manager->id, 'name' => 'Ana Lopez', 'initials' => 'AL'],
            $card['assignee']
        );
    }

    public function test_unassigned_conversation_has_null_assignee(): void
    {
        $this->assertNull($this->card($this->makeConversation())['assignee']);
    }

    public function test_unanswered_flag(): void
    {
        $conversation = $this->makeConversation();
        $this->assertFalse($this->card($conversation)['unanswered']);

        $this->addCustomerMessage($conversation);
        $this->assertTrue($this->card($conversation)['unanswered']);

        $conversation->forceFill(['first_response_at' => now()])->save();
        $this->assertFalse($this->card($conversation)['unanswered']);
    }

    public function test_unanswered_is_false_when_closed(): void
    {
        $conversation = $this->makeConversation();
        $this->addCustomerMessage($conversation);
        $closed = ConversationStatus::query()->where('is_open', false)->first()
            ?? ConversationStatus::create(['name' => 'Closed', 'slug' => 'closed-x', 'color' => '#999', 'is_open' => false, 'order' => 9]);
        $conversation->forceFill(['status_id' => $closed->id])->save();

        $card = $this->card($conversation);

        $this->assertFalse($card['unanswered']);
        $this->assertNull($card['slaChip']);
    }

    public function test_sla_chip_breach_for_overdue_first_response(): void
    {
        $conversation = $this->makeConversation([
            'sla_first_response_due_at' => now()->subMinutes(30),
        ]);

        $chip = $this->card($conversation)['slaChip'];

        $this->assertSame('breach', $chip['kind']);
        $this->assertSame('1ª respuesta', $chip['label']);
        $this->assertSame('first_response', $chip['type']);
        $this->assertStringEndsWith('vencido', $chip['text']);
    }

    public function test_sla_chip_warn_and_ok(): void
    {
        $warn = $this->makeConversation(['sla_first_response_due_at' => now()->addMinutes(45)->addSeconds(30)]);
        $chip = $this->card($warn)['slaChip'];
        $this->assertSame('warn', $chip['kind']);
        $this->assertSame('45m', $chip['text']);

        $ok = $this->makeConversation(['sla_first_response_due_at' => now()->addHours(2)->addMinutes(10)->addSeconds(30)]);
        $chip = $this->card($ok)['slaChip'];
        $this->assertSame('ok', $chip['kind']);
        $this->assertSame('2h 10m', $chip['text']);
    }

    public function test_sla_chip_uses_resolution_after_first_response(): void
    {
        $conversation = $this->makeConversation([
            'first_response_at' => now()->subHour(),
            'sla_first_response_due_at' => now()->subDay(),
            'sla_resolution_due_at' => now()->addDays(12)->addHours(12)->addMinutes(5),
        ]);

        $chip = $this->card($conversation)['slaChip'];

        $this->assertSame('Resolución', $chip['label']);
        $this->assertSame('resolution', $chip['type']);
        $this->assertSame('ok', $chip['kind']);
        $this->assertSame('12d 12h', $chip['text']);
    }

    public function test_sla_chip_paused_and_missing(): void
    {
        $paused = $this->makeConversation([
            'sla_first_response_due_at' => now()->subHour(),
            'sla_paused_at' => now(),
        ]);
        $this->assertSame('ok', $this->card($paused)['slaChip']['kind']);
        $this->assertSame('en pausa', $this->card($paused)['slaChip']['text']);

        $this->assertNull($this->card($this->makeConversation())['slaChip']);
    }

    public function test_tags_are_limited_to_three_with_overflow_count(): void
    {
        $conversation = $this->makeConversation();
        foreach (range(1, 5) as $i) {
            $tag = ConversationTag::create(['name' => "Tag {$i} ".uniqid(), 'color' => '#abcdef']);
            $conversation->conversationTags()->attach($tag->id);
        }

        $card = Conversation::query()->with(['customer', 'status', 'assignee', 'inbox', 'conversationTags'])
            ->findOrFail($conversation->id)->toInboxArray();

        $this->assertCount(3, $card['tags']);
        $this->assertSame(2, $card['tagsMore']);
        $this->assertSame('#abcdef', $card['tags'][0]['color']);
    }

    public function test_heartbeat_returns_other_viewers_and_leave_removes_them(): void
    {
        $conversation = $this->makeConversation();

        $this->actingAs($this->otherAgent)
            ->postJson(route('manager.helpdesk.conversations.presence.heartbeat', $conversation), ['action' => 'replying'])
            ->assertOk()
            ->assertJsonPath('viewers', []);

        $response = $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.presence.heartbeat', $conversation))
            ->assertOk()
            ->assertJsonCount(1, 'viewers')
            ->assertJsonPath('viewers.0.user_id', $this->otherAgent->id)
            ->assertJsonPath('viewers.0.action', 'replying');
        $this->assertArrayHasKey('name', $response->json('viewers.0'));

        $this->actingAs($this->otherAgent)
            ->deleteJson(route('manager.helpdesk.conversations.presence.leave', $conversation))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.presence.heartbeat', $conversation))
            ->assertJsonPath('viewers', []);
    }

    public function test_heartbeat_rejects_invalid_action(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.presence.heartbeat', $this->makeConversation()), ['action' => 'dancing'])
            ->assertStatus(422);
    }

    public function test_overview_excludes_self_and_unviewable_conversations(): void
    {
        $visible = $this->makeConversation();
        $mine = $this->makeConversation();
        $hidden = $this->makeConversation();

        $restricted = User::factory()->create();
        $restricted->assignRole('helpdesk-agent-restricted');

        $this->actingAs($this->otherAgent)
            ->postJson(route('manager.helpdesk.conversations.presence.heartbeat', $visible));
        $this->actingAs($this->otherAgent)
            ->postJson(route('manager.helpdesk.conversations.presence.heartbeat', $hidden));
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.presence.heartbeat', $mine));

        $ids = implode(',', [$visible->id, $mine->id, $hidden->id]);

        $data = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.presence.overview', ['ids' => $ids]))
            ->assertOk()
            ->json('data');

        $this->assertSame([(string) $visible->id, (string) $hidden->id], array_map('strval', array_keys($data)));

        // El agente restringido solo ve lo asignado a él: nada de lo anterior.
        $this->actingAs($restricted)
            ->getJson(route('manager.helpdesk.conversations.presence.overview', ['ids' => $ids]))
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_heartbeat_is_forbidden_for_a_conversation_the_agent_cannot_view(): void
    {
        $restricted = User::factory()->create();
        $restricted->assignRole('helpdesk-agent-restricted');

        $this->actingAs($restricted)
            ->postJson(route('manager.helpdesk.conversations.presence.heartbeat', $this->makeConversation()))
            ->assertForbidden();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeConversation(array $attributes = []): Conversation
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'web',
            'is_archived' => false,
        ]);

        // Update vía query builder: evita los hooks del modelo que recalculan el SLA.
        Conversation::query()->whereKey($conversation->id)->update(array_merge([
            'status_id' => $this->openStatus->id,
            'channel' => 'web',
            'first_response_at' => null,
            'assignee_id' => null,
            'sla_policy_id' => null,
            'sla_first_response_due_at' => null,
            'sla_resolution_due_at' => null,
            'sla_first_response_breached' => false,
            'sla_resolution_breached' => false,
            'sla_paused_at' => null,
        ], $attributes));

        return $conversation->fresh();
    }

    private function addCustomerMessage(Conversation $conversation): void
    {
        ConversationItem::factory()->fromCustomer($conversation->customer_id)->create(['conversation_id' => $conversation->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function card(Conversation $conversation): array
    {
        return Conversation::query()
            ->with(['customer', 'status', 'assignee', 'inbox', 'conversationTags'])
            ->withCount(['items as incoming_messages_count' => fn ($q) => $q->where('type', 'message')->whereNull('user_id')])
            ->findOrFail($conversation->id)
            ->toInboxArray();
    }
}
