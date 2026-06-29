<?php

namespace Modules\Role\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Feature tests for RoleController.
 *
 * Uses DatabaseTransactions to wrap each test in a transaction that rolls back,
 * avoiding the need to re-run migrations on the live database.
 */
class RoleControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected Role $settingsRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Reuse the existing 'manager' role (already in DB) or create it
        $this->settingsRole = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => 'web']
        );

        // Create a test pages user for this test run
        $this->admin = User::create([
            'firstname' => 'Test',
            'lastname' => 'Admin',
            'email' => 'pages-test-'.uniqid().'@test.local',
            'password' => Hash::make('password'),
        ]);

        $this->admin->assignRole($this->settingsRole);

        // Clear Spatie permission cache to prevent stale data between tests
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // -----------------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------------

    public function test_admin_can_view_roles_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('settings.roles.index'));

        $response->assertOk()
            ->assertViewIs('role::roles.index')
            ->assertViewHas('roles');
    }

    public function test_index_filters_by_search_keyword(): void
    {
        $unique = uniqid('test-');
        Role::create(['name' => 'editor-'.$unique, 'guard_name' => 'web']);
        Role::create(['name' => 'viewer-'.$unique, 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.roles.index', ['search' => 'editor-'.$unique]));

        $response->assertOk();
        $roles = $response->viewData('roles');
        $this->assertTrue($roles->contains('name', 'editor-'.$unique));
        $this->assertFalse($roles->contains('name', 'viewer-'.$unique));
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
        $roleName = 'test-new-'.uniqid();

        $response = $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'name' => $roleName,
                'guard_name' => 'web',
                'description' => 'A test role',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['name' => $roleName, 'guard_name' => 'web']);
    }

    public function test_store_assigns_permissions_to_new_role(): void
    {
        $permName = 'test.perm.'.uniqid();
        $permission = Permission::create(['name' => $permName, 'guard_name' => 'web']);

        $roleName = 'test-role-'.uniqid();
        $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'name' => $roleName,
                'guard_name' => 'web',
                'permissions' => [$permission->id],
            ]);

        $role = Role::where('name', $roleName)->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo($permName));
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
                'name' => 'ab',
                'guard_name' => 'web',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_fails_validation_when_name_is_duplicate(): void
    {
        $existingName = 'test-existing-'.uniqid();
        Role::create(['name' => $existingName, 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'name' => $existingName,
                'guard_name' => 'web',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_fails_validation_with_invalid_guard(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.roles.store'), [
                'name' => 'test-valid-'.uniqid(),
                'guard_name' => 'invalid-guard',
            ]);

        $response->assertSessionHasErrors(['guard_name']);
    }

    // -----------------------------------------------------------------------
    // Edit
    // -----------------------------------------------------------------------

    public function test_admin_can_view_edit_form(): void
    {
        $role = Role::create(['name' => 'test-edit-'.uniqid(), 'guard_name' => 'web']);

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
        $role = Role::create(['name' => 'test-old-'.uniqid(), 'guard_name' => 'web']);
        $newName = 'test-updated-'.uniqid();

        $response = $this->actingAs($this->admin)
            ->put(route('settings.roles.update', $role), [
                'name' => $newName,
                'guard_name' => 'web',
            ]);

        $response->assertRedirect(route('settings.roles.edit', $role->id));
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $newName]);
    }

    public function test_update_syncs_permissions(): void
    {
        $role = Role::create(['name' => 'test-perm-update-'.uniqid(), 'guard_name' => 'web']);
        $permName = 'test.roles.edit.'.uniqid();
        $permission = Permission::create(['name' => $permName, 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->put(route('settings.roles.update', $role), [
                'name' => $role->name,
                'guard_name' => 'web',
                'permissions' => [$permission->id],
            ]);

        $this->assertTrue($role->fresh()->hasPermissionTo($permName));
    }

    public function test_cannot_update_super_settings_role(): void
    {
        $systemRole = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->put(route('settings.roles.update', $systemRole), [
                'name' => 'super-settings',
                'guard_name' => 'web',
            ]);

        // Controller returns error() — a 400 JSON response
        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Cannot modify system roles']);
    }

    public function test_update_fails_validation_when_name_is_missing(): void
    {
        $role = Role::create(['name' => 'test-validate-'.uniqid(), 'guard_name' => 'web']);

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
        $role = Role::create(['name' => 'test-delete-'.uniqid(), 'guard_name' => 'web']);
        $roleId = $role->id;

        $response = $this->actingAs($this->admin)
            ->delete(route('settings.roles.destroy', $role));

        $response->assertRedirect(route('settings.roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
    }

    public function test_cannot_delete_super_settings_role(): void
    {
        $systemRole = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->delete(route('settings.roles.destroy', $systemRole));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    public function test_cannot_delete_customer_role(): void
    {
        $systemRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->delete(route('settings.roles.destroy', $systemRole));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    public function test_cannot_delete_role_with_assigned_users(): void
    {
        $role = Role::create(['name' => 'test-with-users-'.uniqid(), 'guard_name' => 'web']);

        $user = User::create([
            'firstname' => 'Assigned',
            'lastname' => 'User',
            'email' => 'assigned-'.uniqid().'@test.local',
            'password' => Hash::make('password'),
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
        $roleName = 'test-unauth-'.uniqid();

        $response = $this->post(route('settings.roles.store'), [
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $response->assertRedirect(route('auth.login'));
        $this->assertDatabaseMissing('roles', ['name' => $roleName]);
    }

    public function test_user_without_settings_role_cannot_access_roles(): void
    {
        $plainRole = Role::create(['name' => 'test-plain-'.uniqid(), 'guard_name' => 'web']);

        $plainUser = User::create([
            'firstname' => 'Plain',
            'lastname' => 'User',
            'email' => 'plain-'.uniqid().'@test.local',
            'password' => Hash::make('password'),
        ]);
        $plainUser->assignRole($plainRole);

        $response = $this->actingAs($plainUser)
            ->get(route('settings.roles.index'));

        $response->assertStatus(403);
    }
}
