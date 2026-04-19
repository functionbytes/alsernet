<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Tests\AuthTestCase;

class LockoutTest extends AuthTestCase
{
    public function test_user_is_locked_after_max_failed_attempts(): void
    {
        config()->set('auth.auth-policy.lockout', [
            'enabled' => true,
            'max_attempts' => 3,
            'duration_minutes' => 30,
        ]);

        $user = User::factory()->create([
            'email' => 'lockout@example.com',
            'password' => Hash::make('correct-password'),
            'available' => true,
        ]);

        $service = app(AuthService::class);
        $request = request();

        for ($i = 0; $i < 3; $i++) {
            $result = $service->attempt(
                ['email' => 'lockout@example.com', 'password' => 'wrong'],
                false,
                $request,
            );
            $this->assertNull($result);
        }

        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->isLocked());
    }

    public function test_locked_user_cannot_login_even_with_correct_password(): void
    {
        $user = User::factory()->create([
            'email' => 'locked@example.com',
            'password' => Hash::make('correct-password'),
            'available' => true,
            'locked_until' => now()->addMinutes(30),
        ]);

        $result = app(AuthService::class)->attempt(
            ['email' => 'locked@example.com', 'password' => 'correct-password'],
            false,
            request(),
        );

        $this->assertNull($result);
    }

    public function test_successful_login_resets_failure_count(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('correct-password'),
            'available' => true,
            'failed_login_count' => 5,
        ]);

        app(AuthService::class)->attempt(
            ['email' => 'reset@example.com', 'password' => 'correct-password'],
            false,
            request(),
        );

        $user->refresh();
        $this->assertSame(0, (int) $user->failed_login_count);
        $this->assertNull($user->locked_until);
    }
}
