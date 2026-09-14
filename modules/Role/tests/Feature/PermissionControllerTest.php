<?php

namespace Modules\Role\Tests\Feature;

use Modules\Role\Tests\TestCase;
use Spatie\Permission\Models\Permission;

class PermissionControllerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // GET /settings/permissions — index
    // -------------------------------------------------------------------------

    public function test_admin_can_view_permissions_index(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('settings.permissions.index'))
            ->assertOk()
            ->assertViewIs('role::permissions.index');
    }

    public function test_permissions_index_filters_by_search(): void
    {
        $user = $this->createAdminUser();
        $unique = uniqid('test.perm.');
        Permission::firstOrCreate(['name' => $unique.'.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => $unique.'.edit', 'guard_name' => 'web']);

        $response = $this->actingAs($user)
            ->get(route('settings.permissions.index', ['search' => $unique.'.view']))
            ->assertOk();

        $perms = $response->viewData('permissions');
        $this->assertTrue($perms->contains('name', $unique.'.view'));
        $this->assertFalse($perms->contains('name', $unique.'.edit'));
    }

    // -------------------------------------------------------------------------
    // POST /settings/permissions — store
    // -------------------------------------------------------------------------

    public function test_can_create_permission(): void
    {
        $user = $this->createAdminUser();
        $name = 'test.new.perm.'.uniqid();

        $this->actingAs($user)
            ->postJson(route('settings.permissions.store'), [
                'name' => $name,
                'guard_name' => 'web',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('permissions', ['name' => $name]);
    }

    public function test_can_create_permission_with_description(): void
    {
        $user = $this->createAdminUser();
        $name = 'test.desc.perm.'.uniqid();

        $this->actingAs($user)
            ->postJson(route('settings.permissions.store'), [
                'name' => $name,
                'guard_name' => 'web',
                'description' => 'Permiso de prueba con descripción',
            ])
            ->assertOk();

        $this->assertDatabaseHas('permissions', [
            'name' => $name,
            'description' => 'Permiso de prueba con descripción',
        ]);
    }

    public function test_store_requires_name(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->postJson(route('settings.permissions.store'), ['guard_name' => 'web'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_rejects_duplicate_name(): void
    {
        $user = $this->createAdminUser();
        Permission::firstOrCreate(['name' => 'test.duplicate.already', 'guard_name' => 'web']);

        $this->actingAs($user)
            ->postJson(route('settings.permissions.store'), [
                'name' => 'test.duplicate.already',
                'guard_name' => 'web',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_rejects_invalid_guard(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->postJson(route('settings.permissions.store'), [
                'name' => 'test.bad.guard.'.uniqid(),
                'guard_name' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guard_name']);
    }

    public function test_store_rejects_name_with_invalid_characters(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->postJson(route('settings.permissions.store'), [
                'name' => 'invalid name with spaces',
                'guard_name' => 'web',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // PUT /settings/permissions/{permission} — update
    // -------------------------------------------------------------------------

    public function test_can_update_permission_name(): void
    {
        $user = $this->createAdminUser();
        $perm = Permission::create(['name' => 'test.old.name.'.uniqid(), 'guard_name' => 'web']);
        $newName = 'test.new.name.'.uniqid();

        $this->actingAs($user)
            ->putJson(route('settings.permissions.update', $perm->id), [
                'name' => $newName,
                'guard_name' => 'web',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('permissions', ['id' => $perm->id, 'name' => $newName]);
    }

    public function test_can_update_permission_description(): void
    {
        $user = $this->createAdminUser();
        $perm = Permission::create(['name' => 'test.desc.update.'.uniqid(), 'guard_name' => 'web']);

        $this->actingAs($user)
            ->putJson(route('settings.permissions.update', $perm->id), [
                'name' => $perm->name,
                'guard_name' => 'web',
                'description' => 'Descripción actualizada',
            ])
            ->assertOk();

        $this->assertDatabaseHas('permissions', [
            'id' => $perm->id,
            'description' => 'Descripción actualizada',
        ]);
    }

    public function test_update_allows_same_name_for_same_permission(): void
    {
        $user = $this->createAdminUser();
        $perm = Permission::create(['name' => 'test.same.name.'.uniqid(), 'guard_name' => 'web']);

        $this->actingAs($user)
            ->putJson(route('settings.permissions.update', $perm->id), [
                'name' => $perm->name,
                'guard_name' => 'web',
            ])
            ->assertOk();
    }

    public function test_update_rejects_duplicate_name_from_other_permission(): void
    {
        $user = $this->createAdminUser();
        Permission::firstOrCreate(['name' => 'test.taken.name', 'guard_name' => 'web']);
        $perm = Permission::create(['name' => 'test.to.rename.'.uniqid(), 'guard_name' => 'web']);

        $this->actingAs($user)
            ->putJson(route('settings.permissions.update', $perm->id), [
                'name' => 'test.taken.name',
                'guard_name' => 'web',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // DELETE /settings/permissions/{permission} — destroy
    // -------------------------------------------------------------------------

    public function test_can_delete_permission_not_assigned_to_roles(): void
    {
        $user = $this->createAdminUser();
        $perm = Permission::create(['name' => 'test.orphan.perm.'.uniqid(), 'guard_name' => 'web']);

        $this->actingAs($user)
            ->deleteJson(route('settings.permissions.destroy', $perm->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('permissions', ['id' => $perm->id]);
    }

    public function test_cannot_delete_permission_assigned_to_a_role(): void
    {
        $user = $this->createAdminUser();
        $perm = Permission::firstOrCreate(['name' => 'test.assigned.perm.'.uniqid(), 'guard_name' => 'web']);
        $role = $this->createRole();
        $role->givePermissionTo($perm);

        $this->actingAs($user)
            ->deleteJson(route('settings.permissions.destroy', $perm->id))
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('permissions', ['id' => $perm->id]);
    }

    public function test_unauthenticated_user_cannot_access_permissions(): void
    {
        $this->get(route('settings.permissions.index'))
            ->assertRedirect(route('auth.login'));
    }
}
