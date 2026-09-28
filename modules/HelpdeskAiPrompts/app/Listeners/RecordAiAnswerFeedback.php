<?php

namespace Modules\HelpdeskAiPrompts\Listeners;

use Modules\HelpdeskAiPrompts\Services\PromptRunRecorder;
use Modules\HelpdeskLivechat\Events\AiAnswerRated;

class RecordAiAnswerFeedback
{
    public function __construct(private readonly PromptRunRecorder $recorder) {}

    public function handle(AiAnswerRated $event): void
    {
        $this->recorder->recordFeedback($event->item->id, $event->value);
    }
}
