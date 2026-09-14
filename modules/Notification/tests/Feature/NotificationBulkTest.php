<?php

namespace Modules\Notification\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Notification\Tests\TestCase;

class NotificationBulkTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function seedNotification(User $user, bool $isRead = false): string
    {
        $id = (string) Str::uuid();

        \DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Test', 'message' => 'Hello']),
            'read_at' => $isRead ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    // -------------------------------------------------------------------------
    // Authentication guard
    // -------------------------------------------------------------------------

    public function test_mark_all_read_requires_auth(): void
    {
        $this->post(route('notifications.markAllAsRead'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_notification_index_requires_auth(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_bulk_delete_requires_auth(): void
    {
        $this->delete(route('notifications.bulk-destroy'), ['ids' => []])
            ->assertRedirect(route('auth.login'));
    }

    public function test_destroy_all_requires_auth(): void
    {
        $this->delete(route('notifications.destroy-all'))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // Mark all as read
    // -------------------------------------------------------------------------

    public function test_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        $this->seedNotification($user);
        $this->seedNotification($user);

        $this->actingAs($user)
            ->postJson(route('notifications.markAllAsRead'))
            ->assertOk()
            ->assertJson(['message' => 'All notifications marked as read', 'unread_count' => 0]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Bulk delete
    // -------------------------------------------------------------------------

    public function test_bulk_delete_removes_selected_notifications(): void
    {
        $user = User::factory()->create();

        $id1 = $this->seedNotification($user);
        $id2 = $this->seedNotification($user);
        $id3 = $this->seedNotification($user);

        $this->actingAs($user)
            ->deleteJson(route('notifications.bulk-destroy'), ['ids' => [$id1, $id2]])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['id' => $id1]);
        $this->assertDatabaseMissing('notifications', ['id' => $id2]);
        $this->assertDatabaseHas('notifications', ['id' => $id3]);
    }

    public function test_bulk_delete_requires_ids(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('notifications.bulk-destroy'), [])
            ->assertUnprocessable();
    }

    public function test_bulk_delete_only_removes_own_notifications(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $otherId = $this->seedNotification($other);

        // Try to delete another user's notification using its ID
        $this->actingAs($owner)
            ->deleteJson(route('notifications.bulk-destroy'), ['ids' => [$otherId]])
            ->assertOk();

        // The other user's notification must still exist
        $this->assertDatabaseHas('notifications', ['id' => $otherId]);
    }

    // -------------------------------------------------------------------------
    // Destroy all
    // -------------------------------------------------------------------------

    public function test_destroy_all_removes_all_user_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->seedNotification($user);
        $this->seedNotification($user);
        $otherId = $this->seedNotification($other);

        $this->actingAs($user)
            ->deleteJson(route('notifications.destroy-all'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $user->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherId]);
    }

    // -------------------------------------------------------------------------
    // Notification center view
    // -------------------------------------------------------------------------

    public function test_notification_center_shows_correct_unread_count(): void
    {
        $user = User::factory()->create();

        // 3 unread, 1 read
        $this->seedNotification($user);
        $this->seedNotification($user);
        $this->seedNotification($user);
        $this->seedNotification($user, isRead: true);

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk()
            ->assertViewHas('unreadCount', 3);
    }
}
