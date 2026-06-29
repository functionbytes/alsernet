<?php

namespace Modules\Engagement\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Engagement\Models\VisitorScore;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;

class BoostScoreOnPsOrderListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'engagement';

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function handle(PsOrderCreated $event): void
    {
        $psCustomerId = $event->customerId();

        if (! $psCustomerId) {
            return;
        }

        $orderTotal = (float) ($event->payload['total'] ?? 0);
        $boost = $this->calculateBoost($orderTotal);

        // Find session tokens for this PS customer via visitor contexts
        $sessionTokens = DB::connection('helpdesk')
            ->table('engagement_visitor_contexts')
            ->where('ps_customer_id', $psCustomerId)
            ->pluck('session_token');

        if ($sessionTokens->isEmpty()) {
            return;
        }

        VisitorScore::query()
            ->whereIn('session_token', $sessionTokens)
            ->get()
            ->each(function (VisitorScore $score) use ($boost) {
                $newScore = min(100, $score->score + $boost);
                $score->update([
                    'score' => $newScore,
                    'segment' => VisitorScore::segmentFromScore($newScore),
                ]);
            });
    }

    public function failed(PsOrderCreated $event, \Throwable $exception): void
    {
        Log::error('Engagement: BoostScoreOnPsOrderListener failed', [
            'ps_customer_id' => $event->customerId(),
            'error' => $exception->getMessage(),
        ]);
    }

    private function calculateBoost(float $orderTotal): int
    {
        return match (true) {
            $orderTotal >= 500 => 30,
            $orderTotal >= 100 => 20,
            default => 10,
        };
    }
}
