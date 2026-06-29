<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Models\CampaignSubscriber;

/**
 * Identifica suscriptores dormidos y los marca para campañas de re-engagement.
 * En producción se conecta con un trigger de automation para lanzar la campaña.
 */
class ReEngagementCommand extends Command
{
    protected $signature = 'campaign:re-engagement-check
                            {--days=90 : Días sin apertura para considerar dormido}
                            {--min-score=-20 : Score máximo para considerar}';

    protected $description = 'Detecta suscriptores dormidos y prepara re-engagement';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $minScore = (int) $this->option('min-score');

        $dormant = CampaignSubscriber::query()
            ->join('campaign_subscriber_engagement_scores as ces', 'ces.subscriber_id', '=', 'campaign_subscribers.id')
            ->where(function ($q) use ($days): void {
                $q->whereNull('ces.last_opened_at')
                    ->orWhere('ces.last_opened_at', '<', now()->subDays($days));
            })
            ->where('ces.score', '<=', $minScore)
            ->select('campaign_subscribers.id', 'campaign_subscribers.email', 'ces.score')
            ->get();

        $this->info("{$dormant->count()} suscriptores dormidos detectados.");

        foreach ($dormant as $sub) {
            DB::table('campaign_reengagement_queue')->updateOrInsert(
                ['subscriber_id' => $sub->id],
                ['status' => 'pending', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        return self::SUCCESS;
    }
}
