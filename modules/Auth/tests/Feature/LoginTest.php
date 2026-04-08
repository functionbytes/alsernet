<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'available' => true,
        ], $attributes));
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('auth.login'));
        $response->assertStatus(200);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson(route('auth.login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson(route('auth.login.post'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false]);

        $this->assertGuest();
    }

    public function test_login_fails_for_disabled_account(): void
    {
        $user = $this->makeUser(['available' => false]);

        $response = $this->postJson(route('auth.login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['success' => false]);

        $this->assertGuest();
    }

    public function test_login_records_last_login_metadata(): void
    {
        $user = $this->makeUser();

        $this->postJson(route('auth.login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson(route('auth.login.post'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_invalidates_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $this->post(route('auth.logout'));

        $this->assertGuest();
    }
}
