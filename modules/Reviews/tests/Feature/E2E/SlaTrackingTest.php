<?php

namespace Modules\Reviews\Tests\Feature\E2E;

use Illuminate\Support\Facades\Notification;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Notifications\SlaBreachNotification;
use Modules\Reviews\Tests\TestCase;

class SlaTrackingTest extends TestCase
{
    // -------------------------------------------------------------------------
    // SLA breach notification sent
    // -------------------------------------------------------------------------

    public function test_sla_breach_notification_sent_for_overdue_review(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $location->update(['sla_hours' => 2]);

        // Review from 3 hours ago with no reply
        Review::factory()->for($location, 'location')->create([
            'review_time' => now()->subHours(3),
            'google_reply_text' => null,
            'sla_alerted_at' => null,
        ]);

        $this->artisan('reviews:check-sla')->assertExitCode(0);

        Notification::assertSentTo($user, SlaBreachNotification::class);
    }

    public function test_sla_check_marks_review_as_alerted(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $location->update(['sla_hours' => 1]);

        $review = Review::factory()->for($location, 'location')->create([
            'review_time' => now()->subHours(2),
            'google_reply_text' => null,
            'sla_alerted_at' => null,
        ]);

        $this->artisan('reviews:check-sla');

        $this->assertNotNull($review->fresh()->sla_alerted_at);
    }

    public function test_sla_check_is_idempotent_does_not_resend_notification(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $location->update(['sla_hours' => 1]);

        // Review already alerted
        Review::factory()->for($location, 'location')->create([
            'review_time' => now()->subHours(2),
            'google_reply_text' => null,
            'sla_alerted_at' => now()->subMinutes(30),
        ]);

        $this->artisan('reviews:check-sla');

        Notification::assertNothingSent();
    }

    public function test_sla_check_does_not_alert_replied_reviews(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $location->update(['sla_hours' => 1]);

        Review::factory()->withGoogleReply()->for($location, 'location')->create([
            'review_time' => now()->subHours(3),
            'sla_alerted_at' => null,
        ]);

        $this->artisan('reviews:check-sla');

        Notification::assertNothingSent();
    }

    public function test_sla_check_does_not_alert_within_deadline(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $location->update(['sla_hours' => 24]);

        // Review from 3 hours ago — still within 24h SLA window
        Review::factory()->for($location, 'location')->create([
            'review_time' => now()->subHours(3),
            'google_reply_text' => null,
            'sla_alerted_at' => null,
        ]);

        $this->artisan('reviews:check-sla');

        Notification::assertNothingSent();
    }

    public function test_sla_check_sends_notification_to_location_owner(): void
    {
        Notification::fake();

        $owner = $this->createUser();
        $connection = $this->createConnection($owner);
        $location = $this->createLocation($connection);
        $location->update(['sla_hours' => 2]);

        // Create another user who is NOT the owner
        $otherUser = $this->createUser();

        Review::factory()->for($location, 'location')->create([
            'review_time' => now()->subHours(3),
            'google_reply_text' => null,
            'sla_alerted_at' => null,
        ]);

        $this->artisan('reviews:check-sla');

        // Notification sent to the owner, not the other user
        Notification::assertSentTo($owner, SlaBreachNotification::class);
        Notification::assertNotSentTo($otherUser, SlaBreachNotification::class);
    }

    public function test_sla_check_sends_multiple_notifications_for_multiple_breaches(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $location->update(['sla_hours' => 1]);

        // 3 overdue reviews
        Review::factory()->for($location, 'location')->count(3)->create([
            'review_time' => now()->subHours(2),
            'google_reply_text' => null,
            'sla_alerted_at' => null,
        ]);

        $this->artisan('reviews:check-sla');

        Notification::assertSentToTimes($user, SlaBreachNotification::class, 3);
    }

    // -------------------------------------------------------------------------
    // Command output
    // -------------------------------------------------------------------------

    public function test_sla_check_command_reports_zero_when_no_breaches(): void
    {
        $this->artisan('reviews:check-sla')
            ->expectsOutputToContain('No hay resenas')
            ->assertExitCode(0);
    }

    public function test_sla_check_command_succeeds_when_notifications_sent(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $location->update(['sla_hours' => 1]);

        Review::factory()->for($location, 'location')->create([
            'review_time' => now()->subHours(2),
            'google_reply_text' => null,
            'sla_alerted_at' => null,
        ]);

        $this->artisan('reviews:check-sla')->assertExitCode(0);
    }
}
