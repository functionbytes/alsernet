<?php

namespace Modules\Reviews\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Reviews\Models\ReviewReply;

class ReplyFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ReviewReply $reply,
        public string $reason
    ) {}
}
