<?php

namespace Modules\Cookie\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Configure the 'helpdesk' database connection before migrations run.
     *
     * Two Helpdesk migrations specify `$connection = 'helpdesk'` which is not
     * defined in the test environment. Mapping it to the default SQLite connection
     * here (via the RefreshDatabase hook) allows migrate:fresh to complete.
     */
    protected function beforeRefreshingDatabase(): void
    {
        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    protected function createUser(array $permissions = []): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
            $user->givePermissionTo($permission);
        }

        return $user;
    }
}
