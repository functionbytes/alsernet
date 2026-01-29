<?php

namespace Modules\Mailing\Tests\Unit\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Mailing\Models\MailList;
use Modules\Mailing\Models\Subscriber;
use Modules\Mailing\Notifications\SubscriberNotification;
use Tests\TestCase;

class SubscriberNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_handles_subscribed_action(): void
    {
        $user = User::factory()->create(['name' => 'Admin User']);
        $mailList = MailList::factory()->create();
        $subscriber = Subscriber::factory()->create([
            'email' => 'newuser@example.com',
            'mail_list_id' => $mailList->id,
        ]);

        $notification = new SubscriberNotification($subscriber, 'subscribed');
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('New Subscriber Added', $mailMessage->subject);
        $this->assertStringContainsString('newuser@example.com', $mailMessage->introLines[0]);
    }

    public function test_notification_handles_unsubscribed_action(): void
    {
        $user = User::factory()->create();
        $subscriber = Subscriber::factory()->create(['email' => 'leaving@example.com']);

        $notification = new SubscriberNotification($subscriber, 'unsubscribed');
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('Subscriber Unsubscribed', $mailMessage->subject);
        $this->assertStringContainsString('leaving@example.com', $mailMessage->introLines[0]);
        $this->assertStringContainsString('unsubscribed', $mailMessage->introLines[0]);
    }

    public function test_notification_handles_bounced_action_with_reason(): void
    {
        $user = User::factory()->create();
        $subscriber = Subscriber::factory()->create(['email' => 'bounced@example.com']);

        $notification = new SubscriberNotification(
            $subscriber,
            'bounced',
            ['reason' => 'Mailbox full']
        );

        $mailMessage = $notification->toMail($user);

        $this->assertEquals('Email Bounce Detected', $mailMessage->subject);
        $this->assertStringContainsString('bounced@example.com', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Mailbox full', $mailMessage->introLines[0]);
    }

    public function test_notification_handles_spam_complaint(): void
    {
        $user = User::factory()->create();
        $subscriber = Subscriber::factory()->create(['email' => 'complainer@example.com']);

        $notification = new SubscriberNotification($subscriber, 'complained');
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('Spam Complaint Received', $mailMessage->subject);
        $this->assertStringContainsString('spam', strtolower($mailMessage->introLines[0]));
    }

    public function test_notification_database_payload_structure(): void
    {
        $user = User::factory()->create();
        $mailList = MailList::factory()->create();
        $subscriber = Subscriber::factory()->create([
            'email' => 'test@example.com',
            'mail_list_id' => $mailList->id,
        ]);

        $notification = new SubscriberNotification(
            $subscriber,
            'subscribed',
            ['source' => 'web_form']
        );

        $payload = $notification->toArray($user);

        $this->assertArrayHasKey('subscriber_id', $payload);
        $this->assertArrayHasKey('subscriber_email', $payload);
        $this->assertArrayHasKey('mail_list_id', $payload);
        $this->assertArrayHasKey('action', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('timestamp', $payload);

        $this->assertEquals('test@example.com', $payload['subscriber_email']);
        $this->assertEquals('subscribed', $payload['action']);
        $this->assertEquals(['source' => 'web_form'], $payload['data']);
    }

    public function test_notification_is_queued(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $subscriber = Subscriber::factory()->create();

        $user->notify(new SubscriberNotification($subscriber, 'subscribed'));

        Notification::assertSentTo($user, SubscriberNotification::class);
    }
}
