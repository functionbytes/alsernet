<?php

namespace Modules\Database\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DatabaseCleanupTest extends TestCase
{
    use DatabaseTransactions;

    // mysql/mariadb/helpdesk apuntan a la MISMA BD real - RefreshDatabase la
    // migro-fresh por un fallo de force="true" en phpunit.xml (incidente
    // 29-ago-2026) - nunca usar RefreshDatabase en este proyecto.
    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/panel/settings/database/cleanup');

        $response->assertRedirect();
    }

    public function test_user_without_permission_cannot_view_cleanup(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/panel/settings/database/cleanup');

        $response->assertForbidden();
    }

    public function test_truncate_requires_password_confirmation(): void
    {
        Permission::firstOrCreate(['name' => 'database.cleanup.truncate', 'guard_name' => 'web']);
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);
        $user->givePermissionTo('database.cleanup.truncate');

        $response = $this->actingAs($user)->postJson('/panel/settings/database/cleanup/truncate', [
            'tables' => ['some_table'],
            // missing password
        ]);

        $response->assertStatus(422);
    }
}
