<?php

namespace Modules\Reviews\Tests\Unit\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\ReplyFailed;
use Modules\Reviews\Listeners\HandleReplyFailed;
use Modules\Reviews\Tests\TestCase;

class HandleReplyFailedTest extends TestCase
{
    public function test_handle_logs_warning_with_reply_and_reason(): void
    {
        Log::spy();

        $reply = $this->createReply();
        $reason = 'Google API returned 403 Forbidden';

        $event = new ReplyFailed($reply, $reason);

        (new HandleReplyFailed)->handle($event);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Reply publication failed', [
                'reply_id' => $reply->id,
                'review_id' => $reply->review_id,
                'created_by' => $reply->created_by,
                'reason' => $reason,
            ]);
    }

    public function test_handle_includes_reason_in_log_context(): void
    {
        Log::spy();

        $reply = $this->createReply();
        $reason = 'Network timeout';

        $event = new ReplyFailed($reply, $reason);

        (new HandleReplyFailed)->handle($event);

        Log::shouldHaveReceived('warning')
            ->with(\Mockery::any(), \Mockery::on(fn ($context) => $context['reason'] === $reason));
    }
}
