<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\RoutingRule;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoutingRulesControllerTest extends TestCase
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

    public function test_guest_cannot_access_routing_rules_index(): void
    {
        $this->get(route('settings.helpdesk.routing-rules.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.routing-rules.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_routing_rules_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.routing-rules.index'))
            ->assertOk();
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_manager_can_view_create_form(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.routing-rules.create'))
            ->assertOk();
    }

    public function test_user_without_permission_cannot_access_create_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.routing-rules.create'))
            ->assertForbidden();
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_manager_can_create_routing_rule(): void
    {
        $payload = [
            'keyword' => 'soporte urgente',
            'match_type' => 'contains',
            'priority' => 10,
            'is_active' => '1',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.routing-rules.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.routing-rules.index'));

        $rule = RoutingRule::query()->where('keyword', 'soporte urgente')->first();

        $this->assertNotNull($rule, 'La regla de enrutamiento debe haberse creado.');
        $this->assertEquals('contains', $rule->match_type);
        $this->assertEquals(10, $rule->priority);
        $this->assertTrue($rule->is_active);
    }

    public function test_store_fails_without_required_fields(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.routing-rules.store'), [])
            ->assertSessionHasErrors(['keyword', 'match_type']);
    }

    public function test_store_fails_with_invalid_match_type(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.routing-rules.store'), [
                'keyword' => 'test',
                'match_type' => 'invalid_type',
            ])
            ->assertSessionHasErrors(['match_type']);
    }

    public function test_store_fails_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.routing-rules.store'), [
                'keyword' => 'test',
                'match_type' => 'contains',
            ])
            ->assertForbidden();
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_manager_can_update_routing_rule(): void
    {
        $rule = RoutingRule::create([
            'keyword' => 'original',
            'match_type' => 'contains',
            'is_active' => true,
        ]);

        $payload = [
            'keyword' => 'actualizado',
            'match_type' => 'exact',
            'priority' => 5,
        ];

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.routing-rules.update', $rule), $payload)
            ->assertRedirect(route('settings.helpdesk.routing-rules.index'));

        $rule->refresh();
        $this->assertEquals('actualizado', $rule->keyword);
        $this->assertEquals('exact', $rule->match_type);
        $this->assertEquals(5, $rule->priority);
    }

    public function test_update_fails_for_user_without_permission(): void
    {
        $rule = RoutingRule::create([
            'keyword' => 'protegido',
            'match_type' => 'contains',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.routing-rules.update', $rule), [
                'keyword' => 'modificado',
                'match_type' => 'exact',
            ])
            ->assertForbidden();
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_manager_can_delete_routing_rule(): void
    {
        $rule = RoutingRule::create([
            'keyword' => 'a eliminar',
            'match_type' => 'contains',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.routing-rules.destroy', $rule))
            ->assertRedirect(route('settings.helpdesk.routing-rules.index'));

        $this->assertDatabaseMissing('helpdesk_routing_rules', ['id' => $rule->id], 'helpdesk');
    }

    public function test_destroy_fails_for_user_without_permission(): void
    {
        $rule = RoutingRule::create([
            'keyword' => 'no eliminar',
            'match_type' => 'contains',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.routing-rules.destroy', $rule))
            ->assertForbidden();
    }

    // ─── toggle ───────────────────────────────────────────────────────────────

    public function test_manager_can_toggle_routing_rule_active_status(): void
    {
        $rule = RoutingRule::create([
            'keyword' => 'toggle test',
            'match_type' => 'contains',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.routing-rules.toggle', $rule))
            ->assertOk()
            ->assertJson(['success' => true, 'is_active' => false]);

        $this->assertFalse($rule->fresh()->is_active);
    }

    public function test_toggle_deactivated_rule_activates_it(): void
    {
        $rule = RoutingRule::create([
            'keyword' => 'inactive rule',
            'match_type' => 'exact',
            'is_active' => false,
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.routing-rules.toggle', $rule))
            ->assertOk()
            ->assertJson(['success' => true, 'is_active' => true]);

        $this->assertTrue($rule->fresh()->is_active);
    }

    public function test_toggle_fails_for_user_without_permission(): void
    {
        $rule = RoutingRule::create([
            'keyword' => 'toggle sin permiso',
            'match_type' => 'contains',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.routing-rules.toggle', $rule))
            ->assertForbidden();
    }
}
