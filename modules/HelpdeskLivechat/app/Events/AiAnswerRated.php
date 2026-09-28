<?php

namespace Modules\HelpdeskLivechat\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\ConversationItem;

/**
 * A visitor rated an AI-generated answer (👍/👎) from the widget. Other
 * modules (e.g. analytics/quality dashboards) may listen for this to track
 * AI answer quality over time — no listener lives in this module.
 */
class AiAnswerRated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  int  $value  1 for a positive rating ("up"), -1 for a negative one ("down")
     */
    public function __construct(
        public readonly ConversationItem $item,
        public readonly int $value,
    ) {}
}
