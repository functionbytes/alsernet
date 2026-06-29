<?php

namespace Modules\HelpdeskChatFlow\tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskChatFlow\Database\Seeders\ChatFlowPermissionsSeeder;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatFlowsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $user;

    private bool $helpdeskDbAvailable = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChatFlowPermissionsSeeder::class);

        $superAdmin = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole($superAdmin);
        $this->user->givePermissionTo([
            'chatflow.view',
            'chatflow.create',
            'chatflow.update',
            'chatflow.delete',
        ]);

        try {
            \DB::connection('helpdesk')->statement('SELECT 1 FROM helpdesk_chat_flows LIMIT 1');
            $this->helpdeskDbAvailable = true;
        } catch (\Throwable) {
            $this->helpdeskDbAvailable = false;
        }
    }

    private function requireHelpdeskDb(): void
    {
        if (! $this->helpdeskDbAvailable) {
            $this->markTestSkipped('helpdesk_chat_flows table not available in test DB (system_test_pristine).');
        }
    }

    // ─── index ─────────────────────────────────────────────────────────────────

    public function test_index_requires_authentication(): void
    {
        $this->get(route('chatflow.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_returns_ok_for_authorized_user(): void
    {
        $this->requireHelpdeskDb();
        $this->actingAs($this->user)
            ->get(route('chatflow.index'))
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_index(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->get(route('chatflow.index'))
            ->assertForbidden();
    }

    // ─── create ────────────────────────────────────────────────────────────────

    public function test_create_returns_ok_for_authorized_user(): void
    {
        $this->requireHelpdeskDb();
        $this->actingAs($this->user)
            ->get(route('chatflow.create'))
            ->assertOk();
    }

    public function test_create_requires_authentication(): void
    {
        $this->get(route('chatflow.create'))
            ->assertRedirect(route('auth.login'));
    }

    // ─── store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_chatflow_with_valid_data(): void
    {
        $this->requireHelpdeskDb();

        $payload = [
            'name' => 'Flow de bienvenida',
            'trigger_type' => 'conversation_start',
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('chatflow.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_chat_flows', [
            'name' => 'Flow de bienvenida',
            'trigger_type' => 'conversation_start',
        ], 'helpdesk');
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->requireHelpdeskDb();
        $this->actingAs($this->user)
            ->post(route('chatflow.store'), ['trigger_type' => 'manual'])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_fails_validation_without_trigger_type(): void
    {
        $this->requireHelpdeskDb();
        $this->actingAs($this->user)
            ->post(route('chatflow.store'), ['name' => 'Test Flow'])
            ->assertSessionHasErrors(['trigger_type']);
    }

    public function test_store_fails_validation_with_invalid_trigger_type(): void
    {
        $this->actingAs($this->user)
            ->post(route('chatflow.store'), [
                'name' => 'Test Flow',
                'trigger_type' => 'invalid_trigger',
            ])
            ->assertSessionHasErrors(['trigger_type']);
    }

    public function test_store_requires_create_permission(): void
    {
        $this->requireHelpdeskDb();
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo('chatflow.view');

        $this->actingAs($viewOnly)
            ->post(route('chatflow.store'), [
                'name' => 'Unauthorized Flow',
                'trigger_type' => 'manual',
            ])
            ->assertForbidden();
    }

    // ─── edit ──────────────────────────────────────────────────────────────────

    public function test_edit_returns_ok_for_existing_chatflow(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();

        $this->actingAs($this->user)
            ->get(route('chatflow.edit', $flow))
            ->assertOk();
    }

    public function test_edit_returns_404_for_nonexistent_chatflow(): void
    {
        $this->requireHelpdeskDb();
        $this->actingAs($this->user)
            ->get(route('chatflow.edit', 999999))
            ->assertNotFound();
    }

    // ─── update ────────────────────────────────────────────────────────────────

    public function test_update_modifies_chatflow_data(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create(['name' => 'Original Name']);

        $this->actingAs($this->user)
            ->put(route('chatflow.update', $flow), [
                'name' => 'Updated Name',
                'trigger_type' => 'manual',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_chat_flows', [
            'id' => $flow->id,
            'name' => 'Updated Name',
        ], 'helpdesk');
    }

    public function test_update_requires_update_permission(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo('chatflow.view');

        $this->actingAs($viewOnly)
            ->put(route('chatflow.update', $flow), [
                'name' => 'Hijacked Name',
                'trigger_type' => 'manual',
            ])
            ->assertForbidden();
    }

    // ─── destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_chatflow(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create(['name' => 'Flow a eliminar']);

        $this->actingAs($this->user)
            ->delete(route('chatflow.destroy', $flow))
            ->assertRedirect(route('chatflow.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('helpdesk_chat_flows', ['id' => $flow->id], 'helpdesk');
    }

    public function test_destroy_requires_delete_permission(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo('chatflow.view');

        $this->actingAs($viewOnly)
            ->delete(route('chatflow.destroy', $flow))
            ->assertForbidden();
    }

    // ─── publish ───────────────────────────────────────────────────────────────

    public function test_publish_sets_status_to_active(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->draft()->create();

        $this->actingAs($this->user)
            ->post(route('chatflow.publish', $flow))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_chat_flows', [
            'id' => $flow->id,
            'status' => 'active',
        ], 'helpdesk');
    }

    public function test_publish_sets_published_at_timestamp(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->draft()->create(['published_at' => null]);

        $this->actingAs($this->user)
            ->post(route('chatflow.publish', $flow));

        $flow->refresh();
        $this->assertNotNull($flow->published_at);
    }

    public function test_publish_requires_update_permission(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->draft()->create();
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo('chatflow.view');

        $this->actingAs($viewOnly)
            ->post(route('chatflow.publish', $flow))
            ->assertForbidden();
    }

    // ─── duplicate ─────────────────────────────────────────────────────────────

    public function test_duplicate_creates_copy_with_draft_status(): void
    {
        $this->requireHelpdeskDb();
        $original = ChatFlow::factory()->active()->create(['name' => 'Flow original']);

        $response = $this->actingAs($this->user)
            ->post(route('chatflow.duplicate', $original))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_chat_flows', [
            'name' => 'Flow original (copia)',
            'status' => 'draft',
        ], 'helpdesk');
    }

    public function test_duplicate_assigns_new_uid_to_copy(): void
    {
        $this->requireHelpdeskDb();
        $original = ChatFlow::factory()->active()->create();

        $this->actingAs($this->user)
            ->post(route('chatflow.duplicate', $original));

        $copy = ChatFlow::query()
            ->where('name', $original->name.' (copia)')
            ->first();

        $this->assertNotNull($copy);
        $this->assertNotEquals($original->uid, $copy->uid);
    }

    public function test_duplicate_requires_create_permission(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo('chatflow.view');

        $this->actingAs($viewOnly)
            ->post(route('chatflow.duplicate', $flow))
            ->assertForbidden();
    }

    // ─── sessions ──────────────────────────────────────────────────────────────

    public function test_sessions_returns_ok_for_chatflow(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();

        $this->actingAs($this->user)
            ->get(route('chatflow.sessions', $flow))
            ->assertOk();
    }

    public function test_sessions_requires_authentication(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();

        $this->get(route('chatflow.sessions', $flow))
            ->assertRedirect(route('auth.login'));
    }

    public function test_sessions_requires_view_permission(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->get(route('chatflow.sessions', $flow))
            ->assertForbidden();
    }

    // ─── storeFromTemplate ───────────────────────────────────────────────────────

    public function test_store_from_template_creates_draft_flow_with_nodes(): void
    {
        $this->requireHelpdeskDb();

        $this->actingAs($this->user)
            ->post(route('chatflow.store-template', 'faq_ai'))
            ->assertRedirect();

        $flow = ChatFlow::query()->where('name', 'FAQ con IA')->first();

        $this->assertNotNull($flow);
        $this->assertSame('draft', $flow->status);
        $this->assertNotEmpty($flow->nodes);
        $this->assertContains('ai_response', array_column($flow->nodes, 'type'));
    }

    public function test_store_from_template_rejects_unknown_template(): void
    {
        $this->requireHelpdeskDb();

        $this->actingAs($this->user)
            ->post(route('chatflow.store-template', 'no_such_template'))
            ->assertRedirect(route('chatflow.index'));

        $this->assertDatabaseMissing('helpdesk_chat_flows', ['name' => 'no_such_template'], 'helpdesk');
    }

    public function test_store_from_template_requires_create_permission(): void
    {
        $this->requireHelpdeskDb();
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo('chatflow.view');

        $this->actingAs($viewOnly)
            ->post(route('chatflow.store-template', 'faq_ai'))
            ->assertForbidden();
    }

    // ─── analytics ───────────────────────────────────────────────────────────────

    public function test_analytics_returns_ok_for_authorized_user(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();

        $this->actingAs($this->user)
            ->get(route('chatflow.analytics', $flow))
            ->assertOk()
            ->assertViewHas('summary')
            ->assertViewHas('dropOff');
    }

    public function test_analytics_requires_view_permission(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->get(route('chatflow.analytics', $flow))
            ->assertForbidden();
    }
}
