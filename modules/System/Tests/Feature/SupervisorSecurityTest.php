<?php

namespace Modules\System\Tests\Feature;

use Modules\System\Tests\TestCase;

/**
 * Tests for the critical security fixes in SupervisorController:
 *  - Role-based access to write/destructive routes
 *  - Artisan command allowlist enforced by runCommand
 *  - processName validation via SupervisorService::validateProcessName
 */
class SupervisorSecurityTest extends TestCase
{
    // ---------------------------------------------------------------
    // Authorization — unauthenticated
    // ---------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected_from_supervisor_start(): void
    {
        $response = $this->post(route('settings.system.supervisor.start', ['processName' => 'worker:queue_0']));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_from_supervisor_stop(): void
    {
        $response = $this->post(route('settings.system.supervisor.stop', ['processName' => 'worker:queue_0']));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_from_run_command(): void
    {
        $response = $this->postJson(route('settings.system.supervisor.run-command'), ['command' => 'cache:clear']);

        // JSON request to an auth-protected route returns 401
        $response->assertUnauthorized();
    }

    // ---------------------------------------------------------------
    // Authorization — callcenter role (access to 'settings' middleware,
    // but NOT in the elevated write group)
    // ---------------------------------------------------------------

    public function test_callcenter_user_receives_403_on_supervisor_start(): void
    {
        $user = $this->createUserWithRole('callcenter');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.start', ['processName' => 'worker:queue_0']));

        $response->assertForbidden();
    }

    public function test_callcenter_user_receives_403_on_supervisor_stop(): void
    {
        $user = $this->createUserWithRole('callcenter');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.stop', ['processName' => 'worker:queue_0']));

        $response->assertForbidden();
    }

    public function test_callcenter_user_receives_403_on_supervisor_restart(): void
    {
        $user = $this->createUserWithRole('callcenter');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.restart', ['processName' => 'worker:queue_0']));

        $response->assertForbidden();
    }

    public function test_callcenter_user_receives_403_on_run_command(): void
    {
        $user = $this->createUserWithRole('callcenter');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.run-command'), ['command' => 'cache:clear']);

        $response->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Authorization — administrative role (allowed)
    // ---------------------------------------------------------------

    public function test_administrative_user_is_not_forbidden_on_run_command(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.run-command'), ['command' => 'cache:clear']);

        // The route is reachable; the command may succeed or fail in the test
        // environment, but the middleware must not return 401 or 403.
        $this->assertNotContains($response->status(), [401, 403]);
    }

    public function test_manager_user_is_not_forbidden_on_run_command(): void
    {
        $user = $this->createUserWithRole('manager');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.run-command'), ['command' => 'cache:clear']);

        $this->assertNotContains($response->status(), [401, 403]);
    }

    // ---------------------------------------------------------------
    // Artisan command allowlist — runCommand endpoint
    // ---------------------------------------------------------------

    public function test_run_command_rejects_disallowed_command_with_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.run-command'), ['command' => 'migrate:fresh']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['command']);
    }

    public function test_run_command_rejects_shell_injection_attempt_with_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.run-command'), ['command' => 'cache:clear; rm -rf /']);

        $response->assertUnprocessable();
    }

    public function test_run_command_rejects_missing_command_with_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.run-command'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['command']);
    }

    public function test_run_command_accepts_allowed_command(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.run-command'), ['command' => 'cache:clear']);

        // Middleware passes; execution may succeed or return 503 if Artisan
        // encounters an environment issue — but never 401, 403, or 422.
        $this->assertNotContains($response->status(), [401, 403, 422]);
    }

    // ---------------------------------------------------------------
    // processName validation (start / stop / restart routes)
    // The controller calls SupervisorService::validateProcessName which
    // rejects "all" and any name with special characters.
    // ---------------------------------------------------------------

    public function test_start_with_process_name_all_returns_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.start', ['processName' => 'all']));

        $response->assertUnprocessable();
    }

    public function test_stop_with_process_name_all_returns_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.stop', ['processName' => 'all']));

        $response->assertUnprocessable();
    }

    public function test_restart_with_process_name_all_returns_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.restart', ['processName' => 'all']));

        $response->assertUnprocessable();
    }

    public function test_start_with_dangerous_process_name_returns_422(): void
    {
        $user = $this->createUserWithRole('administrative');

        // URL-encode the dangerous segment so the router accepts the request
        $processName = rawurlencode('; rm -rf');

        $response = $this->actingAs($user)
            ->postJson("/setting/system/supervisor/{$processName}/start");

        $response->assertUnprocessable();
    }

    public function test_start_with_valid_process_name_passes_validation(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.start', ['processName' => 'worker:queue_0']));

        // Validation passes; supervisorctl will fail in the test environment
        // returning 500, but not 422.
        $this->assertNotEquals(422, $response->status());
    }

    public function test_restart_with_valid_process_name_passes_validation(): void
    {
        $user = $this->createUserWithRole('administrative');

        $response = $this->actingAs($user)
            ->postJson(route('settings.system.supervisor.restart', ['processName' => 'worker:queue_0']));

        $this->assertNotEquals(422, $response->status());
    }
}
