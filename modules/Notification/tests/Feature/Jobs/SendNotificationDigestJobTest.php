<?php

namespace Modules\Notification\Tests\Feature\Jobs;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Notification\Jobs\SendNotificationDigestJob;
use Modules\Notification\Notifications\NotificationDigestNotification;
use Modules\Notification\Tests\TestCase;

class SendNotificationDigestJobTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function seedUnreadNotification(User $user, string $createdAt): void
    {
        \DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Test', 'message' => 'Hello']),
            'read_at' => null,
            'created_at' => $createdAt,
            'updated_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // handle — sends digest when unread notifications exist within 24h
    // -------------------------------------------------------------------------

    public function test_handle_sends_notification_when_user_has_unread(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->seedUnreadNotification($user, now()->subHours(2)->toDateTimeString());

        (new SendNotificationDigestJob($user))->handle();

        Notification::assertSentTo($user, NotificationDigestNotification::class);
    }

    // -------------------------------------------------------------------------
    // handle — skips when no unread notifications within 24h
    // -------------------------------------------------------------------------

    public function test_handle_skips_when_no_unread_notifications(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        (new SendNotificationDigestJob($user))->handle();

        Notification::assertNotSentTo($user, NotificationDigestNotification::class);
    }

    public function test_handle_skips_when_all_notifications_are_older_than_24h(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->seedUnreadNotification($user, now()->subDays(2)->toDateTimeString());

        (new SendNotificationDigestJob($user))->handle();

        Notification::assertNotSentTo($user, NotificationDigestNotification::class);
    }

    // -------------------------------------------------------------------------
    // failed — logs error with user context
    // -------------------------------------------------------------------------

    public function test_failed_logs_error(): void
    {
        $user = User::factory()->create();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return str_contains($message, 'SendNotificationDigestJob')
                    && $context['user_id'] === $user->id
                    && $context['error'] === 'test error';
            });

        $job = new SendNotificationDigestJob($user);
        $job->failed(new \Exception('test error'));
    }
}
