<?php

namespace Modules\HelpdeskSocial\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialCommentEscalated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SocialComment $comment,
    ) {
        $this->comment->load(['socialAccount']);
    }
}
