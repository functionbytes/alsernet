<?php

namespace Modules\Role\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a role the 'settings' middleware accepts
        $this->managerRole = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        // Create an admin user with the manager role
        $this->admin = User::create([
            'firstname' => 'Test',
            'lastname'  => 'Admin',
            'email'     => 'admin@test.local',
            'password'  => Hash::make('password'),
        ]);

        $this->admin->assignRole($this->managerRole);
    }

    // -----------------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------------

    public function test_admin_can_view_roles_index(): void
    {
        Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.roles.index'));

        $response->assertOk()
            ->assertViewIs('role::roles.index')
            ->assertViewHas('roles');
    }

    public function test_index_filters_by_search_keyword(): void
    {
        Role::create(['name' => 'editor-unique', 'guard_name' => 'web']);
        Role::create(['name' => 'viewer-unique', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.roles.index', ['search' => 'editor']));

        $response->assertOk();
        $roles = $response->viewData('roles');
        $this->assertTrue($roles->contains('name', 'editor-unique'));
        $this->assertFalse($roles->contains('name', 'viewer-unique'));
    }

    // -----------------------------------------------------------------------
    // Create
    // -----------------------------------------------------------------------

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('settings.roles.create'));

        $response->assertOk()
            ->assertViewIs('role::roles.create');
    }

    // -----------------------------------------------------------------------
    // Store
    // -----------------------------------------------------------------------

    public function test_admin_can_create_a_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'name'        => 'new-role',
                'guard_name'  => 'web',
                'description' => 'A test role',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['name' => 'new-role', 'guard_name' => 'web']);
    }

    public function test_store_assigns_permissions_to_new_role(): void
    {
        $permission = Permission::create(['name' => 'roles.view', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'name'        => 'role-with-perm',
                'guard_name'  => 'web',
                'permissions' => [$permission->id],
            ]);

        $role = Role::where('name', 'role-with-perm')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('roles.view'));
    }

    public function test_store_fails_validation_when_name_is_missing(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'guard_name' => 'web',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_fails_validation_when_name_is_too_short(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'name'       => 'ab',
                'guard_name' => 'web',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_fails_validation_when_name_is_duplicate(): void
    {
        Role::create(['name' => 'existing-role', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'name'       => 'existing-role',
                'guard_name' => 'web',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_fails_validation_with_invalid_guard(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'name'       => 'valid-role',
                'guard_name' => 'invalid-guard',
            ]);

        $response->assertSessionHasErrors(['guard_name']);
    }

    // -----------------------------------------------------------------------
    // Edit
    // -----------------------------------------------------------------------

    public function test_admin_can_view_edit_form(): void
    {
        $role = Role::create(['name' => 'editable-role', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.roles.edit', $role));

        $response->assertOk()
            ->assertViewIs('role::roles.edit')
            ->assertViewHas('role', $role);
    }

    // -----------------------------------------------------------------------
    // Update
    // -----------------------------------------------------------------------

    public function test_admin_can_update_a_role(): void
    {
        $role = Role::create(['name' => 'old-name', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->put(route('settings.roles.update', $role), [
                'name'       => 'updated-name',
                'guard_name' => 'web',
            ]);

        $response->assertRedirect(route('settings.roles.edit', $role->id));
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'updated-name']);
    }

    public function test_update_syncs_permissions(): void
    {
        $role = Role::create(['name' => 'role-to-update', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'roles.edit', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->put(route('settings.roles.update', $role), [
                'name'        => 'role-to-update',
                'guard_name'  => 'web',
                'permissions' => [$permission->id],
            ]);

        $this->assertTrue($role->fresh()->hasPermissionTo('roles.edit'));
    }

    public function test_cannot_update_super_settings_role(): void
    {
        $systemRole = Role::create(['name' => 'super-settings', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->put(route('settings.roles.update', $systemRole), [
                'name'       => 'super-settings',
                'guard_name' => 'web',
            ]);

        // The controller returns error() which is a JSON 400 response
        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Cannot modify system roles']);
    }

    public function test_update_fails_validation_when_name_is_missing(): void
    {
        $role = Role::create(['name' => 'some-role', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->put(route('settings.roles.update', $role), [
                'guard_name' => 'web',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    // -----------------------------------------------------------------------
    // Destroy
    // -----------------------------------------------------------------------

    public function test_admin_can_delete_a_role(): void
    {
        $role = Role::create(['name' => 'deletable-role', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->delete(route('settings.roles.destroy', $role));

        $response->assertRedirect(route('settings.roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_super_settings_role(): void
    {
        $systemRole = Role::create(['name' => 'super-settings', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->delete(route('settings.roles.destroy', $systemRole));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    public function test_cannot_delete_customer_role(): void
    {
        $systemRole = Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->delete(route('settings.roles.destroy', $systemRole));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    public function test_cannot_delete_role_with_assigned_users(): void
    {
        $role = Role::create(['name' => 'role-with-users', 'guard_name' => 'web']);

        $user = User::create([
            'firstname' => 'Assigned',
            'lastname'  => 'User',
            'email'     => 'assigned@test.local',
            'password'  => Hash::make('password'),
        ]);
        $user->assignRole($role);

        $response = $this->actingAs($this->admin)
            ->delete(route('settings.roles.destroy', $role));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    // -----------------------------------------------------------------------
    // Authentication / Authorization
    // -----------------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected_to_login_on_index(): void
    {
        $response = $this->get(route('settings.roles.index'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_create(): void
    {
        $response = $this->get(route('settings.roles.create'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_cannot_store_role(): void
    {
        $response = $this->post(route('settings.roles.store'), [
            'name'       => 'unauthorized-role',
            'guard_name' => 'web',
        ]);

        $response->assertRedirect(route('auth.login'));
        $this->assertDatabaseMissing('roles', ['name' => 'unauthorized-role']);
    }

    public function test_user_without_settings_role_cannot_access_roles(): void
    {
        $plainRole = Role::create(['name' => 'plain-user', 'guard_name' => 'web']);

        $plainUser = User::create([
            'firstname' => 'Plain',
            'lastname'  => 'User',
            'email'     => 'plain@test.local',
            'password'  => Hash::make('password'),
        ]);
        $plainUser->assignRole($plainRole);

        $response = $this->actingAs($plainUser)
            ->get(route('settings.roles.index'));

        $response->assertStatus(403);
    }
}
