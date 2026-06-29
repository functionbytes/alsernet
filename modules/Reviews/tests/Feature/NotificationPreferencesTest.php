<?php

namespace Modules\Reviews\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Reviews\Models\UserNotificationPreference;
use Modules\Reviews\Services\NotificationService;
use Modules\Reviews\Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    protected User $user;

    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser(['reviews.settings.manage']);
        $this->service = app(NotificationService::class);
    }

    public function test_can_initialize_default_preferences(): void
    {
        $this->service->initializeDefaultPreferences($this->user);

        $this->assertDatabaseCount('user_notification_preferences', 7);

        $preference = UserNotificationPreference::query()
            ->where('user_id', $this->user->id)
            ->where('notification_type', NotificationService::TYPE_NEGATIVE_REVIEW)
            ->first();

        $this->assertTrue($preference->is_enabled);
        $this->assertEquals(['mail', 'database'], $preference->channels);
    }

    public function test_can_access_notification_preferences_page(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('settings.reviews.notifications.index'));

        $response->assertOk();
        $response->assertViewIs('reviews::settings.notifications');
    }

    public function test_can_update_notification_preferences(): void
    {
        $this->service->initializeDefaultPreferences($this->user);
        $this->actingAs($this->user);

        $response = $this->post(route('settings.reviews.notifications.update'), [
            'preferences' => [
                [
                    'notification_type' => NotificationService::TYPE_NEGATIVE_REVIEW,
                    'is_enabled' => false,
                    'channels' => ['database'],
                    'filters' => null,
                ],
            ],
        ]);

        $response->assertRedirect(route('settings.reviews.notifications.index'));
        $response->assertSessionHas('success');

        $preference = UserNotificationPreference::query()
            ->where('user_id', $this->user->id)
            ->where('notification_type', NotificationService::TYPE_NEGATIVE_REVIEW)
            ->first();

        $this->assertFalse($preference->is_enabled);
        $this->assertEquals(['database'], $preference->channels);
    }

    public function test_disabled_notification_is_not_sent(): void
    {
        Notification::fake();

        $this->service->initializeDefaultPreferences($this->user);

        UserNotificationPreference::query()
            ->where('user_id', $this->user->id)
            ->where('notification_type', NotificationService::TYPE_NEGATIVE_REVIEW)
            ->update(['is_enabled' => false]);

        $shouldNotify = $this->service->checkPreferences(
            $this->user,
            NotificationService::TYPE_NEGATIVE_REVIEW
        );

        $this->assertFalse($shouldNotify);
    }

    public function test_enabled_notification_is_sent(): void
    {
        Notification::fake();

        $this->service->initializeDefaultPreferences($this->user);

        $shouldNotify = $this->service->checkPreferences(
            $this->user,
            NotificationService::TYPE_NEGATIVE_REVIEW
        );

        $this->assertTrue($shouldNotify);
    }

    public function test_notification_respects_rating_filters(): void
    {
        $this->service->initializeDefaultPreferences($this->user);

        UserNotificationPreference::query()
            ->where('user_id', $this->user->id)
            ->where('notification_type', NotificationService::TYPE_NEGATIVE_REVIEW)
            ->update([
                'filters' => [
                    'min_rating' => 1,
                    'max_rating' => 2,
                ],
            ]);

        $shouldNotifyForTwoStars = $this->service->checkPreferences(
            $this->user,
            NotificationService::TYPE_NEGATIVE_REVIEW,
            ['rating' => 2]
        );

        $shouldNotifyForFourStars = $this->service->checkPreferences(
            $this->user,
            NotificationService::TYPE_NEGATIVE_REVIEW,
            ['rating' => 4]
        );

        $this->assertTrue($shouldNotifyForTwoStars);
        $this->assertFalse($shouldNotifyForFourStars);
    }

    public function test_get_enabled_channels_returns_correct_channels(): void
    {
        $this->service->initializeDefaultPreferences($this->user);

        $channels = $this->service->getEnabledChannels(
            $this->user,
            NotificationService::TYPE_NEGATIVE_REVIEW
        );

        $this->assertEquals(['mail', 'database'], $channels);

        UserNotificationPreference::query()
            ->where('user_id', $this->user->id)
            ->where('notification_type', NotificationService::TYPE_NEGATIVE_REVIEW)
            ->update(['channels' => ['database']]);

        $channels = $this->service->getEnabledChannels(
            $this->user,
            NotificationService::TYPE_NEGATIVE_REVIEW
        );

        $this->assertEquals(['database'], $channels);
    }

    public function test_notification_preference_has_correct_relationship(): void
    {
        $this->service->initializeDefaultPreferences($this->user);

        $preference = UserNotificationPreference::query()
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertInstanceOf(User::class, $preference->user);
        $this->assertEquals($this->user->id, $preference->user->id);
    }
}
