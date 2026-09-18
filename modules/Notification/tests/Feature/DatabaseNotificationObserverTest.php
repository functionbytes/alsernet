<?php

namespace Modules\Notification\Tests\Feature;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Notification\Events\NewNotificationEvent;
use Modules\Notification\Tests\TestCase;

class DatabaseNotificationObserverTest extends TestCase
{
    private function databaseNotification(): Notification
    {
        return new class extends Notification
        {
            public function via(mixed $notifiable): array
            {
                return ['database'];
            }

            public function toArray(mixed $notifiable): array
            {
                return ['title' => 'Título', 'message' => 'Mensaje', 'color' => 'success'];
            }
        };
    }

    public function test_creating_notification_broadcasts_event(): void
    {
        Event::fake([NewNotificationEvent::class]);

        $user = User::factory()->create();
        $user->notify($this->databaseNotification());

        Event::assertDispatched(
            NewNotificationEvent::class,
            fn (NewNotificationEvent $event) => $event->userId === $user->id
                && $event->title === 'Título'
                && $event->type === 'success',
        );
    }

    public function test_creating_notification_clears_unread_count_cache(): void
    {
        $user = User::factory()->create();
        Cache::put(User::unreadCountCacheKey($user->id), 99, now()->addHour());

        $user->notify($this->databaseNotification());

        $this->assertFalse(Cache::has(User::unreadCountCacheKey($user->id)));
    }
}
