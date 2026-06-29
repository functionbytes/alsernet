<?php

namespace Modules\Role\Tests\Feature;

use App\Models\User;
use Modules\Role\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementTest extends TestCase
{
    // -------------------------------------------------------------------------
    // GET /settings/roles — index
    // -------------------------------------------------------------------------

    public function test_role_list_requires_auth(): void
    {
        $this->get(route('settings.roles.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_can_view_roles_list(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('settings.roles.index'))
            ->assertOk()
            ->assertViewIs('role::roles.index');
    }

    // -------------------------------------------------------------------------
    // POST /settings/roles — store
    // -------------------------------------------------------------------------

    public function test_can_create_role(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->postJson(route('settings.roles.store'), [
                'name' => 'rol_nuevo_prueba',
                'guard_name' => 'web',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('roles', ['name' => 'rol_nuevo_prueba']);
    }

    public function test_role_name_must_be_unique(): void
    {
        $user = $this->createAdminUser();
        $existing = $this->createRole(['name' => 'rol_existente']);

        $this->actingAs($user)
            ->postJson(route('settings.roles.store'), [
                'name' => 'rol_existente',
                'guard_name' => 'web',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // POST /settings/roles/{role}/permissions — toggle permission
    // -------------------------------------------------------------------------

    public function test_can_assign_permission_to_role(): void
    {
        $user = $this->createAdminUser();
        $role = $this->createRole();
        $permission = Permission::firstOrCreate(['name' => 'test.permiso.toggle', 'guard_name' => 'web']);

        $this->actingAs($user)
            ->postJson(route('settings.roles.update.permissions', $role->id), [
                'permission_id' => $permission->id,
                'action' => 'attach',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($role->fresh()->hasPermissionTo('test.permiso.toggle'));
    }

    public function test_can_sync_permissions_to_role(): void
    {
        $user = $this->createAdminUser();
        $role = $this->createRole();
        $permission = Permission::firstOrCreate(['name' => 'test.permiso.sync', 'guard_name' => 'web']);

        $this->actingAs($user)
            ->postJson(route('settings.roles.update.permissions', $role->id), [
                'permissions' => [$permission->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($role->fresh()->hasPermissionTo('test.permiso.sync'));
    }

    // -------------------------------------------------------------------------
    // POST /settings/roles/clone/{role} — clone (web redirect)
    // -------------------------------------------------------------------------

    public function test_can_clone_role(): void
    {
        $user = $this->createAdminUser();
        $original = $this->createRole(['name' => 'rol_original_para_clonar']);
        $roleCountBefore = Role::count();

        $this->actingAs($user)
            ->post(route('settings.roles.clone', $original->id))
            ->assertRedirect();

        // A new role should have been created (total count increased)
        $this->assertGreaterThan($roleCountBefore, Role::count());

        // The clone should contain the original name with "_copia_" suffix
        $clone = Role::where('name', 'like', 'rol_original_para_clonar_copia_%')->first();
        $this->assertNotNull($clone);
    }

    public function test_cloned_role_has_all_original_permissions(): void
    {
        $user = $this->createAdminUser();
        $permission = Permission::firstOrCreate(['name' => 'test.permiso.clon', 'guard_name' => 'web']);
        $original = $this->createRole(['name' => 'rol_con_permisos_clonar']);
        $original->givePermissionTo($permission);

        $this->actingAs($user)
            ->post(route('settings.roles.clone', $original->id));

        $clone = Role::where('name', 'like', 'rol_con_permisos_clonar_copia_%')->first();

        $this->assertNotNull($clone);
        $this->assertTrue($clone->hasPermissionTo('test.permiso.clon'));
    }

    // -------------------------------------------------------------------------
    // DELETE /settings/roles/{role} — destroy / protected roles
    // -------------------------------------------------------------------------

    public function test_system_role_cannot_be_deleted(): void
    {
        $user = $this->createAdminUser();

        // 'super-settings' and 'customer' are protected per role.php config
        $protectedRole = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->actingAs($user)
            ->deleteJson(route('settings.roles.destroy', $protectedRole->id))
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('roles', ['name' => 'super-settings']);
    }

    public function test_non_protected_role_can_be_deleted(): void
    {
        $user = $this->createAdminUser();
        $role = $this->createRole(['name' => 'rol_para_eliminar']);

        $this->actingAs($user)
            ->deleteJson(route('settings.roles.destroy', $role->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('roles', ['name' => 'rol_para_eliminar']);
    }

    // -------------------------------------------------------------------------
    // POST /settings/roles/{role}/users — assign user to role
    // -------------------------------------------------------------------------

    public function test_can_assign_user_to_role(): void
    {
        $user = $this->createAdminUser();
        $role = $this->createRole();
        $targetUser = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('settings.roles.assign.users', $role->id), [
                'user_ids' => [$targetUser->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($targetUser->fresh()->hasRole($role->name));
    }

    // -------------------------------------------------------------------------
    // GET /settings/roles/matrix — permission matrix
    // -------------------------------------------------------------------------

    public function test_permission_matrix_returns_correct_structure(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)
            ->get(route('settings.roles.matrix'))
            ->assertOk()
            ->assertViewIs('role::roles.matrix');

        $response->assertViewHasAll(['roles', 'permissions', 'permissionsByModule', 'rolePermissions']);
    }

    // -------------------------------------------------------------------------
    // POST /settings/roles/{role}/duplicate
    // -------------------------------------------------------------------------

    public function test_can_duplicate_role(): void
    {
        $user = $this->createAdminUser();
        $perm = Permission::firstOrCreate(['name' => 'test.duplicate.perm', 'guard_name' => 'web']);
        $original = $this->createRole(['name' => 'rol_a_duplicar']);
        $original->givePermissionTo($perm);

        $this->actingAs($user)
            ->postJson(route('settings.roles.duplicate', $original->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $copy = Role::where('name', $original->name.' (Copy)')->first();
        $this->assertNotNull($copy);
        $this->assertTrue($copy->hasPermissionTo('test.duplicate.perm'));
    }

    // -------------------------------------------------------------------------
    // GET /settings/roles/{role}/export
    // -------------------------------------------------------------------------

    public function test_can_export_role(): void
    {
        $user = $this->createAdminUser();
        $perm = Permission::firstOrCreate(['name' => 'test.export.perm', 'guard_name' => 'web']);
        $role = $this->createRole(['name' => 'rol_exportar']);
        $role->givePermissionTo($perm);

        $response = $this->actingAs($user)
            ->getJson(route('settings.roles.export', $role->id))
            ->assertOk();

        $data = $response->json();
        $this->assertEquals('rol_exportar', $data['name']);
        $this->assertContains('test.export.perm', $data['permissions']);
        $this->assertArrayHasKey('exported_at', $data);
    }

    // -------------------------------------------------------------------------
    // POST /settings/roles/{role}/copy-from
    // -------------------------------------------------------------------------

    public function test_can_copy_permissions_from_another_role(): void
    {
        $user = $this->createAdminUser();
        $perm = Permission::firstOrCreate(['name' => 'test.copyfrom.perm', 'guard_name' => 'web']);
        $source = $this->createRole(['name' => 'rol_origen']);
        $source->givePermissionTo($perm);
        $target = $this->createRole(['name' => 'rol_destino']);

        $this->actingAs($user)
            ->postJson(route('settings.roles.copy-from', $target->id), [
                'source_role_id' => $source->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($target->fresh()->hasPermissionTo('test.copyfrom.perm'));
    }

    public function test_copy_permissions_requires_different_role(): void
    {
        $user = $this->createAdminUser();
        $role = $this->createRole(['name' => 'rol_mismo_origen']);

        $this->actingAs($user)
            ->postJson(route('settings.roles.copy-from', $role->id), [
                'source_role_id' => $role->id,
            ])
            ->assertUnprocessable();
    }

    // -------------------------------------------------------------------------
    // GET /settings/roles/compare
    // -------------------------------------------------------------------------

    public function test_compare_returns_view_without_roles(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('settings.roles.compare'))
            ->assertOk()
            ->assertViewIs('role::roles.compare')
            ->assertViewHasAll(['roles', 'roleA', 'roleB', 'onlyInA', 'onlyInB', 'inBoth']);
    }

    public function test_compare_shows_permission_diff(): void
    {
        $user = $this->createAdminUser();
        $permA = Permission::firstOrCreate(['name' => 'test.compare.only_a', 'guard_name' => 'web']);
        $permB = Permission::firstOrCreate(['name' => 'test.compare.only_b', 'guard_name' => 'web']);
        $permBoth = Permission::firstOrCreate(['name' => 'test.compare.shared', 'guard_name' => 'web']);

        $roleA = $this->createRole(['name' => 'rol_compare_a']);
        $roleA->givePermissionTo([$permA, $permBoth]);
        $roleB = $this->createRole(['name' => 'rol_compare_b']);
        $roleB->givePermissionTo([$permB, $permBoth]);

        $response = $this->actingAs($user)
            ->get(route('settings.roles.compare', ['role_a' => $roleA->id, 'role_b' => $roleB->id]))
            ->assertOk();

        $this->assertTrue($response->viewData('onlyInA')->contains('test.compare.only_a'));
        $this->assertTrue($response->viewData('onlyInB')->contains('test.compare.only_b'));
        $this->assertTrue($response->viewData('inBoth')->contains('test.compare.shared'));
    }

    // -------------------------------------------------------------------------
    // GET /settings/roles/modules/{role}
    // -------------------------------------------------------------------------

    public function test_show_modules_returns_view(): void
    {
        $user = $this->createAdminUser();
        $role = $this->createRole();

        $this->actingAs($user)
            ->get(route('settings.roles.show.modules', $role->id))
            ->assertOk()
            ->assertViewIs('role::roles.modules')
            ->assertViewHasAll(['role', 'modules', 'roleModules']);
    }

    // -------------------------------------------------------------------------
    // POST /settings/roles/{role}/modules
    // -------------------------------------------------------------------------

    public function test_can_update_role_modules(): void
    {
        $user = $this->createAdminUser();
        $role = $this->createRole();
        Permission::firstOrCreate(['name' => 'modules.view.documents', 'guard_name' => 'web']);

        $this->actingAs($user)
            ->postJson(route('settings.roles.update.modules', $role->id), [
                'modules' => ['documents'],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($role->fresh()->hasPermissionTo('modules.view.documents'));
    }

    public function test_update_modules_with_empty_array_removes_all_module_permissions(): void
    {
        $user = $this->createAdminUser();
        $role = $this->createRole();
        $perm = Permission::firstOrCreate(['name' => 'modules.view.media', 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        $this->actingAs($user)
            ->postJson(route('settings.roles.update.modules', $role->id), [
                'modules' => [],
            ])
            ->assertOk();

        $this->assertFalse($role->fresh()->hasPermissionTo('modules.view.media'));
    }
}
