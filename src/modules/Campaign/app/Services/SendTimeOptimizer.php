<?php

namespace Modules\Campaign\Services;

use Illuminate\Support\Facades\DB;
use Modules\Campaign\Models\CampaignSubscriber;

/**
 * Send Time Optimization (STO) básico.
 * Calcula la hora del día (0-23) donde el suscriptor tiene más opens históricos.
 */
class SendTimeOptimizer
{
    public function optimalHour(CampaignSubscriber $subscriber): ?int
    {
        $rows = DB::table('campaign_open_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_open_logs.tracking_log_id')
            ->where('campaign_tracking_logs.subscriber_id', $subscriber->id)
            ->where('campaign_open_logs.created_at', '>=', now()->subDays(90))
            ->selectRaw('HOUR(campaign_open_logs.created_at) as hr, COUNT(*) as cnt')
            ->groupBy('hr')
            ->orderByDesc('cnt')
            ->limit(1)
            ->get();

        return $rows->first()?->hr;
    }

    public function shouldDelay(CampaignSubscriber $subscriber, int $targetHour = 10): int
    {
        $optimal = $this->optimalHour($subscriber);
        if ($optimal === null) {
            return 0; // Sin datos: enviar ahora
        }

        $now = now();
        $target = $now->copy()->hour($optimal)->minute(0)->second(0);

        if ($target->isPast()) {
            $target->addDay();
        }

        $diff = (int) $now->diffInSeconds($target, false);

        return max(0, $diff);
    }
}
