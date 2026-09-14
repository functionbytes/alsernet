<?php

namespace Modules\HelpdeskAnalytics\Services;

use Modules\Helpdesk\Services\CustomerInsightsService;

/**
 * Batch version of CustomerInsightsService::healthScore.
 *
 * La lógica batch vive ahora en CustomerInsightsService::healthScoresFor() (core),
 * junto al método individual, como única fuente de verdad. Este servicio delega
 * ahí; se mantiene por compatibilidad con AnalyticsAggregatorService, que lo
 * inyecta.
 */
class HealthScoreBatchService
{
    public function __construct(
        private readonly CustomerInsightsService $insights,
    ) {}

    /**
     * @param  array<int, int>  $customerIds
     * @return array<int, int> [customerId => score 0..100]
     */
    public function scoresFor(array $customerIds): array
    {
        return $this->insights->healthScoresFor($customerIds);
    }
}
