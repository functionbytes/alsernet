<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Events\LoginFailed;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Tests\AuthTestCase;

class EventsTest extends AuthTestCase
{
    public function test_user_logged_in_event_is_dispatched_on_successful_login(): void
    {
        Event::fake([UserLoggedIn::class]);

        $user = User::factory()->create([
            'email' => 'ev@example.com',
            'password' => Hash::make('password123'),
            'available' => true,
        ]);

        $this->post(route('auth.login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        Event::assertDispatched(UserLoggedIn::class, fn ($e) => $e->user->id === $user->id);
    }

    public function test_login_failed_event_is_dispatched_on_wrong_password(): void
    {
        Event::fake([LoginFailed::class]);

        $user = User::factory()->create([
            'email' => 'fail@example.com',
            'password' => Hash::make('password123'),
            'available' => true,
        ]);

        $this->from(route('auth.login'))
            ->post(route('auth.login.post'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        Event::assertDispatched(LoginFailed::class);
    }

    public function test_login_attempt_is_recorded_on_success(): void
    {
        $user = User::factory()->create([
            'email' => 'record@example.com',
            'password' => Hash::make('password123'),
            'available' => true,
        ]);

        $this->post(route('auth.login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('login_attempts', [
            'user_id' => $user->id,
            'status' => LoginAttempt::STATUS_SUCCESS,
        ]);
    }
}
