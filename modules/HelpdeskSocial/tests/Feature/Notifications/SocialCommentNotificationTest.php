<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Notifications\NewSocialCommentNotification;
use Modules\HelpdeskSocial\Notifications\SocialCommentEscalatedNotification;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialCommentNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_new_comment_notification_has_correct_structure(): void
    {
        $user = User::factory()->create();
        $comment = SocialComment::factory()->create([
            'platform' => 'facebook',
            'urgency' => 'high',
            'body' => 'This is a test comment',
        ]);

        $notification = new NewSocialCommentNotification($comment);
        $array = $notification->toArray($user);

        $this->assertEquals('helpdesk_social_new_comment', $array['type']);
        $this->assertEquals('Nuevo comentario en facebook', $array['title']);
        $this->assertStringContainsString('🟠 Alta', $array['message']);
        $this->assertEquals($comment->id, $array['entity_id']);
    }

    public function test_escalated_notification_has_correct_structure(): void
    {
        $user = User::factory()->create();
        $comment = SocialComment::factory()->create([
            'status' => 'escalated',
            'body' => 'Escalate this please',
        ]);

        $notification = new SocialCommentEscalatedNotification($comment);
        $array = $notification->toArray($user);

        $this->assertEquals('helpdesk_social_escalated', $array['type']);
        $this->assertEquals('Escalación requerida', $array['title']);
        $this->assertEquals($comment->id, $array['entity_id']);
    }

    public function test_notification_is_sent_via_database_and_broadcast(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $comment = SocialComment::factory()->create();

        $user->notify(new NewSocialCommentNotification($comment));

        Notification::assertSentTo(
            $user,
            NewSocialCommentNotification::class,
            fn ($notification, $channels) => in_array('database', $channels) && in_array('broadcast', $channels) && in_array('mail', $channels)
        );
    }

    public function test_new_comment_notification_includes_mail_channel(): void
    {
        $user = User::factory()->create();
        $comment = SocialComment::factory()->create();

        $notification = new NewSocialCommentNotification($comment);
        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
    }
}
