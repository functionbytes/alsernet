<?php

namespace Modules\Engagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Services\AutomationEngine;
use Modules\Engagement\Services\ConversionMatcher;
use Modules\Engagement\Services\RecommenderService;
use Modules\Engagement\Services\ScoringService;
use Modules\Engagement\Services\TriggerEvaluator;

class ProcessEventBatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly string $sessionToken,
        private readonly int $inboxId,
    ) {
        $this->onQueue('helpdesklivechat');
    }

    public function handle(
        ScoringService $scoringService,
        TriggerEvaluator $triggerEvaluator,
        RecommenderService $recommenderService,
        ConversionMatcher $conversionMatcher,
        AutomationEngine $automationEngine,
    ): void {
        $visitorScore = $scoringService->recalculate($this->sessionToken, $this->inboxId);

        $customerId = $visitorScore->customer_id;

        if ($customerId) {
            $recommenderService->updateProfile($this->sessionToken, $customerId);
        }

        // Process recent events for conversion goals + automation triggers
        $recentEvents = Event::query()
            ->forSession($this->sessionToken)
            ->where('occurred_at', '>=', now()->subMinutes(5))
            ->orderBy('occurred_at')
            ->get();

        foreach ($recentEvents as $event) {
            $matchedGoals = $conversionMatcher->matchEvent($event);
            if (! empty($matchedGoals)) {
                Log::info('Conversion goals matched', [
                    'session_token' => $this->sessionToken,
                    'event' => $event->event_name,
                    'goals' => $matchedGoals,
                ]);
            }

            $automationEngine->startFlowsForEvent(
                $event->event_name,
                $this->sessionToken,
                $this->inboxId,
                $customerId,
                $event->properties ?? [],
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessEventBatchJob failed', [
            'session_token' => $this->sessionToken,
            'inbox_id' => $this->inboxId,
            'error' => $exception->getMessage(),
        ]);
    }
}
