<?php

namespace Modules\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Seo\Mail\SeoWeeklyReportMail;
use Modules\Seo\Models\Seo404Log;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoRedirect;

class SendSeoWeeklyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 60;

    public function __construct()
    {
        $this->onQueue('seo-scheduled');
    }

    public function handle(): void
    {
        $email = config('Seo.notifications.email');

        if (empty($email)) {
            return;
        }

        $stats = [
            'total_metas' => SeoMeta::count(),
            'avg_score' => (int) SeoMeta::whereNotNull('seo_score')->avg('seo_score'),
            'total_redirects' => SeoRedirect::count(),
            'total_404s' => Seo404Log::where('has_redirect', false)->count(),
            'new_orphans' => 0,
            'worst_pages' => SeoMeta::whereNotNull('seo_score')
                ->orderBy('seo_score')
                ->limit(5)
                ->get(['id', 'title', 'seo_score', 'seo_grade', 'canonical_url']),
            'top_404s' => Seo404Log::where('has_redirect', false)
                ->orderByDesc('hit_count')
                ->limit(5)
                ->get(['path', 'hit_count']),
            'improving_count' => $this->countTrendingPages('>'),
            'declining_count' => $this->countTrendingPages('<'),
        ];

        Mail::to($email)->queue(new SeoWeeklyReportMail($stats));
    }

    private function countTrendingPages(string $operator): int
    {
        $rows = DB::select("
            SELECT COUNT(*) AS total
            FROM (
                SELECT
                    m.id,
                    (latest.score - prev.score) AS score_change
                FROM seo_metas m
                INNER JOIN seo_audit_logs latest ON latest.seo_meta_id = m.id
                INNER JOIN seo_audit_logs prev ON prev.seo_meta_id = m.id
                WHERE latest.id = (
                    SELECT id FROM seo_audit_logs WHERE seo_meta_id = m.id ORDER BY audited_at DESC LIMIT 1
                )
                AND prev.id = (
                    SELECT id FROM seo_audit_logs WHERE seo_meta_id = m.id ORDER BY audited_at DESC LIMIT 1 OFFSET 1
                )
                AND latest.score != prev.score
            ) AS trend
            WHERE score_change {$operator} 0
        ");

        return (int) ($rows[0]->total ?? 0);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendSeoWeeklyReport failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
