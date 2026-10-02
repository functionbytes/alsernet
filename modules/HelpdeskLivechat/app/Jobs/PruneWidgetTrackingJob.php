<?php

namespace Modules\HelpdeskLivechat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskLivechat\Models\WidgetPageView;
use Modules\HelpdeskLivechat\Models\WidgetSession;

/**
 * Purga el tracking del widget: vistas de página y sesiones sin actividad
 * desde hace más de N días (config helpdesklivechat.retention). Borra por
 * lotes para no mantener bloqueos largos en tablas de alto volumen.
 */
class PruneWidgetTrackingJob implements ShouldQueue
{
    use Queueable;

    private const CHUNK_SIZE = 1000;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $days = max(1, (int) config('helpdesklivechat.retention.widget_sessions_days', 90));
        $cutoff = now()->subDays($days);

        $pageViews = $this->deleteInChunks(
            fn (): int => WidgetPageView::query()
                ->where('viewed_at', '<', $cutoff)
                ->limit(self::CHUNK_SIZE)
                ->delete()
        );

        $sessions = $this->deleteInChunks(
            fn (): int => WidgetSession::query()
                ->where('last_activity_at', '<', $cutoff)
                ->limit(self::CHUNK_SIZE)
                ->delete()
        );

        Log::info('PruneWidgetTrackingJob completed', [
            'cutoff' => $cutoff->toIso8601String(),
            'page_views_deleted' => $pageViews,
            'sessions_deleted' => $sessions,
        ]);
    }

    /**
     * @param  callable(): int  $deleteChunk
     */
    private function deleteInChunks(callable $deleteChunk): int
    {
        $total = 0;

        do {
            $deleted = $deleteChunk();
            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
