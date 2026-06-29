<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\ConversationStatus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * T0.8 regression: statuses did not persist is_open / is_closed.
 *
 * Bugs fixed:
 *  - StoreConversationStatusRequest and UpdateConversationStatusRequest were
 *    missing the 'is_open' validation rule, so the value was stripped before
 *    reaching the controller.
 *  - ConversationStatus::$fillable was missing 'is_closed', so even when the
 *    column was populated it was silently dropped on mass-assignment.
 *  - StatusesController had no toggle() method, but the route {status}/toggle
 *    pointed to it — any call would throw a MethodNotAllowedException.
 */
class StatusesControllerTest extends TestCase
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

    public function test_guest_cannot_access_statuses_index(): void
    {
        $this->get(route('settings.helpdesk.statuses.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.statuses.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_statuses_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.statuses.index'))
            ->assertOk();
    }

    // ── store — is_open persisted ────────────────────────────────────────────

    public function test_store_persists_is_open_true_and_is_closed_false(): void
    {
        $payload = [
            'name' => 'En progreso',
            'slug' => 'en-progreso',
            'color' => '#90bb13',
            'is_open' => '1',
            'is_default' => '0',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.statuses.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.statuses.index'));

        $status = ConversationStatus::query()->where('slug', 'en-progreso')->first();

        $this->assertNotNull($status);
        $this->assertTrue($status->is_open, 'is_open debe ser true cuando se envía is_open=1');
        $this->assertFalse($status->is_closed, 'is_closed debe ser false cuando is_open=1');
    }

    public function test_store_persists_is_open_false_and_is_closed_true(): void
    {
        $payload = [
            'name' => 'Resuelto',
            'slug' => 'resuelto',
            'color' => '#FA896B',
            'is_open' => '0',
            'is_default' => '0',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.statuses.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.statuses.index'));

        $status = ConversationStatus::query()->where('slug', 'resuelto')->first();

        $this->assertNotNull($status);
        $this->assertFalse($status->is_open, 'is_open debe ser false cuando se envía is_open=0');
        $this->assertTrue($status->is_closed, 'is_closed debe ser true cuando is_open=0');
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.statuses.store'), [
                'slug' => 'sin-nombre',
                'color' => '#90bb13',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_fails_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.statuses.store'), [
                'name' => 'Sin permiso',
                'slug' => 'sin-permiso',
                'color' => '#90bb13',
            ])
            ->assertForbidden();
    }

    // ── update — is_open persisted ───────────────────────────────────────────

    public function test_update_persists_is_open_change_to_closed(): void
    {
        $status = ConversationStatus::create([
            'name' => 'Pendiente',
            'slug' => 'pendiente-upd',
            'color' => '#FEC90F',
            'is_open' => true,
            'is_closed' => false,
            'is_system' => false,
        ]);

        $payload = [
            'name' => 'Pendiente',
            'slug' => 'pendiente-upd',
            'color' => '#FEC90F',
            'is_open' => '0',
            'is_default' => '0',
            'active' => '1',
        ];

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.statuses.update', $status), $payload)
            ->assertRedirect(route('settings.helpdesk.statuses.index'));

        $status->refresh();

        $this->assertFalse($status->is_open, 'is_open debe cambiar a false tras la actualización');
        $this->assertTrue($status->is_closed, 'is_closed debe cambiar a true tras la actualización');
    }

    public function test_update_persists_is_open_change_to_open(): void
    {
        $status = ConversationStatus::create([
            'name' => 'Cerrado prev',
            'slug' => 'cerrado-prev',
            'color' => '#FA896B',
            'is_open' => false,
            'is_closed' => true,
            'is_system' => false,
        ]);

        $payload = [
            'name' => 'Cerrado prev',
            'slug' => 'cerrado-prev',
            'color' => '#FA896B',
            'is_open' => '1',
            'is_default' => '0',
            'active' => '1',
        ];

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.statuses.update', $status), $payload)
            ->assertRedirect(route('settings.helpdesk.statuses.index'));

        $status->refresh();

        $this->assertTrue($status->is_open, 'is_open debe cambiar a true tras la actualización');
        $this->assertFalse($status->is_closed, 'is_closed debe cambiar a false tras la actualización');
    }

    public function test_update_fails_for_user_without_permission(): void
    {
        $status = ConversationStatus::create([
            'name' => 'Protegido',
            'slug' => 'protegido',
            'color' => '#539BFF',
            'is_system' => false,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.statuses.update', $status), [
                'name' => 'Cambio no autorizado',
                'slug' => 'protegido',
                'color' => '#539BFF',
            ])
            ->assertForbidden();
    }

    // ── toggle — método ahora existe ──────────────────────────────────────────

    public function test_toggle_flips_active_flag(): void
    {
        $status = ConversationStatus::create([
            'name' => 'Activo toggle',
            'slug' => 'activo-toggle',
            'color' => '#13C672',
            'is_system' => false,
            'active' => true,
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.statuses.toggle', $status))
            ->assertRedirect();

        $this->assertFalse($status->fresh()->active, 'active debe cambiar a false tras toggle');

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.statuses.toggle', $status))
            ->assertRedirect();

        $this->assertTrue($status->fresh()->active, 'active debe volver a true tras segundo toggle');
    }

    public function test_toggle_fails_for_user_without_permission(): void
    {
        $status = ConversationStatus::create([
            'name' => 'Toggle sin permiso',
            'slug' => 'toggle-sin-permiso',
            'color' => '#95A5A6',
            'is_system' => false,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.statuses.toggle', $status))
            ->assertForbidden();
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_manager_can_delete_non_system_status(): void
    {
        $status = ConversationStatus::create([
            'name' => 'Eliminar este',
            'slug' => 'eliminar-este',
            'color' => '#E74C3C',
            'is_system' => false,
            'is_default' => false,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.statuses.destroy', $status))
            ->assertRedirect(route('settings.helpdesk.statuses.index'));

        $this->assertNull(ConversationStatus::find($status->id));
    }

    public function test_cannot_delete_system_status(): void
    {
        $status = ConversationStatus::create([
            'name' => 'Sistema',
            'slug' => 'sistema',
            'color' => '#333333',
            'is_system' => true,
            'is_default' => false,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.statuses.destroy', $status))
            ->assertRedirect();

        $this->assertNotNull(ConversationStatus::find($status->id), 'El estado del sistema no debe eliminarse');
    }
}
