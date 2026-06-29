<?php

namespace Modules\Reviews\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\ReplyFailed;

class HandleReplyFailed
{
    public function handle(ReplyFailed $event): void
    {
        $reply = $event->reply;

        Log::warning('Reply publication failed', [
            'reply_id' => $reply->id,
            'review_id' => $reply->review_id,
            'created_by' => $reply->created_by,
            'reason' => $event->reason,
        ]);
    }
}
