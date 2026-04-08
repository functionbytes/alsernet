<?php

namespace Modules\Role\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Use real MariaDB — SQLite lacks guard_name on the roles table
        // due to a conflicting stripped-down migration that runs first.
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'inoqualabsystem',
        ]);
        DB::purge();
        DB::reconnect('mysql');

        // Tables are expected to exist in the MariaDB dev database.
        // Run `php artisan migrate` manually if needed before running these tests.
    }

    protected function createAdminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'administrative', 'guard_name' => 'web']);

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
