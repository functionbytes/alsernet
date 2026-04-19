<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Auth\Models\EmailChangeToken;
use Modules\Auth\Notifications\EmailChangeVerificationNotification;
use Modules\Auth\Tests\AuthTestCase;

class EmailChangeTest extends AuthTestCase
{
    public function test_request_email_change_requires_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('mypassword'),
            'available' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.auth.email.change.request'), [
                'new_email' => 'new-address@example.com',
                'current_password' => 'wrong-password',
            ])
            ->assertStatus(422);
    }

    public function test_request_email_change_sends_verification_to_new_address(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('mypassword'),
            'available' => true,
        ]);

        $newEmail = 'fresh-'.Str::random(6).'@example.com';

        $this->actingAs($user)
            ->postJson(route('settings.auth.email.change.request'), [
                'new_email' => $newEmail,
                'current_password' => 'mypassword',
            ])
            ->assertOk();

        // On-demand notification routed to the new email
        Notification::assertSentOnDemand(
            EmailChangeVerificationNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $newEmail
        );
        $this->assertDatabaseHas('email_change_tokens', [
            'user_id' => $user->id,
            'new_email' => $newEmail,
        ]);
    }

    public function test_confirming_token_updates_email(): void
    {
        $user = User::factory()->create(['available' => true]);
        $newEmail = 'updated-'.Str::random(6).'@example.com';
        $plain = 'token-'.Str::random(20);

        EmailChangeToken::create([
            'user_id' => $user->id,
            'new_email' => $newEmail,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addHour(),
            'created_at' => now(),
        ]);

        $this->get(route('auth.email-change.confirm', ['token' => $plain]))
            ->assertRedirect(route('auth.login'));

        $user->refresh();
        $this->assertSame($newEmail, $user->email);
    }

    public function test_new_email_must_be_different_from_current(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('pwd'),
            'available' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.auth.email.change.request'), [
                'new_email' => $user->email,
                'current_password' => 'pwd',
            ])
            ->assertStatus(422);
    }
}
