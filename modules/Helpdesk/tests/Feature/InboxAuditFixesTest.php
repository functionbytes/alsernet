<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\Inbox;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cobertura de los arreglos de la auditoría del inbox (jul-2026):
 * validación de group_id, restore JSON, papelera (view=deleted), acceso por
 * inbox en restore, bulk-assign con capacidad, y vistas guardadas.
 */
class InboxAuditFixesTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

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
        $this->manager->assignRole(Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']));
    }

    private function createConversation(array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create($overrides);
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }

    private function inbox(string $name): Inbox
    {
        return Inbox::create(['name' => $name, 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
    }

    // ── S2: group_id validado en el update AJAX ──────────────────────────────

    public function test_update_rejects_invalid_group_id(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.conversations.update', $conversation), [
                'group_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('group_id');
    }

    // ── #7: restore() responde JSON cuando se pide JSON ──────────────────────

    public function test_restore_returns_json_when_wants_json(): void
    {
        $conversation = $this->createConversation();
        $conversation->delete();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.restore', $conversation->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'deleted_at' => null,
        ], 'helpdesk');
    }

    // ── #8: la papelera lista SOLO las eliminadas (regresión lista vacía) ─────

    public function test_deleted_view_lists_only_trashed_conversations(): void
    {
        $active = $this->createConversation();
        $trashed = $this->createConversation();
        $trashed->delete();

        $html = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.list', ['view' => 'deleted']))
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('data-bv-conv-id="'.$trashed->id.'"', $html);
        $this->assertStringNotContainsString('data-bv-conv-id="'.$active->id.'"', $html);
    }

    // ── S3: restore requiere acceso al inbox de la conversación ──────────────

    public function test_restore_requires_inbox_access(): void
    {
        $inboxA = $this->inbox('Inbox A');
        $inboxB = $this->inbox('Inbox B');
        $conversation = $this->createConversation(['inbox_id' => $inboxB->id]);
        $conversation->delete();

        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdesk.view', 'helpdesk.conversations.view', 'helpdesk.conversations.manage']);
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $inboxA->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.restore', $conversation->id))
            ->assertForbidden();

        $this->assertSoftDeleted('helpdesk_conversations', ['id' => $conversation->id], connection: 'helpdesk');
    }

    // ── S4: la asignación masiva salta agentes sin capacidad en el inbox ─────

    public function test_bulk_assign_skips_assignee_without_inbox_capacity(): void
    {
        $inboxA = $this->inbox('Inbox A');
        $inboxB = $this->inbox('Inbox B');
        $conversation = $this->createConversation(['inbox_id' => $inboxB->id]);

        $assignee = User::factory()->create();
        // Solo puede atender el inbox A, no el B de la conversación.
        AgentInboxCapacity::create(['user_id' => $assignee->id, 'inbox_id' => $inboxA->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.bulk'), [
                'action' => 'assign',
                'ids' => [$conversation->id],
                'payload' => ['assignee_id' => $assignee->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'assignee_id' => null,
        ], 'helpdesk');
    }

    // ── #9: guardar vista exige el permiso de la ruta ────────────────────────

    public function test_view_store_requires_permission(): void
    {
        // Tiene acceso general al panel (helpdesk.view) pero no el permiso
        // específico que exige la ruta de guardar vista.
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.view');

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.views.store'), [
                'name' => 'Mis urgentes',
                'filters' => ['priority' => 'urgent'],
            ])
            ->assertForbidden();
    }

    // ── #10: solo el dueño (o un gestor) puede borrar una vista guardada ─────

    public function test_agent_cannot_delete_another_users_saved_view(): void
    {
        $owner = User::factory()->create();
        $view = ConversationView::factory()->create(['user_id' => $owner->id]);

        $other = User::factory()->create();
        $other->givePermissionTo(['helpdesk.view', 'helpdesk.conversations.view']);

        $this->actingAs($other)
            ->deleteJson(route('manager.helpdesk.views.destroy', $view))
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_conversation_views', ['id' => $view->id], 'helpdesk');
    }

    public function test_owner_can_delete_their_saved_view(): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo(['helpdesk.view', 'helpdesk.conversations.view']);
        $view = ConversationView::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->deleteJson(route('manager.helpdesk.views.destroy', $view))
            ->assertOk();

        $this->assertDatabaseMissing('helpdesk_conversation_views', ['id' => $view->id], 'helpdesk');
    }

    // ── markSpam exige helpdesk.conversations.update (no basta con ver) ───────

    public function test_mark_spam_requires_update_permission(): void
    {
        $conversation = $this->createConversation();

        // Puede acceder al panel (pasa el middleware del grupo) pero no tiene
        // el permiso que exige MarkSpamRequest::authorize().
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.view');

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.conversations.mark-spam', $conversation))
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'is_spam' => false,
        ], 'helpdesk');
    }
}
