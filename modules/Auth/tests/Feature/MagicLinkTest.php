<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Auth\Models\MagicLoginToken;
use Modules\Auth\Notifications\MagicLinkNotification;
use Modules\Auth\Tests\AuthTestCase;

class MagicLinkTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureMagicLinkTable();
    }

    private function ensureMagicLinkTable(): void
    {
        if (! Schema::hasTable('magic_login_tokens')) {
            DB::statement('CREATE TABLE magic_login_tokens (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                token_hash VARCHAR(64) NOT NULL UNIQUE,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(500) NULL,
                expires_at TIMESTAMP NOT NULL,
                used_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                INDEX (user_id, used_at)
            ) ENGINE=InnoDB');
        }
    }

    public function test_magic_link_request_dispatches_notification_to_valid_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'magic@example.com',
            'available' => true,
        ]);

        $this->post(route('auth.magic-link.request'), ['email' => 'magic@example.com']);

        Notification::assertSentTo($user, MagicLinkNotification::class);
        $this->assertDatabaseHas('magic_login_tokens', ['user_id' => $user->id]);
    }

    public function test_magic_link_request_does_not_leak_whether_email_exists(): void
    {
        Notification::fake();

        $response = $this->post(route('auth.magic-link.request'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_consuming_valid_token_logs_user_in(): void
    {
        $user = User::factory()->create(['available' => true]);
        $plain = 'test-token-'.Str::random(16);

        MagicLoginToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
        ]);

        $this->get(route('auth.magic-link.consume', ['token' => $plain]))
            ->assertRedirect();

        if (! $user->hasTwoFactorEnabled()) {
            $this->assertAuthenticatedAs($user);
        }
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create(['available' => true]);
        $plain = 'expired-token';

        MagicLoginToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->subMinute(),
            'created_at' => now()->subMinutes(30),
        ]);

        $this->get(route('auth.magic-link.consume', ['token' => $plain]))
            ->assertRedirect(route('auth.login'));

        $this->assertGuest();
    }

    public function test_used_token_cannot_be_reused(): void
    {
        $user = User::factory()->create(['available' => true]);
        $plain = 'used-token';

        MagicLoginToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(15),
            'used_at' => now(),
            'created_at' => now(),
        ]);

        $this->get(route('auth.magic-link.consume', ['token' => $plain]))
            ->assertRedirect(route('auth.login'));

        $this->assertGuest();
    }
}
