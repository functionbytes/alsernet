<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Modules\Auth\Tests\AuthTestCase;

/**
 * AuthTest
 *
 * Integration tests for the Auth module login/logout flow.
 * Covers guest access, credential validation, session handling,
 * and redirection behaviour for authenticated users.
 */
class AuthTest extends AuthTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => bcrypt('password'),
            'available' => true,
            // Ensure 2FA is disabled so login completes in one step
            'two_factor_confirmed_at' => null,
        ]);
    }

    // =========================================================================
    // GET /login — login page
    // =========================================================================

    public function test_login_page_returns_200_for_guests(): void
    {
        $this->get(route('auth.login'))
            ->assertStatus(200);
    }

    // =========================================================================
    // POST /login — valid credentials
    // =========================================================================

    public function test_post_login_with_valid_credentials_authenticates_user(): void
    {
        $response = $this->postJson(route('auth.login.post'), [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $this->assertAuthenticatedAs($this->user);
    }

    // =========================================================================
    // POST /login — invalid credentials
    // =========================================================================

    public function test_post_login_with_invalid_credentials_returns_error(): void
    {
        $response = $this->postJson(route('auth.login.post'), [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false]);

        $this->assertGuest();
    }

    // =========================================================================
    // POST /logout — redirects to login
    // =========================================================================

    public function test_post_logout_redirects_to_login(): void
    {
        $this->actingAs($this->user)
            ->post(route('auth.logout'))
            ->assertRedirect(route('auth.login'));

        $this->assertGuest();
    }

    // =========================================================================
    // GET /login — authenticated user is redirected away
    // =========================================================================

    public function test_authenticated_user_accessing_login_page_is_redirected(): void
    {
        // The guest middleware (RedirectIfAuthenticated) intercepts and sends
        // authenticated users to RouteServiceProvider::HOME ('/') before the
        // controller's own Auth::check() branch is reached.
        $this->actingAs($this->user)
            ->get(route('auth.login'))
            ->assertRedirect('/panel/dashboard');
    }

    // =========================================================================
    // Protected route — unauthenticated user redirected to login
    // =========================================================================

    public function test_unauthenticated_user_accessing_protected_route_is_redirected_to_login(): void
    {
        // The Authenticate middleware redirects to auth.login for non-JSON requests
        $this->get(route('core.dashboard'))
            ->assertRedirect(route('auth.login'));
    }

    // =========================================================================
    // POST /login — missing fields return validation errors
    // =========================================================================

    public function test_post_login_with_missing_fields_returns_validation_errors(): void
    {
        $response = $this->postJson(route('auth.login.post'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // =========================================================================
    // POST /login — disabled account is rejected
    // =========================================================================

    public function test_post_login_with_disabled_account_returns_error(): void
    {
        $disabled = User::factory()->create([
            'password' => bcrypt('password'),
            'available' => false,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->postJson(route('auth.login.post'), [
            'email' => $disabled->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false]);

        $this->assertGuest();
    }
}
