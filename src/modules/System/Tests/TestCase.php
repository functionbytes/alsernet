<?php

namespace Modules\System\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Map the 'helpdesk' connection to SQLite before migrations run.
     * Two Helpdesk migrations declare `$connection = 'helpdesk'` which is
     * not configured in the test environment; this prevents migrate:fresh
     * from failing.
     */
    protected function beforeRefreshingDatabase(): void
    {
        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    /**
     * After migrations, patch the roles table if the Role module's stripped
     * migration ran before Spatie's full migration, leaving guard_name absent.
     * Then flush Spatie's permission cache so it re-reads the patched schema.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('roles') && ! Schema::hasColumn('roles', 'guard_name')) {
            Schema::table('roles', function ($table) {
                $table->string('guard_name')->default('web')->after('name');
                $table->unique(['name', 'guard_name']);
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    /**
     * Create a user with the given Spatie role.
     * The role model is passed directly to assignRole to avoid a findByName
     * lookup that fails on the stripped SQLite roles table (no guard_name).
     */
    protected function createUserWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }
}
