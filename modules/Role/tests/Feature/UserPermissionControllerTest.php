<?php

namespace Modules\Role\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Role\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Feature tests for UserPermissionController.
 *
 * Uses DatabaseTransactions (from module TestCase) to roll back after each test.
 * Routes are under panel/settings/ with middleware: web, auth, settings.
 * The 'settings' middleware requires one of the allowed roles (e.g. 'manager').
 */
class UserPermissionControllerTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdminUser();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function createPlainUser(): User
    {
        return User::create([
            'uid' => Str::uuid(),
            'firstname' => 'Plain',
            'lastname' => 'User',
            'email' => 'plain-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'available' => true,
        ]);
    }

    private function createPermission(?string $name = null): Permission
    {
        $name ??= 'test.permission.'.Str::random(6);

        return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    // -----------------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------------

    public function test_index_requires_authentication(): void
    {
        $this->get(route('settings.user-permissions.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_requires_settings_role(): void
    {
        $userWithNoRole = $this->createPlainUser();

        $this->actingAs($userWithNoRole)
            ->get(route('settings.user-permissions.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_users_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.user-permissions.index'))
            ->assertOk()
            ->assertViewIs('role::users.index');
    }

    public function test_index_returns_paginated_users_with_permission_count(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('settings.user-permissions.index'));

        $response->assertOk();

        $users = $response->viewData('users');
        $firstUser = $users->first();
        $this->assertNotNull($firstUser);
        $this->assertNotNull($firstUser->direct_permissions_count, 'Each user should have direct_permissions_count via withCount');
    }

    public function test_index_filters_by_search(): void
    {
        $unique = Str::random(8);
        $targetUser = User::create([
            'uid' => Str::uuid(),
            'firstname' => 'Filterable'.$unique,
            'lastname' => 'Person',
            'email' => 'filterable-'.$unique.'@example.com',
            'password' => Hash::make('password'),
            'available' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('settings.user-permissions.index', ['search' => 'Filterable'.$unique]));

        $response->assertOk();
        $users = $response->viewData('users');
        $this->assertTrue($users->contains('id', $targetUser->id));
    }

    // -----------------------------------------------------------------------
    // Show
    // -----------------------------------------------------------------------

    public function test_show_requires_authentication(): void
    {
        $user = $this->createPlainUser();

        $this->get(route('settings.user-permissions.show', $user))
            ->assertRedirect(route('auth.login'));
    }

    public function test_admin_can_view_user_permissions(): void
    {
        $target = $this->createPlainUser();

        $this->actingAs($this->admin)
            ->get(route('settings.user-permissions.show', $target))
            ->assertOk()
            ->assertViewIs('role::users.permissions');
    }

    // -----------------------------------------------------------------------
    // Update (toggle)
    // -----------------------------------------------------------------------

    public function test_update_assigns_permission_to_user(): void
    {
        $target = $this->createPlainUser();
        $permission = $this->createPermission();

        $this->actingAs($this->admin)
            ->postJson(route('settings.user-permissions.update', $target), [
                'permission_id' => $permission->id,
                'action' => 'attach',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('model_has_permissions', [
            'permission_id' => $permission->id,
            'model_id' => $target->id,
            'model_type' => User::class,
        ]);
    }

    public function test_update_revokes_permission_from_user(): void
    {
        $target = $this->createPlainUser();
        $permission = $this->createPermission();
        $target->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->admin)
            ->postJson(route('settings.user-permissions.update', $target), [
                'permission_id' => $permission->id,
                'action' => 'detach',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('model_has_permissions', [
            'permission_id' => $permission->id,
            'model_id' => $target->id,
            'model_type' => User::class,
        ]);
    }

    public function test_update_validates_required_fields(): void
    {
        $target = $this->createPlainUser();

        $this->actingAs($this->admin)
            ->postJson(route('settings.user-permissions.update', $target), [
                'action' => 'attach',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permission_id']);
    }

    public function test_update_validates_action_values(): void
    {
        $target = $this->createPlainUser();
        $permission = $this->createPermission();

        $this->actingAs($this->admin)
            ->postJson(route('settings.user-permissions.update', $target), [
                'permission_id' => $permission->id,
                'action' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    }

    // -----------------------------------------------------------------------
    // Sync
    // -----------------------------------------------------------------------

    public function test_sync_replaces_all_user_permissions(): void
    {
        $target = $this->createPlainUser();
        $oldPermission = $this->createPermission();
        $newPermission = $this->createPermission();
        $target->givePermissionTo($oldPermission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->admin)
            ->postJson(route('settings.user-permissions.sync', $target), [
                'permissions' => [$newPermission->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('model_has_permissions', [
            'permission_id' => $newPermission->id,
            'model_id' => $target->id,
            'model_type' => User::class,
        ]);

        $this->assertDatabaseMissing('model_has_permissions', [
            'permission_id' => $oldPermission->id,
            'model_id' => $target->id,
            'model_type' => User::class,
        ]);
    }

    public function test_sync_with_empty_array_removes_all_permissions(): void
    {
        $target = $this->createPlainUser();
        $permission = $this->createPermission();
        $target->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->admin)
            ->postJson(route('settings.user-permissions.sync', $target), [
                'permissions' => [],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('model_has_permissions', [
            'model_id' => $target->id,
            'model_type' => User::class,
        ]);
    }

    public function test_sync_validates_permission_ids_exist(): void
    {
        $target = $this->createPlainUser();

        $this->actingAs($this->admin)
            ->postJson(route('settings.user-permissions.sync', $target), [
                'permissions' => [999999999],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.0']);
    }
}
