<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Macro;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MacrosControllerTest extends TestCase
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

    // ─── index ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_macros_index(): void
    {
        $this->get(route('settings.helpdesk.macros.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.macros.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_macros_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.macros.index'))
            ->assertOk();
    }

    // ─── store: visibility → is_shared mapping ─────────────────────────────────

    public function test_store_with_global_visibility_sets_is_shared_true(): void
    {
        $payload = [
            'name' => 'Macro Global Test',
            'description' => 'Test macro',
            'actions' => [
                ['type' => 'change_status', 'value' => 'resolved'],
            ],
            'visibility' => 'global',
            'is_active' => '1',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.macros.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.macros.index'));

        $macro = Macro::query()->where('name', 'Macro Global Test')->first();

        $this->assertNotNull($macro);
        $this->assertTrue((bool) $macro->is_shared, 'visibility=global debe guardar is_shared=true');
        $this->assertNull($macro->user_id, 'Un macro global no debe tener user_id');
    }

    public function test_store_with_personal_visibility_sets_is_shared_false(): void
    {
        $payload = [
            'name' => 'Macro Personal Test',
            'actions' => [
                ['type' => 'change_priority', 'value' => 'high'],
            ],
            'visibility' => 'personal',
            'is_active' => '1',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.macros.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.macros.index'));

        $macro = Macro::query()->where('name', 'Macro Personal Test')->first();

        $this->assertNotNull($macro);
        $this->assertFalse((bool) $macro->is_shared, 'visibility=personal debe guardar is_shared=false');
        $this->assertEquals($this->manager->id, $macro->user_id, 'Un macro personal debe tener el user_id del creador');
    }

    // ─── store: validation ─────────────────────────────────────────────────────

    public function test_store_fails_without_required_fields(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.macros.store'), [])
            ->assertSessionHasErrors(['name', 'actions', 'visibility']);
    }

    public function test_store_fails_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.macros.store'), [
                'name' => 'No autorizado',
                'actions' => [['type' => 'change_status', 'value' => 'open']],
                'visibility' => 'global',
            ])
            ->assertForbidden();
    }

    // ─── edit: existingActions pre-loaded ──────────────────────────────────────

    public function test_edit_passes_existing_actions_to_view(): void
    {
        $macro = Macro::factory()->create([
            'name' => 'Macro con acciones',
            'actions' => [
                ['type' => 'change_status', 'value' => 'resolved'],
                ['type' => 'change_priority', 'value' => 'high'],
            ],
            'is_shared' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.macros.edit', $macro));

        $response->assertOk();
        $response->assertViewHas('existingActions');

        $existingActions = $response->viewData('existingActions');
        $this->assertCount(2, $existingActions, 'El edit debe pasar las 2 acciones existentes');
        $this->assertEquals('change_status', $existingActions[0]['type']);
        $this->assertEquals('change_priority', $existingActions[1]['type']);
    }

    public function test_edit_passes_empty_existing_actions_for_macro_with_no_actions(): void
    {
        $macro = Macro::factory()->create([
            'actions' => [],
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.macros.edit', $macro));

        $response->assertOk();
        $this->assertIsArray($response->viewData('existingActions'));
    }

    // ─── update: visibility → is_shared mapping ────────────────────────────────

    public function test_update_changes_visibility_from_personal_to_global(): void
    {
        $macro = Macro::factory()->personal($this->manager->id)->create([
            'name' => 'Macro a cambiar',
        ]);

        $this->assertFalse((bool) $macro->is_shared);

        $payload = [
            'name' => 'Macro a cambiar',
            'actions' => [['type' => 'change_status', 'value' => 'resolved']],
            'visibility' => 'global',
        ];

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.macros.update', $macro), $payload)
            ->assertRedirect(route('settings.helpdesk.macros.index'));

        $macro->refresh();
        $this->assertTrue((bool) $macro->is_shared, 'Cambiar visibility a global debe poner is_shared=true');
        $this->assertNull($macro->user_id);
    }

    public function test_update_changes_visibility_from_global_to_personal(): void
    {
        $macro = Macro::factory()->create([
            'name' => 'Macro global',
            'is_shared' => true,
            'user_id' => null,
        ]);

        $payload = [
            'name' => 'Macro global',
            'actions' => [['type' => 'change_status', 'value' => 'open']],
            'visibility' => 'personal',
        ];

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.macros.update', $macro), $payload)
            ->assertRedirect(route('settings.helpdesk.macros.index'));

        $macro->refresh();
        $this->assertFalse((bool) $macro->is_shared, 'Cambiar visibility a personal debe poner is_shared=false');
        $this->assertNotNull($macro->user_id);
    }

    public function test_update_preserves_usage_count(): void
    {
        $macro = Macro::factory()->withUsage()->create([
            'name' => 'Macro con uso',
            'is_shared' => true,
        ]);

        $originalUsageCount = $macro->usage_count;
        $this->assertGreaterThan(0, $originalUsageCount);

        $payload = [
            'name' => 'Macro con uso actualizado',
            'actions' => [['type' => 'change_status', 'value' => 'resolved']],
            'visibility' => 'global',
        ];

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.macros.update', $macro), $payload)
            ->assertRedirect(route('settings.helpdesk.macros.index'));

        $macro->refresh();
        $this->assertEquals($originalUsageCount, $macro->usage_count, 'El update no debe alterar usage_count');
    }

    public function test_update_fails_for_user_without_permission(): void
    {
        $macro = Macro::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.macros.update', $macro), [
                'name' => 'No autorizado',
                'actions' => [['type' => 'change_status', 'value' => 'open']],
                'visibility' => 'global',
            ])
            ->assertForbidden();
    }

    // ─── destroy ───────────────────────────────────────────────────────────────

    public function test_manager_can_delete_macro(): void
    {
        $macro = Macro::factory()->create(['name' => 'Macro a eliminar']);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.macros.destroy', $macro))
            ->assertRedirect(route('settings.helpdesk.macros.index'));

        $this->assertSoftDeleted('helpdesk_macros', ['id' => $macro->id], 'helpdesk');
    }

    public function test_destroy_fails_for_user_without_permission(): void
    {
        $macro = Macro::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.macros.destroy', $macro))
            ->assertForbidden();
    }
}
