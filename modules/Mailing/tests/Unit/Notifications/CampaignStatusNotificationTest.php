<?php

namespace Modules\Mailing\Tests\Unit\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Notifications\CampaignStatusNotification;
use Tests\TestCase;

class CampaignStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_sent_when_campaign_status_changes(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['name' => 'Test Campaign']);

        $user->notify(new CampaignStatusNotification($campaign, 'completed'));

        Notification::assertSentTo(
            $user,
            CampaignStatusNotification::class,
            function ($notification, $channels) use ($campaign) {
                return $notification->campaign->id === $campaign->id
                    && in_array('mail', $channels)
                    && in_array('database', $channels);
            }
        );
    }

    public function test_notification_mail_contains_correct_subject(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['name' => 'Newsletter Campaign']);

        $notification = new CampaignStatusNotification($campaign, 'completed');
        $mailMessage = $notification->toMail($user);

        $this->assertEquals(
            'Campaign completed: Newsletter Campaign',
            $mailMessage->subject
        );
    }

    public function test_notification_mail_contains_campaign_link(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $notification = new CampaignStatusNotification($campaign, 'completed');
        $mailMessage = $notification->toMail($user);

        $this->assertNotEmpty($mailMessage->actionUrl);
        $this->assertStringContainsString('campaigns', $mailMessage->actionUrl);
    }

    public function test_notification_database_payload_contains_required_fields(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'name' => 'Test Campaign',
            'uid' => 'test-uid-123',
        ]);

        $notification = new CampaignStatusNotification($campaign, 'completed', 'Campaign sent successfully');
        $payload = $notification->toArray($user);

        $this->assertArrayHasKey('campaign_id', $payload);
        $this->assertArrayHasKey('campaign_name', $payload);
        $this->assertArrayHasKey('campaign_uid', $payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('message', $payload);
        $this->assertArrayHasKey('timestamp', $payload);

        $this->assertEquals($campaign->id, $payload['campaign_id']);
        $this->assertEquals('Test Campaign', $payload['campaign_name']);
        $this->assertEquals('test-uid-123', $payload['campaign_uid']);
        $this->assertEquals('completed', $payload['status']);
        $this->assertEquals('Campaign sent successfully', $payload['message']);
    }

    public function test_notification_includes_custom_message_when_provided(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $campaign = Campaign::factory()->create();

        $notification = new CampaignStatusNotification(
            $campaign,
            'paused',
            'Campaign paused due to high bounce rate'
        );

        $mailMessage = $notification->toMail($user);

        // Convert mail message to string representation to check content
        $this->assertStringContainsString('John Doe', $mailMessage->greeting);
        $this->assertCount(3, $mailMessage->introLines); // greeting + status + custom message
    }

    public function test_notification_implements_should_queue(): void
    {
        $campaign = Campaign::factory()->create();
        $notification = new CampaignStatusNotification($campaign, 'completed');

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
    }
}
