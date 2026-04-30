<?php

namespace Modules\Chat\Services\Analytics;

use Carbon\Carbon;
use Modules\Chat\Models\Sla\SlaPolicy;

class SlaReportService
{
    public function __construct(
        protected int $accountId
    ) {}

    /**
     * Get SLA policy metrics with breach statistics.
     */
    public function getPolicyMetrics(int $period): array
    {
        $startDate = now()->subDays($period);

        return SlaPolicy::where('account_id', $this->accountId)
            ->withCount([
                'appliedConversations as total_conversations' => fn ($q) => $q->where('created_at', '>=', $startDate),
                'appliedConversations as breached_conversations' => fn ($q) => $q
                    ->where('created_at', '>=', $startDate)
                    ->where(fn ($q2) => $q2
                        ->where('first_response_breached', true)
                        ->orWhere('resolution_breached', true)
                    ),
            ])
            ->get()
            ->map(function ($policy) {
                $total = $policy->total_conversations ?? 0;
                $breached = $policy->breached_conversations ?? 0;

                return [
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'total_conversations' => $total,
                    'breached_conversations' => $breached,
                    'compliance_rate' => $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100,
                ];
            })
            ->toArray();
    }

    /**
     * Get detailed breach statistics for a policy.
     */
    public function getPolicyBreachDetails(int $policyId, Carbon $startDate, Carbon $endDate): array
    {
        $policy = SlaPolicy::where('account_id', $this->accountId)
            ->findOrFail($policyId);

        $conversations = $policy->appliedConversations()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN first_response_breached = 1 THEN 1 ELSE 0 END) as first_response_breaches')
            ->selectRaw('SUM(CASE WHEN resolution_breached = 1 THEN 1 ELSE 0 END) as resolution_breaches')
            ->first();

        $total = (int) ($conversations->total ?? 0);
        $firstResponseBreaches = (int) ($conversations->first_response_breaches ?? 0);
        $resolutionBreaches = (int) ($conversations->resolution_breaches ?? 0);

        return [
            'policy' => [
                'id' => $policy->id,
                'name' => $policy->name,
                'first_response_target' => $policy->first_response_time_target,
                'resolution_target' => $policy->resolution_time_target,
            ],
            'total_conversations' => $total,
            'first_response_breaches' => $firstResponseBreaches,
            'resolution_breaches' => $resolutionBreaches,
            'compliance_rate' => $total > 0
                ? round((($total - $firstResponseBreaches - $resolutionBreaches) / $total) * 100, 1)
                : 100,
        ];
    }
}
