<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;

class CustomerStatsService
{
    /**
     * Returns all customer stats in a single aggregated query, cached for 5 minutes.
     *
     * @return array{total: int, verified: int, banned: int, active: int, total_conversations: int, new_this_month: int}
     */
    public function getStats(): array
    {
        return Cache::remember('helpdesk:customer_stats', now()->addMinutes(5), function () {
            $stats = Customer::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified')
                ->selectRaw('SUM(CASE WHEN banned_at IS NOT NULL THEN 1 ELSE 0 END) as banned')
                ->selectRaw('SUM(CASE WHEN banned_at IS NULL THEN 1 ELSE 0 END) as active')
                ->selectRaw('SUM(total_conversations) as total_conversations')
                ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_this_month', [now()->startOfMonth()])
                ->first();

            return [
                'total' => (int) $stats->total,
                'verified' => (int) $stats->verified,
                'banned' => (int) $stats->banned,
                'active' => (int) $stats->active,
                'total_conversations' => (int) $stats->total_conversations,
                'new_this_month' => (int) $stats->new_this_month,
            ];
        });
    }
}
