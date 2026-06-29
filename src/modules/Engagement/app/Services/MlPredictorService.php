<?php

declare(strict_types=1);

namespace Modules\Engagement\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\VisitorScore;

class MlPredictorService
{
    /**
     * Predict conversion probability (0-100) based on session features.
     */
    public function predictConversion(string $sessionToken, int $inboxId): int
    {
        $features = $this->extractFeatures($sessionToken, $inboxId);

        if (empty($features)) {
            return 0;
        }

        $remote = $this->callRemote('predict/conversion', $features);
        if ($remote !== null) {
            return (int) round($remote['probability']);
        }

        // Fallback to local heuristic
        $weights = [
            'page_views' => 2.5,
            'time_on_site' => 1.8,
            'cart_events' => 8.0,
            'purchase_events' => 15.0,
            'days_since_first' => -1.5,
            'engagement_score' => 3.0,
        ];

        $logit = -3.0;

        foreach ($weights as $feature => $weight) {
            $value = $features[$feature] ?? 0;
            $normalized = $this->normalize($feature, $value);
            $logit += $normalized * $weight;
        }

        $probability = 1 / (1 + exp(-$logit));

        return (int) round($probability * 100);
    }

    /**
     * Predict next-best-action for the session.
     *
     * @return array{action: string, confidence: int}
     */
    public function predictNextAction(string $sessionToken, int $inboxId): array
    {
        $features = $this->extractFeatures($sessionToken, $inboxId);

        if (($features['cart_events'] ?? 0) > 0 && ($features['purchase_events'] ?? 0) === 0) {
            return ['action' => 'offer_discount', 'confidence' => 85];
        }

        if (($features['page_views'] ?? 0) > 5) {
            return ['action' => 'show_chat', 'confidence' => 70];
        }

        if (($features['engagement_score'] ?? 0) > 60) {
            return ['action' => 'recommend_product', 'confidence' => 65];
        }

        return ['action' => 'wait', 'confidence' => 50];
    }

    /**
     * Retrain coefficients from recent conversions (placeholder for real model training).
     *
     * @return array<string, float>
     */
    public function retrain(int $inboxId): array
    {
        // In a real implementation, this would call a Python microservice
        // Here we just return the current heuristic weights
        return Cache::remember("engagement:ml:weights:{$inboxId}", 3600, function () {
            return [
                'page_views' => 2.5,
                'time_on_site' => 1.8,
                'cart_events' => 8.0,
                'purchase_events' => 15.0,
                'days_since_first' => -1.5,
                'engagement_score' => 3.0,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFeatures(string $sessionToken, int $inboxId): array
    {
        $events = Event::query()
            ->forSession($sessionToken)
            ->forInbox($inboxId)
            ->get();

        if ($events->isEmpty()) {
            return [];
        }

        $firstEvent = $events->first();
        $score = VisitorScore::query()
            ->forSession($sessionToken)
            ->where('inbox_id', $inboxId)
            ->first();

        return [
            'page_views' => $events->where('event_name', 'page_view')->count(),
            'time_on_site' => $events->max('occurred_at')?->diffInMinutes($firstEvent->occurred_at) ?? 0,
            'cart_events' => $events->where('event_name', 'add_to_cart')->count(),
            'purchase_events' => $events->where('event_name', 'purchase')->count(),
            'days_since_first' => $firstEvent->occurred_at->diffInDays(now()),
            'engagement_score' => $score?->score ?? 0,
        ];
    }

    private function normalize(string $feature, float $value): float
    {
        $max = match ($feature) {
            'page_views' => 20.0,
            'time_on_site' => 60.0,
            'cart_events' => 5.0,
            'purchase_events' => 3.0,
            'days_since_first' => 30.0,
            'engagement_score' => 100.0,
            default => 1.0,
        };

        return min($value / $max, 1.0);
    }

    /**
     * @param  array<string, mixed>  $features
     * @return array<string, mixed>|null
     */
    private function callRemote(string $endpoint, array $features): ?array
    {
        $url = config('engagement.ml_service_url');
        if (! $url) {
            return null;
        }

        try {
            $client = new Client(['base_uri' => $url, 'timeout' => 3, 'connect_timeout' => 1]);
            $response = $client->post($endpoint, ['json' => $features]);

            return json_decode((string) $response->getBody(), true);
        } catch (GuzzleException) {
            return null;
        }
    }
}
