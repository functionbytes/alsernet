<?php

namespace Modules\Engagement\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Models\VisitorScore;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class VisitorPsContextEnrichmentService
{
    public function __construct(
        private readonly PrestashopContextService $psService,
    ) {}

    public function enrich(string $sessionToken, string $email): void
    {
        $context = $this->fetchPsContext($email);

        if (! $context || ! ($context['customer']['found'] ?? false)) {
            return;
        }

        $customer = $context['customer'];
        $psCustomerId = $customer['id'] ?? null;

        $visitorContext = VisitorContext::query()
            ->forSession($sessionToken)
            ->first();

        if (! $visitorContext) {
            return;
        }

        // Merge PS data into the flexible context JSON
        $visitorContext->mergeContext([
            'email' => $email,
            'ps_lifetime_orders' => $customer['orders_count'] ?? 0,
            'ps_lifetime_revenue' => $customer['total_spent'] ?? 0,
            'ps_last_order_at' => $customer['last_order_at'] ?? null,
        ]);

        // Store PS customer id for efficient cross-referencing
        if ($psCustomerId) {
            $visitorContext->ps_customer_id = $psCustomerId;
            $visitorContext->save();
        }

        // Boost score based on purchase history
        $this->applyScoreBoost($sessionToken, $customer);
    }

    private function fetchPsContext(string $email): ?array
    {
        $cacheKey = 'eng_ps_enrich_'.md5($email);

        return Cache::remember($cacheKey, 600, function () use ($email) {
            try {
                return $this->psService->getCustomerContext($email);
            } catch (\Throwable $e) {
                Log::warning('Engagement: PS context enrichment failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    private function applyScoreBoost(string $sessionToken, array $customer): void
    {
        $score = VisitorScore::query()->forSession($sessionToken)->first();

        if (! $score) {
            return;
        }

        $boost = $this->calculateBoost($customer);
        $newScore = min(100, $score->score + $boost);

        $score->update([
            'score' => $newScore,
            'segment' => VisitorScore::segmentFromScore($newScore),
        ]);
    }

    private function calculateBoost(array $customer): int
    {
        $ordersCount = (int) ($customer['orders_count'] ?? 0);
        $totalSpent = (float) ($customer['total_spent'] ?? 0);

        $boost = 0;

        $boost += match (true) {
            $ordersCount >= 10 => 25,
            $ordersCount >= 5 => 15,
            $ordersCount >= 1 => 5,
            default => 0,
        };

        $boost += match (true) {
            $totalSpent >= 1000 => 25,
            $totalSpent >= 500 => 15,
            $totalSpent >= 100 => 5,
            default => 0,
        };

        return $boost;
    }
}
