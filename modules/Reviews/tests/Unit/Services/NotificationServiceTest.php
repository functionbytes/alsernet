<?php

namespace Modules\Reviews\Tests\Unit\Services;

use App\Models\User;
use Modules\Reviews\Models\UserNotificationPreference;
use Modules\Reviews\Services\NotificationService;
use Modules\Reviews\Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new NotificationService;
    }

    public function test_check_preferences_returns_true_when_no_preference_exists(): void
    {
        $user = User::factory()->create();

        $result = $this->service->checkPreferences($user, NotificationService::TYPE_NEW_REVIEW);

        $this->assertTrue($result);
    }

    public function test_check_preferences_returns_false_when_preference_is_disabled(): void
    {
        $user = User::factory()->create();

        UserNotificationPreference::create([
            'user_id' => $user->id,
            'notification_type' => NotificationService::TYPE_NEW_REVIEW,
            'is_enabled' => false,
            'channels' => [NotificationService::CHANNEL_DATABASE],
            'filters' => null,
        ]);

        $result = $this->service->checkPreferences($user, NotificationService::TYPE_NEW_REVIEW);

        $this->assertFalse($result);
    }

    public function test_check_preferences_respects_min_rating_filter(): void
    {
        $user = User::factory()->create();

        UserNotificationPreference::create([
            'user_id' => $user->id,
            'notification_type' => NotificationService::TYPE_NEW_REVIEW,
            'is_enabled' => true,
            'channels' => [NotificationService::CHANNEL_DATABASE],
            'filters' => ['min_rating' => 4],
        ]);

        $this->assertFalse(
            $this->service->checkPreferences($user, NotificationService::TYPE_NEW_REVIEW, ['rating' => 3])
        );

        $this->assertTrue(
            $this->service->checkPreferences($user, NotificationService::TYPE_NEW_REVIEW, ['rating' => 4])
        );

        $this->assertTrue(
            $this->service->checkPreferences($user, NotificationService::TYPE_NEW_REVIEW, ['rating' => 5])
        );
    }

    public function test_check_preferences_respects_max_rating_filter(): void
    {
        $user = User::factory()->create();

        UserNotificationPreference::create([
            'user_id' => $user->id,
            'notification_type' => NotificationService::TYPE_NEW_REVIEW,
            'is_enabled' => true,
            'channels' => [NotificationService::CHANNEL_DATABASE],
            'filters' => ['max_rating' => 3],
        ]);

        $this->assertTrue(
            $this->service->checkPreferences($user, NotificationService::TYPE_NEW_REVIEW, ['rating' => 2])
        );

        $this->assertTrue(
            $this->service->checkPreferences($user, NotificationService::TYPE_NEW_REVIEW, ['rating' => 3])
        );

        $this->assertFalse(
            $this->service->checkPreferences($user, NotificationService::TYPE_NEW_REVIEW, ['rating' => 4])
        );
    }

    public function test_get_enabled_channels_returns_saved_channels_for_user(): void
    {
        $user = User::factory()->create();

        UserNotificationPreference::create([
            'user_id' => $user->id,
            'notification_type' => NotificationService::TYPE_NEW_REVIEW,
            'is_enabled' => true,
            'channels' => [NotificationService::CHANNEL_MAIL, NotificationService::CHANNEL_SLACK],
            'filters' => null,
        ]);

        $channels = $this->service->getEnabledChannels($user, NotificationService::TYPE_NEW_REVIEW);

        $this->assertSame([NotificationService::CHANNEL_MAIL, NotificationService::CHANNEL_SLACK], $channels);
    }

    public function test_get_enabled_channels_returns_defaults_when_no_preference_exists(): void
    {
        $user = User::factory()->create();

        $channels = $this->service->getEnabledChannels($user, NotificationService::TYPE_NEW_REVIEW);

        $this->assertNotEmpty($channels);
        $this->assertIsArray($channels);
    }

    public function test_initialize_default_preferences_creates_record_for_every_notification_type(): void
    {
        $user = User::factory()->create();

        $this->service->initializeDefaultPreferences($user);

        $expectedTypes = array_keys(NotificationService::getAvailableTypes());

        foreach ($expectedTypes as $type) {
            $this->assertDatabaseHas('user_notification_preferences', [
                'user_id' => $user->id,
                'notification_type' => $type,
            ]);
        }
    }

    public function test_initialize_default_preferences_is_idempotent(): void
    {
        $user = User::factory()->create();

        $this->service->initializeDefaultPreferences($user);
        $this->service->initializeDefaultPreferences($user);

        $count = UserNotificationPreference::where('user_id', $user->id)->count();

        $this->assertSame(count(NotificationService::getAvailableTypes()), $count);
    }
}
