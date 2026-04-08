<?php

namespace Modules\Notification\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Notification\Tests\TestCase;

class NotificationApiTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function seedNotification(User $user, bool $isRead = false, ?string $createdAt = null): string
    {
        $id = (string) Str::uuid();

        \DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Test', 'message' => 'Hello']),
            'read_at' => $isRead ? now() : null,
            'created_at' => $createdAt ?? now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    // -------------------------------------------------------------------------
    // Authentication guard
    // -------------------------------------------------------------------------

    public function test_index_requires_auth(): void
    {
        $this->get(route('api.notifications.index'))
            ->assertRedirect();
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_index_returns_notifications_json(): void
    {
        $user = User::factory()->create();

        $this->seedNotification($user);
        $this->seedNotification($user, isRead: true);

        $this->actingAs($user)
            ->getJson(route('api.notifications.index'))
            ->assertOk()
            ->assertJsonStructure([
                'notifications',
                'unread_count',
            ])
            ->assertJsonCount(2, 'notifications')
            ->assertJsonPath('unread_count', 1);
    }

    public function test_index_filters_by_unread(): void
    {
        $user = User::factory()->create();

        $this->seedNotification($user);
        $this->seedNotification($user, isRead: true);

        $this->actingAs($user)
            ->getJson(route('api.notifications.index', ['unread' => 1]))
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.is_read', false);
    }

    // -------------------------------------------------------------------------
    // Stats
    // -------------------------------------------------------------------------

    public function test_stats_returns_correct_counts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        // 2 unread today, 1 read today, 1 unread different user
        $this->seedNotification($user);
        $this->seedNotification($user);
        $this->seedNotification($user, isRead: true);
        $this->seedNotification($other);

        $this->actingAs($user)
            ->getJson(route('api.notifications.stats'))
            ->assertOk()
            ->assertJsonStructure(['total', 'unread', 'read', 'today', 'this_week'])
            ->assertJsonPath('total', 3)
            ->assertJsonPath('unread', 2)
            ->assertJsonPath('read', 1)
            ->assertJsonPath('this_week', 3);
    }

    // -------------------------------------------------------------------------
    // Mark as read
    // -------------------------------------------------------------------------

    public function test_mark_as_read_marks_notification(): void
    {
        $user = User::factory()->create();
        $id = $this->seedNotification($user);

        $this->actingAs($user)
            ->postJson(route('api.notifications.read', $id))
            ->assertOk()
            ->assertJsonStructure(['message', 'unread_count'])
            ->assertJsonPath('unread_count', 0);

        $this->assertDatabaseMissing('notifications', [
            'id' => $id,
            'read_at' => null,
        ]);
    }

    public function test_cannot_read_other_users_notification(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $otherId = $this->seedNotification($other);

        $this->actingAs($user)
            ->postJson(route('api.notifications.read', $otherId))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // Mark all as read
    // -------------------------------------------------------------------------

    public function test_mark_all_read_marks_all(): void
    {
        $user = User::factory()->create();

        $this->seedNotification($user);
        $this->seedNotification($user);

        $this->actingAs($user)
            ->postJson(route('api.notifications.mark-all-read'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_notification(): void
    {
        $user = User::factory()->create();
        $id = $this->seedNotification($user);

        $this->actingAs($user)
            ->deleteJson(route('api.notifications.destroy', $id))
            ->assertOk()
            ->assertJsonStructure(['message', 'unread_count']);

        $this->assertDatabaseMissing('notifications', ['id' => $id]);
    }

    public function test_cannot_destroy_other_users_notification(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $otherId = $this->seedNotification($other);

        $this->actingAs($user)
            ->deleteJson(route('api.notifications.destroy', $otherId))
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', ['id' => $otherId]);
    }
}
