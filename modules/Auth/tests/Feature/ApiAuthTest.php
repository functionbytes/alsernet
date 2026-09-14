<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Tests\AuthTestCase;

class ApiAuthTest extends AuthTestCase
{
    public function test_api_login_returns_token(): void
    {
        User::factory()->create([
            'email' => 'api@example.com',
            'password' => Hash::make('Secret@123'),
            'available' => true,
        ]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'api@example.com',
            'password' => 'Secret@123',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'token_type', 'user' => ['id', 'email']],
            ]);
    }

    public function test_api_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'bad@example.com',
            'password' => Hash::make('Secret@123'),
            'available' => true,
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => 'bad@example.com',
            'password' => 'wrong',
            'device_name' => 'phpunit',
        ])->assertStatus(422);
    }

    public function test_api_me_returns_current_user(): void
    {
        $user = User::factory()->create(['available' => true]);
        Sanctum::actingAs($user);

        $this->getJson(route('api.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_api_logout_deletes_token(): void
    {
        $user = User::factory()->create(['available' => true]);
        Sanctum::actingAs($user);

        $this->postJson(route('api.auth.logout'))->assertOk();
    }

    public function test_token_crud_endpoints(): void
    {
        $user = User::factory()->create(['available' => true]);
        Sanctum::actingAs($user);

        $create = $this->postJson(route('api.auth.tokens.store'), [
            'name' => 'Test Device',
        ]);
        $create->assertCreated()->assertJsonPath('success', true);

        $this->getJson(route('api.auth.tokens.index'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
