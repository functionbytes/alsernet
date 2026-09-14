<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Modules\Auth\Services\ImpersonationService;
use Modules\Auth\Tests\AuthTestCase;
use Spatie\Permission\Models\Permission;

class ImpersonationTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'auth.impersonate', 'guard_name' => 'web']);
    }

    public function test_user_without_permission_cannot_impersonate(): void
    {
        $admin = User::factory()->create(['available' => true]);
        $target = User::factory()->create(['available' => true]);

        $this->actingAs($admin)
            ->post(route('auth.impersonation.start', $target->id))
            ->assertStatus(403);
    }

    public function test_user_with_permission_can_start_impersonation(): void
    {
        $admin = User::factory()->create(['available' => true]);
        $admin->givePermissionTo('auth.impersonate');

        $target = User::factory()->create(['available' => true]);

        $this->actingAs($admin)
            ->post(route('auth.impersonation.start', $target->id))
            ->assertRedirect();

        $this->assertDatabaseHas('impersonation_logs', [
            'impersonator_id' => $admin->id,
            'impersonated_id' => $target->id,
        ]);

        $this->assertAuthenticatedAs($target);
        $this->assertTrue(session()->has(ImpersonationService::SESSION_KEY));
    }

    public function test_stop_restores_original_user(): void
    {
        $admin = User::factory()->create(['available' => true]);
        $target = User::factory()->create(['available' => true]);

        $this->actingAs($target)
            ->withSession([ImpersonationService::SESSION_KEY => $admin->id])
            ->post(route('auth.impersonation.stop'))
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_cannot_impersonate_self(): void
    {
        $admin = User::factory()->create(['available' => true]);
        $admin->givePermissionTo('auth.impersonate');

        $this->actingAs($admin)
            ->post(route('auth.impersonation.start', $admin->id))
            ->assertStatus(403);
    }
}
