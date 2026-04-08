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
}
