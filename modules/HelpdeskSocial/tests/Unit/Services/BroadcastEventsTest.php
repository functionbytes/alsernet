<?php

namespace Modules\HelpdeskSocial\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Events\SocialCommentReplied;
use Modules\HelpdeskSocial\Listeners\SendNewSocialCommentNotification;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Tests\TestCase;

class BroadcastEventsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_social_comment_received_event_is_dispatched(): void
    {
        Event::fake([SocialCommentReceived::class]);

        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create([
            'social_account_id' => $account->id,
            'platform' => 'facebook',
            'urgency' => 'high',
        ]);

        SocialCommentReceived::dispatch($comment);

        Event::assertDispatched(SocialCommentReceived::class, function ($event) use ($comment) {
            return $event->comment->id === $comment->id;
        });
    }

    public function test_social_comment_replied_event_is_dispatched(): void
    {
        Event::fake([SocialCommentReplied::class]);

        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create([
            'social_account_id' => $account->id,
            'status' => 'replied',
            'reply_type' => 'manual',
        ]);

        SocialCommentReplied::dispatch($comment);

        Event::assertDispatched(SocialCommentReplied::class, function ($event) use ($comment) {
            return $event->comment->id === $comment->id;
        });
    }

    public function test_new_comment_notification_is_not_sent_for_low_urgency(): void
    {
        Notification::fake();

        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create([
            'social_account_id' => $account->id,
            'urgency' => 'low',
        ]);

        $event = new SocialCommentReceived($comment);
        $listener = new SendNewSocialCommentNotification;
        $listener->handle($event);

        Notification::assertNothingSent();
    }
}
