<?php

namespace Modules\System\Tests\Feature;

use Modules\System\Tests\TestCase;

/**
 * Authorization tests for SupervisorController write routes.
 *
 * Complements SupervisorSecurityTest with focused assertions on the `start`
 * endpoint for each role and on processName validation edge cases.
 *
 * Write routes require middleware: auth + role:super-settings|administrative|manager
 */
class SupervisorAuthorizationTest extends TestCase
{
    // ---------------------------------------------------------------
    // Unauthenticated access
    // ---------------------------------------------------------------

    public function test_unauthenticated_post_to_start_redirects_to_login(): void
    {
        $response = $this->post(
            route('settings.system.supervisor.start', ['processName' => 'worker:queue_0'])
        );

        $response->assertRedirect(route('auth.login'));
    }

    // ---------------------------------------------------------------
    // callcenter role — passes 'settings' middleware but NOT the
    // elevated write group (role:super-settings|administrative|manager)
    // ---------------------------------------------------------------

    public function test_callcenter_role_receives_403_on_supervisor_start(): void
    {
        $user = $this->createUserWithRole('callcenter');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.start', ['processName' => 'worker:queue_0']));

        $response->assertForbidden();
    }

    public function test_callcenter_role_receives_403_on_supervisor_stop(): void
    {
        $user = $this->createUserWithRole('callcenter');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.stop', ['processName' => 'worker:queue_0']));

        $response->assertForbidden();
    }

    public function test_callcenter_role_receives_403_on_supervisor_restart(): void
    {
        $user = $this->createUserWithRole('callcenter');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.restart', ['processName' => 'worker:queue_0']));

        $response->assertForbidden();
    }

    // ---------------------------------------------------------------
    // manager role — allowed by both 'settings' and write middleware
    // ---------------------------------------------------------------

    public function test_manager_role_is_not_forbidden_on_supervisor_start(): void
    {
        $user = $this->createUserWithRole('manager');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.start', ['processName' => 'worker:queue_0']));

        // Supervisorctl will fail in test environment (500), but middleware must not block (401/403)
        $this->assertNotContains($response->status(), [401, 403]);
    }

    public function test_manager_role_is_not_forbidden_on_supervisor_stop(): void
    {
        $user = $this->createUserWithRole('manager');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.stop', ['processName' => 'worker:queue_0']));

        $this->assertNotContains($response->status(), [401, 403]);
    }

    // ---------------------------------------------------------------
    // administrative role — allowed by both 'settings' and write middleware
    // ---------------------------------------------------------------

    public function test_administrative_role_is_not_forbidden_on_supervisor_start(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.start', ['processName' => 'worker:queue_0']));

        $this->assertNotContains($response->status(), [401, 403]);
    }

    public function test_administrative_role_is_not_forbidden_on_supervisor_stop(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.stop', ['processName' => 'worker:queue_0']));

        $this->assertNotContains($response->status(), [401, 403]);
    }

    // ---------------------------------------------------------------
    // Artisan command allowlist
    // ---------------------------------------------------------------

    public function test_run_command_rejects_migrate_fresh_with_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.run-command'), ['command' => 'migrate:fresh']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['command']);
    }

    public function test_run_command_does_not_reject_cache_clear_with_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.run-command'), ['command' => 'cache:clear']);

        // Validation passes; execution may return 200 or 503 depending on environment
        $this->assertNotEquals(422, $response->status());
    }

    // ---------------------------------------------------------------
    // processName validation
    // ---------------------------------------------------------------

    public function test_start_with_process_name_all_returns_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.start', ['processName' => 'all']));

        $response->assertUnprocessable();
    }

    public function test_start_with_special_characters_in_process_name_returns_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        // URL-encode so the router accepts the segment; the controller must reject it
        $encoded = rawurlencode('bad;name');

        $response = $this->actingAs($user)
            ->postJson("/setting/system/supervisor/{$encoded}/start");

        $response->assertUnprocessable();
    }

    public function test_start_with_valid_process_name_passes_validation(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.start', ['processName' => 'my-worker']));

        // Validation passes; supervisorctl failure returns 500, not 422
        $this->assertNotEquals(422, $response->status());
    }
}
