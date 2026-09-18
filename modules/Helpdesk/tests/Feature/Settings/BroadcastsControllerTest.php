<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Campaigns\Broadcast;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * T0.24 regression: broadcasts channel filter used "widget" (ghost value) instead of
 * the valid "web" channel; store() forced status="draft" ignoring scheduled_at; delete
 * modal JS used camelCase IDs (#deleteModal, #deleteForm) instead of the kebab-case
 * IDs (#delete-modal, #delete-form) defined by core::components.delete.
 *
 * Bugs fixed:
 *  - StoreBroadcastRequest rejects channel="widget" (not in allowed list); the form
 *    now submits channel="web" so valid broadcasts can be created.
 *  - BroadcastsController::store() now sets status="scheduled" when scheduled_at is
 *    present; otherwise falls back to status="draft".
 */
class BroadcastsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_broadcasts_index(): void
    {
        $this->get(route('settings.helpdesk.broadcasts.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_broadcasts_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.broadcasts.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_broadcasts_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.broadcasts.index'))
            ->assertOk();
    }

    // ── channel filter: "widget" was a ghost value, real channel is "web" ─────

    public function test_store_rejects_widget_channel(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.broadcasts.store'), [
                'name' => 'Test widget',
                'channel' => 'widget',
                'template_type' => 'text',
                'body' => 'Hola desde widget fantasma',
            ])
            ->assertSessionHasErrors(['channel']);
    }

    public function test_store_accepts_web_channel(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.broadcasts.store'), [
                'name' => 'Test web',
                'channel' => 'web',
                'template_type' => 'text',
                'body' => 'Notificacion web',
            ])
            ->assertRedirect(route('settings.helpdesk.broadcasts.index'));

        $this->assertDatabaseHas('helpdesk_broadcasts', [
            'name' => 'Test web',
            'channel' => 'web',
            'status' => 'draft',
        ], 'helpdesk');
    }

    // ── store: scheduled_at → status="scheduled" ──────────────────────────────

    public function test_store_without_scheduled_at_creates_draft(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.broadcasts.store'), [
                'name' => 'Borrador inmediato',
                'channel' => 'email',
                'template_type' => 'text',
                'body' => 'Mensaje de prueba',
            ])
            ->assertRedirect(route('settings.helpdesk.broadcasts.index'));

        $this->assertDatabaseHas('helpdesk_broadcasts', [
            'name' => 'Borrador inmediato',
            'status' => 'draft',
            'scheduled_at' => null,
        ], 'helpdesk');
    }

    public function test_store_with_scheduled_at_creates_scheduled_broadcast(): void
    {
        $scheduledAt = now()->addDay()->format('Y-m-d\TH:i');

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.broadcasts.store'), [
                'name' => 'Programado futuro',
                'channel' => 'whatsapp',
                'template_type' => 'text',
                'body' => 'Mensaje programado',
                'scheduled_at' => $scheduledAt,
            ])
            ->assertRedirect(route('settings.helpdesk.broadcasts.index'));

        $broadcast = Broadcast::query()
            ->where('name', 'Programado futuro')
            ->first();

        $this->assertNotNull($broadcast, 'El broadcast debe existir en la base de datos');
        $this->assertSame('scheduled', $broadcast->status, 'El estado debe ser "scheduled" cuando se provee scheduled_at');
        $this->assertNotNull($broadcast->scheduled_at, 'scheduled_at debe guardarse');
    }

    public function test_store_fails_if_scheduled_at_is_in_the_past(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.broadcasts.store'), [
                'name' => 'Pasado invalido',
                'channel' => 'email',
                'template_type' => 'text',
                'body' => 'Mensaje',
                'scheduled_at' => now()->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors(['scheduled_at']);
    }

    // ── store validation: user without permission ─────────────────────────────

    public function test_store_fails_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.broadcasts.store'), [
                'name' => 'Sin permiso',
                'channel' => 'web',
                'template_type' => 'text',
                'body' => 'Mensaje',
            ])
            ->assertForbidden();
    }

    // ── destroy: only draft broadcasts can be deleted ─────────────────────────

    public function test_manager_can_delete_draft_broadcast(): void
    {
        $broadcast = Broadcast::create([
            'name' => 'Borrador eliminable',
            'channel' => 'web',
            'template_type' => 'text',
            'body' => 'Texto',
            'status' => 'draft',
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.broadcasts.destroy', $broadcast))
            ->assertRedirect(route('settings.helpdesk.broadcasts.index'));

        $this->assertDatabaseMissing('helpdesk_broadcasts', ['id' => $broadcast->id], 'helpdesk');
    }

    public function test_cannot_delete_sent_broadcast(): void
    {
        $broadcast = Broadcast::create([
            'name' => 'Ya enviado',
            'channel' => 'email',
            'template_type' => 'text',
            'body' => 'Enviado',
            'status' => 'sent',
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.broadcasts.destroy', $broadcast))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_broadcasts', ['id' => $broadcast->id], 'helpdesk');
    }

    public function test_user_without_permission_cannot_delete(): void
    {
        $broadcast = Broadcast::create([
            'name' => 'No borrable',
            'channel' => 'web',
            'template_type' => 'text',
            'body' => 'Texto',
            'status' => 'draft',
            'created_by' => $this->manager->id,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.broadcasts.destroy', $broadcast))
            ->assertForbidden();
    }
}
