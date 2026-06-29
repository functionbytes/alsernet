<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Models\CampaignSubscriber;

/**
 * Limpia listas eliminando suscriptores inactivos/dormidos que no abren emails
 * en los últimos N días. Mejora reputación de IP y reduce costos.
 */
class ListHygieneCommand extends Command
{
    protected $signature = 'campaign:list-hygiene
                            {--days=180 : Días sin apertura para eliminar}
                            {--min-score=-30 : Score máximo para considerar}
                            {--dry-run : Simular sin eliminar}';

    protected $description = 'Elimina suscriptores inactivos para mantener listas limpias';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $minScore = (int) $this->option('min-score');
        $dryRun = $this->option('dry-run');

        $query = CampaignSubscriber::query()
            ->join('campaign_subscriber_engagement_scores as ces', 'ces.subscriber_id', '=', 'campaign_subscribers.id')
            ->where(function ($q) use ($days): void {
                $q->whereNull('ces.last_opened_at')
                    ->orWhere('ces.last_opened_at', '<', now()->subDays($days));
            })
            ->where('ces.score', '<=', $minScore)
            ->select('campaign_subscribers.id', 'campaign_subscribers.email', 'ces.score');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No se encontraron suscriptores inactivos para eliminar.');

            return self::SUCCESS;
        }

        $this->warn("{$count} suscriptores inactivos encontrados (sin apertura en {$days} días, score <= {$minScore}).");

        if ($dryRun) {
            $this->info('Modo simulación — no se eliminó nada.');
            $query->limit(10)->get()->each(fn ($s) => $this->line("  - {$s->email} (score: {$s->score})"));

            return self::SUCCESS;
        }

        if (! $this->confirm('¿Eliminar suscriptores inactivos?')) {
            return self::SUCCESS;
        }

        $deleted = 0;
        $query->chunkById(500, function ($subscribers) use (&$deleted): void {
            $ids = $subscribers->pluck('id')->all();
            CampaignSubscriber::whereIn('id', $ids)->delete();
            $deleted += count($ids);
        });

        $this->info("{$deleted} suscriptores eliminados.");

        return self::SUCCESS;
    }
}
