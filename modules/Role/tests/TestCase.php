<?php

namespace Modules\Role\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml routes DB_CONNECTION=mariadb → system_testing which has
        // all Spatie permission tables. Flush the permission cache so each test
        // starts with a clean Spatie state (avoids RoleAlreadyExists errors).
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function createAdminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        $user = User::create([
            'uid' => Str::uuid(),
            'firstname' => 'Admin',
            'lastname' => 'Test',
            'email' => 'role-admin-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'available' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function createUser(array $permissions = []): User
    {
        $user = User::create([
            'uid' => Str::uuid(),
            'firstname' => 'User',
            'lastname' => 'Test',
            'email' => 'role-user-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'available' => true,
        ]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    protected function createRole(array $attributes = []): Role
    {
        return Role::create(array_merge([
            'name' => 'rol_prueba_'.uniqid(),
            'guard_name' => 'web',
        ], $attributes));
    }
}
