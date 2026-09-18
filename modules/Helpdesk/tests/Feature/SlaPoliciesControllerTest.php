<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\SlaPolicy;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SlaPoliciesControllerTest extends TestCase
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

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_sla_policies_index(): void
    {
        $this->get(route('settings.helpdesk.sla-policies.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.sla-policies.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_sla_policies_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.sla-policies.index'))
            ->assertOk();
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_manager_can_view_create_form(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.sla-policies.create'))
            ->assertOk();
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_store_creates_sla_policy_with_uid_auto_generated(): void
    {
        $payload = [
            'name' => 'SLA Prioritario',
            'description' => 'Politica de alta prioridad',
            'first_response_time_hours' => 1,
            'resolution_time_hours' => 8,
            'warning_threshold_percent' => 80,
            'is_active' => '1',
            'business_hours_only' => '0',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.sla-policies.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.sla-policies.index'));

        $policy = SlaPolicy::query()
            ->where('name', 'SLA Prioritario')
            ->first();

        $this->assertNotNull($policy, 'La politica SLA debe haberse creado.');
        $this->assertNotNull($policy->uid, 'El uid debe generarse automaticamente.');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $policy->uid,
            'El uid debe ser un UUID valido.'
        );
        $this->assertEquals(1, $policy->first_response_time_hours);
        $this->assertEquals(8, $policy->resolution_time_hours);
    }

    public function test_store_fails_validation_with_old_minutes_field_names(): void
    {
        // Este test verifica que los campos incorrectos (del bug original)
        // no pasan validacion cuando se usan en lugar de los campos correctos.
        $payload = [
            'name' => 'SLA con campos erroneos',
            'first_response_minutes' => 60,
            'resolution_minutes' => 480,
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.sla-policies.store'), $payload)
            ->assertSessionHasErrors(['first_response_time_hours', 'resolution_time_hours']);
    }

    public function test_store_fails_without_required_time_fields(): void
    {
        $payload = [
            'name' => 'SLA incompleto',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.sla-policies.store'), $payload)
            ->assertSessionHasErrors(['first_response_time_hours', 'resolution_time_hours']);
    }

    public function test_store_fails_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.sla-policies.store'), [
                'name' => 'SLA sin permiso',
                'first_response_time_hours' => 1,
                'resolution_time_hours' => 8,
            ])
            ->assertForbidden();
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_manager_can_update_sla_policy(): void
    {
        $policy = SlaPolicy::create([
            'name' => 'SLA Original',
            'first_response_time_hours' => 2,
            'resolution_time_hours' => 16,
        ]);

        $payload = [
            'name' => 'SLA Actualizado',
            'first_response_time_hours' => 4,
            'resolution_time_hours' => 24,
            'is_active' => '1',
            'business_hours_only' => '0',
        ];

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.sla-policies.update', $policy), $payload)
            ->assertRedirect(route('settings.helpdesk.sla-policies.index'));

        $policy->refresh();
        $this->assertEquals('SLA Actualizado', $policy->name);
        $this->assertEquals(4, $policy->first_response_time_hours);
        $this->assertEquals(24, $policy->resolution_time_hours);
    }

    public function test_update_fails_for_user_without_permission(): void
    {
        $policy = SlaPolicy::create([
            'name' => 'SLA Protegido',
            'first_response_time_hours' => 1,
            'resolution_time_hours' => 8,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.sla-policies.update', $policy), [
                'name' => 'Cambio no autorizado',
                'first_response_time_hours' => 1,
                'resolution_time_hours' => 8,
            ])
            ->assertForbidden();
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_manager_can_delete_sla_policy(): void
    {
        $policy = SlaPolicy::create([
            'name' => 'SLA a eliminar',
            'first_response_time_hours' => 1,
            'resolution_time_hours' => 8,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.sla-policies.destroy', $policy))
            ->assertRedirect(route('settings.helpdesk.sla-policies.index'));

        $this->assertSoftDeleted('helpdesk_sla_policies', ['id' => $policy->id], 'helpdesk');
    }

    public function test_destroy_fails_for_user_without_permission(): void
    {
        $policy = SlaPolicy::create([
            'name' => 'SLA protegido',
            'first_response_time_hours' => 1,
            'resolution_time_hours' => 8,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.sla-policies.destroy', $policy))
            ->assertForbidden();
    }

    // ─── toggle ───────────────────────────────────────────────────────────────

    public function test_manager_can_toggle_sla_policy_status(): void
    {
        $policy = SlaPolicy::create([
            'name' => 'SLA toggle',
            'first_response_time_hours' => 1,
            'resolution_time_hours' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.sla-policies.toggle', $policy))
            ->assertOk()
            ->assertJson(['success' => true, 'is_active' => false]);

        $this->assertFalse($policy->fresh()->is_active);
    }
}
