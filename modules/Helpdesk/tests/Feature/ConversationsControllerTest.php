<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Customer;
use Tests\TestCase;

class ConversationsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private ConversationStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $this->openStatus = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo([
            'helpdesk.conversations.view',
            'helpdesk.conversations.create',
            'helpdesk.conversations.update',
            'helpdesk.conversations.delete',
            'helpdesk.manage',
        ]);
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_conversations_index(): void
    {
        $this->get(route('manager.helpdesk.conversations.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('manager.helpdesk.conversations.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_conversations_index(): void
    {
        Conversation::factory()->count(3)->create();

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.index'))
            ->assertOk()
            ->assertViewIs('helpdesk::managers.helpdesk.conversations.index')
            ->assertViewHas('conversations');
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_manager_can_view_create_form(): void
    {
        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.create'))
            ->assertOk()
            ->assertViewIs('helpdesk::managers.helpdesk.conversations.create');
    }

    public function test_user_without_create_permission_cannot_access_create_form(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');

        $this->actingAs($user)
            ->get(route('manager.helpdesk.conversations.create'))
            ->assertForbidden();
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_manager_can_create_conversation(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.store'), [
                'customer_id' => $customer->id,
                'subject' => 'Test conversation subject',
                'priority' => 'normal',
                'status_id' => $this->openStatus->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_conversations', [
            'customer_id' => $customer->id,
            'subject' => 'Test conversation subject',
            'priority' => 'normal',
        ]);
    }

    public function test_store_validation_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.store'), [])
            ->assertSessionHasErrors(['customer_id', 'subject', 'priority', 'status_id']);
    }

    public function test_store_validation_rejects_invalid_priority(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.store'), [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'priority' => 'invalid-priority',
                'status_id' => $this->openStatus->id,
            ])
            ->assertSessionHasErrors(['priority']);
    }

    public function test_user_without_create_permission_cannot_store_conversation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('manager.helpdesk.conversations.store'), [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'priority' => 'normal',
                'status_id' => $this->openStatus->id,
            ])
            ->assertForbidden();
    }

    // ─── show ─────────────────────────────────────────────────────────────────

    public function test_manager_can_view_conversation(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.show', $conversation))
            ->assertOk()
            ->assertViewIs('helpdesk::managers.helpdesk.conversations.show')
            ->assertViewHas('conversation');
    }

    public function test_user_without_view_permission_cannot_view_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversation();

        $this->actingAs($user)
            ->get(route('manager.helpdesk.conversations.show', $conversation))
            ->assertForbidden();
    }

    // ─── update (form) ────────────────────────────────────────────────────────

    public function test_manager_can_update_conversation_subject(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.conversations.update', $conversation), [
                'subject' => 'Updated subject',
                'priority' => 'high',
                'status_id' => $this->openStatus->id,
            ])
            ->assertRedirect(route('manager.helpdesk.conversations.show', $conversation));

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'subject' => 'Updated subject',
            'priority' => 'high',
        ]);
    }

    public function test_user_without_update_permission_cannot_update_conversation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');
        $conversation = $this->createConversation();

        $this->actingAs($user)
            ->put(route('manager.helpdesk.conversations.update', $conversation), [
                'subject' => 'Updated',
                'priority' => 'normal',
                'status_id' => $this->openStatus->id,
            ])
            ->assertForbidden();
    }

    // ─── update (AJAX — priority) ─────────────────────────────────────────────

    public function test_manager_can_update_priority_via_ajax(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.conversations.update', $conversation), [
                'action' => 'update_priority',
                'priority' => 'urgent',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'priority' => 'urgent',
        ]);
    }

    // ─── update (AJAX — tag) ──────────────────────────────────────────────────

    public function test_manager_can_add_tag_via_ajax(): void
    {
        $conversation = $this->createConversation();
        $tag = ConversationTag::create([
            'name' => 'Billing',
            'slug' => 'billing',
            'color' => '#ff0000',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.conversations.update', $conversation), [
                'action' => 'add_tag',
                'tag_id' => $tag->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(
            $conversation->conversationTags()->where('tag_id', $tag->id)->exists()
        );
    }

    public function test_manager_can_remove_tag_via_ajax(): void
    {
        $conversation = $this->createConversation();
        $tag = ConversationTag::create([
            'name' => 'Support',
            'slug' => 'support',
            'color' => '#0000ff',
            'is_active' => true,
        ]);
        $conversation->conversationTags()->attach($tag->id);

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.conversations.update', $conversation), [
                'action' => 'remove_tag',
                'tag_id' => $tag->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFalse(
            $conversation->conversationTags()->where('tag_id', $tag->id)->exists()
        );
    }

    // ─── destroy / restore ────────────────────────────────────────────────────

    public function test_manager_can_soft_delete_conversation(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->delete(route('manager.helpdesk.conversations.destroy', $conversation))
            ->assertRedirect(route('manager.helpdesk.conversations.index'));

        $this->assertSoftDeleted('helpdesk_conversations', ['id' => $conversation->id]);
    }

    public function test_user_without_delete_permission_cannot_destroy_conversation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');
        $conversation = $this->createConversation();

        $this->actingAs($user)
            ->delete(route('manager.helpdesk.conversations.destroy', $conversation))
            ->assertForbidden();
    }

    public function test_manager_can_restore_soft_deleted_conversation(): void
    {
        $conversation = $this->createConversation();
        $conversation->delete();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.restore', $conversation->id))
            ->assertRedirect(route('manager.helpdesk.conversations.index'));

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'deleted_at' => null,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createConversation(array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create($overrides);
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }
}
