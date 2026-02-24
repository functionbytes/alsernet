<?php

namespace Modules\Reviews\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Reviews\Models\ReviewReply;

class ReplyPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ReviewReply $reply
    ) {}
}
