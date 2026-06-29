<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Modules\Auth\Tests\AuthTestCase;
use Spatie\Permission\Models\Permission;

class AdminAuditTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'auth.audit.view', 'guard_name' => 'web']);
    }

    public function test_admin_without_permission_cannot_force_logout(): void
    {
        $admin = User::factory()->create(['available' => true]);
        $target = User::factory()->create(['available' => true]);

        $this->actingAs($admin)
            ->postJson(route('settings.auth.audit.force-logout', $target->id))
            ->assertStatus(403);
    }

    public function test_admin_with_permission_can_force_logout(): void
    {
        $admin = User::factory()->create(['available' => true]);
        $admin->givePermissionTo('auth.audit.view');

        $target = User::factory()->create(['available' => true]);
        $target->createToken('any-device');

        $this->actingAs($admin)
            ->postJson(route('settings.auth.audit.force-logout', $target->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_can_unlock_account(): void
    {
        $admin = User::factory()->create(['available' => true]);
        $admin->givePermissionTo('auth.audit.view');

        $locked = User::factory()->create([
            'available' => true,
            'locked_until' => now()->addHour(),
            'failed_login_count' => 10,
        ]);

        $this->actingAs($admin)
            ->postJson(route('settings.auth.audit.unlock', $locked->id))
            ->assertOk();

        $locked->refresh();
        $this->assertNull($locked->locked_until);
        $this->assertSame(0, (int) $locked->failed_login_count);
    }

    public function test_admin_can_restore_soft_deleted_account(): void
    {
        $admin = User::factory()->create(['available' => true]);
        $admin->givePermissionTo('auth.audit.view');

        $deleted = User::factory()->create(['available' => true]);
        $originalEmail = $deleted->email;
        $deleted->forceFill(['email' => "deleted_{$deleted->id}_{$originalEmail}"])->save();
        $deleted->delete();

        $this->actingAs($admin)
            ->postJson(route('settings.auth.audit.restore', $deleted->id))
            ->assertOk();

        $restored = User::withTrashed()->find($deleted->id);
        $this->assertNull($restored->deleted_at);
        $this->assertSame($originalEmail, $restored->email);
    }
}
