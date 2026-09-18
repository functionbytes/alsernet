<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\CustomField;
use Modules\Helpdesk\Models\Skill;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verifies that the delete modals in custom-fields/index.blade.php
 * and skills/index.blade.php use the correct IDs from core::components.delete
 * (#delete-modal / #delete-form) and NOT the old broken IDs
 * (#deleteModal / #deleteForm).
 *
 * Also tests the DELETE HTTP endpoints for authorization.
 */
class DeleteModalsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    private User $unauthorized;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        $this->unauthorized = User::factory()->create();
    }

    // ─── Custom Fields index: correct modal IDs ───────────────────────────────

    public function test_custom_fields_index_renders_correct_delete_modal_id(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.custom-fields.index'));

        $response->assertOk();
        $response->assertSee('id="delete-modal"', false);
        $response->assertSee('id="delete-form"', false);
    }

    public function test_custom_fields_index_does_not_use_old_modal_ids(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.custom-fields.index'));

        $response->assertOk();
        $response->assertDontSee('deleteModal', false);
        $response->assertDontSee('deleteForm', false);
        $response->assertDontSee('deleteItemName', false);
    }

    public function test_custom_fields_index_uses_delete_btn_class_and_correct_data_attributes(): void
    {
        CustomField::create([
            'entity_type' => 'customer',
            'key' => 'cf_test_key',
            'label' => 'Campo de prueba',
            'type' => 'text',
            'is_required' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.custom-fields.index'));

        $response->assertOk();
        $response->assertSee('delete-btn', false);
        $response->assertSee('data-bs-target="#delete-modal"', false);
        $response->assertDontSee('btn-delete', false);
    }

    // ─── Skills index: correct modal IDs ─────────────────────────────────────

    public function test_skills_index_renders_correct_delete_modal_id(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.skills.index'));

        $response->assertOk();
        $response->assertSee('id="delete-modal"', false);
        $response->assertSee('id="delete-form"', false);
    }

    public function test_skills_index_does_not_use_old_modal_ids(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.skills.index'));

        $response->assertOk();
        $response->assertDontSee('deleteModal', false);
        $response->assertDontSee('deleteForm', false);
        $response->assertDontSee('deleteItemName', false);
    }

    public function test_skills_index_uses_delete_btn_class_and_correct_data_attributes(): void
    {
        Skill::create([
            'name' => 'Skill de prueba',
            'slug' => 'skill-de-prueba',
            'description' => 'Descripcion de prueba',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.skills.index'));

        $response->assertOk();
        $response->assertSee('delete-btn', false);
        $response->assertSee('data-bs-target="#delete-modal"', false);
        $response->assertDontSee('btn-delete', false);
    }

    // ─── Custom Fields: DELETE endpoint authorization ─────────────────────────

    public function test_manager_can_delete_custom_field(): void
    {
        $field = CustomField::create([
            'entity_type' => 'customer',
            'key' => 'cf_to_delete',
            'label' => 'Campo a eliminar',
            'type' => 'text',
            'is_required' => false,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.custom-fields.destroy', $field))
            ->assertRedirect(route('settings.helpdesk.custom-fields.index'));

        $this->assertDatabaseMissing('helpdesk_custom_fields', ['id' => $field->id], 'helpdesk');
    }

    public function test_unauthorized_user_cannot_delete_custom_field(): void
    {
        $field = CustomField::create([
            'entity_type' => 'customer',
            'key' => 'cf_protected',
            'label' => 'Campo protegido',
            'type' => 'text',
            'is_required' => false,
        ]);

        $this->actingAs($this->unauthorized)
            ->delete(route('settings.helpdesk.custom-fields.destroy', $field))
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_custom_fields', ['id' => $field->id], 'helpdesk');
    }

    public function test_guest_cannot_delete_custom_field(): void
    {
        $field = CustomField::create([
            'entity_type' => 'customer',
            'key' => 'cf_guest_test',
            'label' => 'Campo guest',
            'type' => 'text',
            'is_required' => false,
        ]);

        $this->delete(route('settings.helpdesk.custom-fields.destroy', $field))
            ->assertRedirect(route('auth.login'));
    }

    // ─── Skills: DELETE endpoint authorization ────────────────────────────────

    public function test_manager_can_delete_skill(): void
    {
        $skill = Skill::create([
            'name' => 'Skill a eliminar',
            'slug' => 'skill-a-eliminar',
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.skills.destroy', $skill))
            ->assertRedirect(route('settings.helpdesk.skills.index'));

        $this->assertDatabaseMissing('helpdesk_skills', ['id' => $skill->id], 'helpdesk');
    }

    public function test_unauthorized_user_cannot_delete_skill(): void
    {
        $skill = Skill::create([
            'name' => 'Skill protegido',
            'slug' => 'skill-protegido',
        ]);

        $this->actingAs($this->unauthorized)
            ->delete(route('settings.helpdesk.skills.destroy', $skill))
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_skills', ['id' => $skill->id], 'helpdesk');
    }

    public function test_guest_cannot_delete_skill(): void
    {
        $skill = Skill::create([
            'name' => 'Skill guest',
            'slug' => 'skill-guest-test',
        ]);

        $this->delete(route('settings.helpdesk.skills.destroy', $skill))
            ->assertRedirect(route('auth.login'));
    }
}
