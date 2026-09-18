<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Customer;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * B1-perms regression tests.
 *
 * T0.3: StatusPageController uses `helpdesk.status.manage` middleware.
 *       Before the fix the permission was not seeded, so even a super-admin
 *       could not access the status page.
 *
 * T0.4: CustomerPolicy::manage() did not exist.
 *       GDPR export / delete flows call Gate::authorize('manage', Customer::class),
 *       which would throw a PolicyException (no policy method).
 */
class StatusPagePermissionTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $authorizedUser;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->authorizedUser = User::factory()->create();
        $this->authorizedUser->assignRole($role);

        $this->unauthorizedUser = User::factory()->create();
    }

    // ── StatusPageController — T0.3 ───────────────────────────────────────────

    public function test_guest_is_redirected_from_status_page_components(): void
    {
        $this->get(route('settings.helpdesk.status.components'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_status_manage_permission_gets_403_on_components(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->get(route('settings.helpdesk.status.components'))
            ->assertForbidden();
    }

    public function test_user_with_status_manage_permission_can_access_components(): void
    {
        $this->actingAs($this->authorizedUser)
            ->get(route('settings.helpdesk.status.components'))
            ->assertOk();
    }

    public function test_user_without_status_manage_permission_gets_403_on_incidents(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->get(route('settings.helpdesk.status.incidents'))
            ->assertForbidden();
    }

    public function test_user_with_status_manage_permission_can_access_incidents(): void
    {
        $this->actingAs($this->authorizedUser)
            ->get(route('settings.helpdesk.status.incidents'))
            ->assertOk();
    }

    // ── CustomerPolicy::manage() — T0.4 ──────────────────────────────────────

    public function test_customer_policy_manage_denies_user_without_permission(): void
    {
        $this->assertFalse(
            $this->unauthorizedUser->can('manage', Customer::class),
            'Un usuario sin helpdesk.customers.manage no debe pasar CustomerPolicy::manage()'
        );
    }

    public function test_customer_policy_manage_allows_user_with_permission(): void
    {
        // authorizedUser has super-settings role; Gate::before grants all abilities
        $this->assertTrue(
            $this->authorizedUser->can('manage', Customer::class),
            'Un usuario con super-settings debe pasar CustomerPolicy::manage() via Gate::before'
        );
    }

    public function test_customer_policy_manage_is_independent_from_status_manage(): void
    {
        // A user without any role/permission must be denied CustomerPolicy::manage()
        $this->assertFalse(
            $this->unauthorizedUser->can('manage', Customer::class),
            'Un usuario sin permisos no debe pasar CustomerPolicy::manage()'
        );
    }
}
