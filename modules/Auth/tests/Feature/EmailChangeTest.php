<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Auth\Models\EmailChangeToken;
use Modules\Auth\Notifications\EmailChangeVerificationNotification;
use Modules\Auth\Tests\AuthTestCase;

class EmailChangeTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureEmailChangeTable();
    }

    private function ensureEmailChangeTable(): void
    {
        if (! Schema::hasTable('email_change_tokens')) {
            DB::statement('CREATE TABLE email_change_tokens (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                new_email VARCHAR(255) NOT NULL,
                token_hash VARCHAR(64) NOT NULL UNIQUE,
                expires_at TIMESTAMP NOT NULL,
                confirmed_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                INDEX (user_id, confirmed_at)
            ) ENGINE=InnoDB');
        }
    }

    public function test_request_email_change_requires_password(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => Hash::make('mypassword'),
            'available' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.auth.email.change.request'), [
                'new_email' => 'new@example.com',
                'current_password' => 'wrong-password',
            ])
            ->assertStatus(422);
    }

    public function test_request_email_change_sends_verification_to_new_address(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'old2@example.com',
            'password' => Hash::make('mypassword'),
            'available' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.auth.email.change.request'), [
                'new_email' => 'new2@example.com',
                'current_password' => 'mypassword',
            ])
            ->assertOk();

        Notification::assertSentTo($user, EmailChangeVerificationNotification::class);
        $this->assertDatabaseHas('email_change_tokens', [
            'user_id' => $user->id,
            'new_email' => 'new2@example.com',
        ]);
    }

    public function test_confirming_token_updates_email(): void
    {
        $user = User::factory()->create([
            'email' => 'original@example.com',
            'available' => true,
        ]);

        $plain = 'token-'.Str::random(20);

        EmailChangeToken::create([
            'user_id' => $user->id,
            'new_email' => 'updated@example.com',
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addHour(),
            'created_at' => now(),
        ]);

        $this->get(route('auth.email-change.confirm', ['token' => $plain]))
            ->assertRedirect(route('auth.login'));

        $user->refresh();
        $this->assertSame('updated@example.com', $user->email);
    }

    public function test_new_email_must_be_different_from_current(): void
    {
        $user = User::factory()->create([
            'email' => 'same@example.com',
            'password' => Hash::make('pwd'),
            'available' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.auth.email.change.request'), [
                'new_email' => 'same@example.com',
                'current_password' => 'pwd',
            ])
            ->assertStatus(422);
    }
}
