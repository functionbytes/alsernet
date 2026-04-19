<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Tests\AuthTestCase;

class PasswordResetTest extends AuthTestCase
{
    private function makeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'available' => true,
        ], $attributes));
    }

    public function test_forgot_password_page_accessible(): void
    {
        $response = $this->get(route('auth.password.request'));
        $response->assertStatus(200);
    }

    public function test_forgot_password_requires_email(): void
    {
        $response = $this->post(route('auth.password.email'), []);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_forgot_password_returns_success_view_for_unknown_user(): void
    {
        // Controller deliberately shows success to prevent email enumeration
        $response = $this->post(route('auth.password.email'), [
            'email' => 'unknown-'.uniqid().'@example.com',
        ]);

        $response->assertOk();
        $response->assertSessionMissing('errors');
    }

    public function test_forgot_password_sets_reset_token_for_known_user(): void
    {
        $user = $this->makeUser();

        $this->post(route('auth.password.email'), [
            'email' => $user->email,
        ]);

        $user->refresh();
        $this->assertNotNull($user->password_reset_token);
        $this->assertNotNull($user->password_reset_last_tried_on);
        $this->assertEquals(1, $user->password_reset_max_tries);
    }

    public function test_forgot_password_rate_limits_too_many_requests(): void
    {
        $user = $this->makeUser();

        // Hit the Redis rate limiter (max 3 attempts) — 3 allowed, 4th blocked
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('auth.password.email'), ['email' => $user->email]);
        }

        $response = $this->post(route('auth.password.email'), ['email' => $user->email]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_reset_form_is_shown_with_valid_token(): void
    {
        $response = $this->get(route('auth.password.reset', ['token' => 'abc123', 'email' => 'test@example.com']));
        $response->assertStatus(200);
    }

    public function test_password_reset_requires_min_8_chars(): void
    {
        $plainToken = 'validtoken123';
        $user = $this->makeUser([
            'password_reset_token' => Hash::make($plainToken),
            'password_reset_last_tried_on' => now(),
        ]);

        $response = $this->post(route('auth.password.update'), [
            'token' => $plainToken,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_reset_works_with_valid_token(): void
    {
        $plainToken = 'validtoken123';
        $user = $this->makeUser([
            'password_reset_token' => Hash::make($plainToken),
            'password_reset_last_tried_on' => now(),
        ]);

        config()->set('auth.auth-policy.password', [
            'min_length' => 8,
            'require_uppercase' => false,
            'require_lowercase' => false,
            'require_numbers' => false,
            'require_symbols' => false,
            'reject_compromised' => false,
            'history_count' => 0,
        ]);

        $response = $this->post(route('auth.password.update'), [
            'token' => $plainToken,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertNull($user->password_reset_token);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_password_reset_rejects_expired_token(): void
    {
        $plainToken = 'expiredtoken';
        $user = $this->makeUser([
            'password_reset_token' => Hash::make($plainToken),
            'password_reset_last_tried_on' => now()->subHours(49), // older than 48h expiry
        ]);

        $response = $this->post(route('auth.password.update'), [
            'token' => $plainToken,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_reset_rejects_invalid_token(): void
    {
        $user = $this->makeUser([
            'password_reset_token' => Hash::make('correcttoken'),
            'password_reset_last_tried_on' => now(),
        ]);

        $response = $this->post(route('auth.password.update'), [
            'token' => 'wrongtoken',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
