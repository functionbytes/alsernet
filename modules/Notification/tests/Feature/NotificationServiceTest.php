<?php

namespace Modules\Notification\Tests\Feature;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Modules\Notification\Models\NotificationPreference;
use Modules\Notification\Services\NotificationService;
use Modules\Notification\Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    private NotificationService $service;

    private Notification $notification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NotificationService::class);

        $this->notification = new class extends Notification
        {
            public function via(mixed $notifiable): array
            {
                return ['database'];
            }

            public function toArray(mixed $notifiable): array
            {
                return ['message' => 'test'];
            }
        };
    }

    // -------------------------------------------------------------------------
    // sendToUser — database channel
    // -------------------------------------------------------------------------

    public function test_send_to_user_database_channel_calls_notify(): void
    {
        NotificationFacade::fake();

        $user = User::factory()->create();

        $result = $this->service->sendToUser($user, $this->notification, ['database']);

        $this->assertSame(['database' => true], $result);
        NotificationFacade::assertSentTo($user, get_class($this->notification));
    }

    public function test_send_to_user_returns_disabled_when_preference_off(): void
    {
        NotificationFacade::fake();

        $user = User::factory()->create();

        NotificationPreference::toggle(
            $user->id,
            'database',
            get_class($this->notification),
            false
        );

        $result = $this->service->sendToUser($user, $this->notification, ['database']);

        $this->assertSame(['database' => 'disabled'], $result);
        NotificationFacade::assertNotSentTo($user, get_class($this->notification));
    }

    public function test_send_to_user_returns_false_on_exception(): void
    {
        $user = $this->createMock(User::class);
        $user->method('canReceiveNotification')->willReturn(true);
        $user->method('notify')->willThrowException(new \Exception('send failed'));

        $result = $this->service->sendToUser($user, $this->notification, ['database']);

        $this->assertSame(['database' => false], $result);
    }

    // -------------------------------------------------------------------------
    // sendToUser — multiple channels
    // -------------------------------------------------------------------------

    public function test_send_to_user_multiple_channels_returns_result_per_channel(): void
    {
        NotificationFacade::fake();

        $user = User::factory()->create();

        $result = $this->service->sendToUser($user, $this->notification, ['database', 'mail']);

        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('mail', $result);
        $this->assertTrue($result['database']);
        $this->assertTrue($result['mail']);
    }

    // -------------------------------------------------------------------------
    // sendToUsers
    // -------------------------------------------------------------------------

    public function test_send_to_users_returns_results_indexed_by_user_id(): void
    {
        NotificationFacade::fake();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $result = $this->service->sendToUsers([$user1, $user2], $this->notification, ['database']);

        $this->assertArrayHasKey($user1->id, $result);
        $this->assertArrayHasKey($user2->id, $result);
        $this->assertSame(['database' => true], $result[$user1->id]);
        $this->assertSame(['database' => true], $result[$user2->id]);
    }
}
