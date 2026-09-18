<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Tests\AuthTestCase;

class SessionManagementTest extends AuthTestCase
{
    private function makeUser(): User
    {
        return User::factory()->create([
            'available' => true,
        ]);
    }

    private function seedSession(int $userId, string $sessionId, bool $current = false): void
    {
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'payload' => '',
            'last_activity' => time(),
        ]);
    }

    public function test_sessions_list_requires_authentication(): void
    {
        $response = $this->getJson(route('settings.auth.sessions.list'));
        $response->assertStatus(401);
    }

    public function test_sessions_list_returns_user_sessions(): void
    {
        $user = $this->makeUser();
        $this->seedSession($user->id, 'session-abc');

        $response = $this->actingAs($user)
            ->getJson(route('settings.auth.sessions.list'));

        $response->assertStatus(200)
            ->assertJsonStructure(['sessions' => [['id', 'ip_address', 'user_agent', 'last_activity', 'is_current']]]);
    }

    public function test_can_revoke_another_session(): void
    {
        $user = $this->makeUser();
        $this->seedSession($user->id, 'other-session-xyz');

        $response = $this->actingAs($user)
            ->deleteJson(route('settings.auth.sessions.destroy', ['sessionId' => 'other-session-xyz']));

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-xyz']);
    }

    public function test_destroy_others_requires_correct_password(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)
            ->postJson(route('settings.auth.sessions.destroy-others'), [
                'password' => 'wrongpassword',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Contraseña incorrecta.']);
    }

    public function test_destroy_others_removes_other_sessions(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('mypassword'),
            'available' => true,
        ]);

        $this->seedSession($user->id, 'other-session-1');
        $this->seedSession($user->id, 'other-session-2');

        $response = $this->actingAs($user)
            ->postJson(route('settings.auth.sessions.destroy-others'), [
                'password' => 'mypassword',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-2']);
    }
}
