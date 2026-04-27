<?php

namespace Modules\Reviews\Listeners;

use Modules\Reviews\Events\ReviewAnomalyDetected;
use Modules\Reviews\Services\OutboundWebhookService;

class DispatchWebhookOnAnomalyDetected
{
    public function __construct(private readonly OutboundWebhookService $webhookService) {}

    public function handle(ReviewAnomalyDetected $event): void
    {
        $this->webhookService->dispatch('review.anomaly', [
            'event' => 'review.anomaly',
            'location_id' => $event->anomaly->locationId,
            'location_name' => $event->anomaly->locationName,
            'current_count' => $event->anomaly->currentCount,
            'historical_average' => $event->anomaly->historicalAverage,
            'multiplier' => $event->anomaly->multiplier,
            'detected_at' => $event->anomaly->detectedAt->toIso8601String(),
        ]);
    }
}
