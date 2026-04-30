<?php

namespace Modules\Chat\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Events\ConversationStatusChanged;
use Modules\Chat\Jobs\SendCsatSurvey;
use Modules\Chat\Services\SlaService;

class UpdateConversationStatusListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly SlaService $slaService
    ) {}

    public function handle(ConversationStatusChanged $event): void
    {
        $conversation = $event->conversation;

        if ($conversation->status?->slug !== 'resolved') {
            return;
        }

        try {
            $this->slaService->checkResolution($conversation);
        } catch (\Throwable $e) {
            Log::error('SLA resolution check failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            dispatch(new SendCsatSurvey($conversation));
        } catch (\Throwable $e) {
            Log::error('CSAT survey dispatch failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
