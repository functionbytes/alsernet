<?php

namespace Modules\Notification\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Notification\Tests\TestCase;

class CleanupNotificationsCommandTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function seedNotification(bool $isRead, string $createdAt, ?string $readAt = null): string
    {
        $id = (string) Str::uuid();

        \DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => User::factory()->create()->id,
            'data' => json_encode(['title' => 'Test', 'message' => 'Hello']),
            'read_at' => $isRead ? ($readAt ?? now()) : null,
            'created_at' => $createdAt,
            'updated_at' => now(),
        ]);

        return $id;
    }

    // -------------------------------------------------------------------------
    // Read notifications cleanup
    // -------------------------------------------------------------------------

    public function test_deletes_old_read_notifications(): void
    {
        $id = $this->seedNotification(isRead: true, createdAt: now()->subDays(31)->toDateTimeString());

        $this->artisan('notifications:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['id' => $id]);
    }

    public function test_does_not_delete_recent_read_notifications(): void
    {
        $id = $this->seedNotification(isRead: true, createdAt: now()->subDays(10)->toDateTimeString());

        $this->artisan('notifications:cleanup')->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['id' => $id]);
    }

    // -------------------------------------------------------------------------
    // Unread notifications cleanup
    // -------------------------------------------------------------------------

    public function test_deletes_very_old_unread_notifications(): void
    {
        $id = $this->seedNotification(isRead: false, createdAt: now()->subDays(91)->toDateTimeString());

        $this->artisan('notifications:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['id' => $id]);
    }

    public function test_does_not_delete_recent_unread_notifications(): void
    {
        $id = $this->seedNotification(isRead: false, createdAt: now()->subDays(30)->toDateTimeString());

        $this->artisan('notifications:cleanup')->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['id' => $id]);
    }

    // -------------------------------------------------------------------------
    // --read-only flag
    // -------------------------------------------------------------------------

    public function test_read_only_flag_skips_unread_deletion(): void
    {
        $unreadId = $this->seedNotification(isRead: false, createdAt: now()->subDays(91)->toDateTimeString());

        $this->artisan('notifications:cleanup', ['--read-only' => true])->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['id' => $unreadId]);
    }

    // -------------------------------------------------------------------------
    // --days option
    // -------------------------------------------------------------------------

    public function test_days_option_is_respected(): void
    {
        $oldId = $this->seedNotification(isRead: true, createdAt: now()->subDays(11)->toDateTimeString());
        $recentId = $this->seedNotification(isRead: true, createdAt: now()->subDays(5)->toDateTimeString());

        $this->artisan('notifications:cleanup', ['--days' => '10'])->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['id' => $oldId]);
        $this->assertDatabaseHas('notifications', ['id' => $recentId]);
    }

    // -------------------------------------------------------------------------
    // Output
    // -------------------------------------------------------------------------

    public function test_command_outputs_count(): void
    {
        $readId = $this->seedNotification(isRead: true, createdAt: now()->subDays(31)->toDateTimeString());
        $unreadId = $this->seedNotification(isRead: false, createdAt: now()->subDays(91)->toDateTimeString());

        $this->artisan('notifications:cleanup')
            ->assertSuccessful()
            ->expectsOutputToContain('Deleted');

        $this->assertDatabaseMissing('notifications', ['id' => $readId]);
        $this->assertDatabaseMissing('notifications', ['id' => $unreadId]);
    }
}
