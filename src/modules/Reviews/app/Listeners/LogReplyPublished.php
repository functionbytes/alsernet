<?php

namespace Modules\Reviews\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\ReplyPublished;

class LogReplyPublished
{
    public function handle(ReplyPublished $event): void
    {
        Log::info('Reply published to Google', [
            'reply_id' => $event->reply->id,
            'review_id' => $event->reply->review_id,
            'published_by' => $event->reply->published_by,
        ]);
    }
}
