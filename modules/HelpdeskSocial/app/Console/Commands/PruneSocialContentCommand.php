<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialCompetitorMetric;
use Modules\HelpdeskSocial\Models\SocialMention;

/**
 * Purga comentarios ya cerrados (replied/spam/escalated) y menciones ya
 * procesadas (status != new) más antiguos que el periodo de retención. Sin
 * esto ambas tablas crecen sin límite.
 */
class PruneSocialContentCommand extends Command
{
    private const CLOSED_COMMENT_STATUSES = ['replied', 'spam', 'escalated'];

    protected $signature = 'helpdesksocial:prune
        {--days= : Override the configured retention period in days}';

    protected $description = 'Delete closed social comments and processed mentions older than the retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('helpdesksocial.comment_retention_days', 0));

        if ($days <= 0) {
            $this->components->info('Retention is disabled (comment_retention_days <= 0); nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $comments = $this->pruneClosedComments($cutoff);
        $mentions = $this->pruneProcessedMentions($cutoff);

        if ($comments > 0 || $mentions > 0) {
            Log::info('Pruned social content', [
                'comments' => $comments,
                'mentions' => $mentions,
                'older_than_days' => $days,
            ]);
        }

        $this->components->info("Pruned {$comments} comment(s) and {$mentions} mention(s) older than {$days} days.");

        $this->pruneCompetitorMetrics();

        return self::SUCCESS;
    }

    /**
     * Retencion propia (helpdesksocial.competitors.metrics_retention_days):
     * el benchmarking de competidores se consulta con mucha menos frecuencia
     * que los comentarios/menciones, así que no comparte el retention_days
     * general.
     */
    private function pruneCompetitorMetrics(): void
    {
        $days = (int) config('helpdesksocial.competitors.metrics_retention_days', 0);

        if ($days <= 0) {
            return;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $deleted = SocialCompetitorMetric::query()
                ->where('captured_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $total += $deleted;
        } while ($deleted > 0);

        if ($total > 0) {
            Log::info('Pruned social competitor metrics', ['metrics' => $total, 'older_than_days' => $days]);
        }

        $this->components->info("Pruned {$total} competitor metric(s) older than {$days} days.");
    }

    private function pruneClosedComments(CarbonInterface $cutoff): int
    {
        $total = 0;

        do {
            $deleted = SocialComment::query()
                ->whereIn('status', self::CLOSED_COMMENT_STATUSES)
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }

    private function pruneProcessedMentions(CarbonInterface $cutoff): int
    {
        $total = 0;

        do {
            $deleted = SocialMention::query()
                ->where('status', '!=', 'new')
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
