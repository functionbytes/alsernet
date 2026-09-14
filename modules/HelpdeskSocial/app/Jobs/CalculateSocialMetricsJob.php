<?php

namespace Modules\HelpdeskSocial\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialMetrics;

class CalculateSocialMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 10;

    public function __construct(
        public readonly string $date,
        public readonly ?int $accountId = null,
    ) {
        $this->onQueue(config('helpdesksocial.queues.analytics', 'helpdesk-social-analytics'));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateSocialMetricsJob failed', [
            'date' => $this->date,
            'account_id' => $this->accountId,
            'error' => $exception->getMessage(),
        ]);
    }

    public function handle(): void
    {
        $date = Carbon::parse($this->date);

        // whereDate('posted_at', $date) compila a WHERE DATE(posted_at) = ? —
        // envolver la columna en una función impide usar su índice y fuerza
        // un escaneo completo de social_comments cada vez que corre este job
        // (diario, por cuenta). whereBetween sobre el rango del día sí puede
        // usar el índice de posted_at.
        $base = SocialComment::query()->whereBetween('posted_at', [
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
        ]);

        if ($this->accountId) {
            $base->where('social_account_id', $this->accountId);
        }

        if (! (clone $base)->exists()) {
            return;
        }

        // Antes: ->get() traía todas las filas del día a PHP y las recorría
        // 6-7 veces por separado (groupBy/count/avg) — con volumen alto de
        // comentarios (histórico de meses) esto crecía sin límite. Ahora un
        // único agregado SQL calcula los totales y el tiempo medio de
        // respuesta; los desgloses por intent/hora van en dos queries GROUP
        // BY aparte (mismo índice, sin traer las filas completas a PHP).
        $totals = (clone $base)->selectRaw(<<<'SQL'
            COUNT(*) as total,
            SUM(CASE WHEN replied_at IS NOT NULL THEN 1 ELSE 0 END) as total_replies,
            SUM(CASE WHEN reply_type = 'auto' THEN 1 ELSE 0 END) as auto_replies,
            SUM(CASE WHEN status = 'escalated' THEN 1 ELSE 0 END) as escalated_count,
            SUM(CASE WHEN is_spam = 1 THEN 1 ELSE 0 END) as spam_detected,
            AVG(CASE WHEN replied_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, posted_at, replied_at) END) as avg_response_time_seconds
        SQL)->first();

        // platform: mismo criterio laxo que antes ($comments->first()->platform
        // — un valor arbitrario cuando se agrega sin accountId y hay más de una
        // plataforma en el mismo día; no se introduce un GROUP BY platform
        // para no cambiar la forma de la fila resultante).
        $platform = (clone $base)->value('platform');

        $intentsBreakdown = (clone $base)
            ->selectRaw('intent, COUNT(*) as c')
            ->groupBy('intent')
            ->pluck('c', 'intent')
            ->toArray();

        $sentimentBreakdown = [
            'positive' => $intentsBreakdown['positive'] ?? 0,
            'neutral' => $intentsBreakdown['neutral'] ?? 0,
            'negative' => (int) (($intentsBreakdown['complaint'] ?? 0) + ($intentsBreakdown['spam'] ?? 0)),
        ];

        $hourlyDistribution = (clone $base)
            ->selectRaw('HOUR(posted_at) as h, COUNT(*) as c')
            ->groupBy('h')
            ->pluck('c', 'h')
            ->toArray();
        // Claves como string ('9', '14'...) — mismo formato que antes
        // ($c->posted_at->format('H'), zero-padded a 2 dígitos).
        $hourlyDistribution = collect($hourlyDistribution)
            ->mapWithKeys(fn ($count, $hour) => [str_pad((string) $hour, 2, '0', STR_PAD_LEFT) => $count])
            ->toArray();

        $totalReplies = (int) $totals->total_replies;
        $autoReplies = (int) $totals->auto_replies;
        $automationRate = $totalReplies > 0 ? round(($autoReplies / $totalReplies) * 100, 2) : 0;

        SocialMetrics::updateOrCreate(
            [
                'date' => $date->toDateString(),
                'social_account_id' => $this->accountId,
            ],
            [
                'platform' => $platform,
                'comments_received' => (int) $totals->total,
                'replies_sent' => $totalReplies,
                'auto_replies_sent' => $autoReplies,
                'manual_replies_sent' => $totalReplies - $autoReplies,
                'escalated_count' => (int) $totals->escalated_count,
                'spam_detected' => (int) $totals->spam_detected,
                'avg_response_time_seconds' => $totals->avg_response_time_seconds !== null ? (int) round($totals->avg_response_time_seconds) : null,
                'automation_rate' => $automationRate,
                'intents_breakdown' => $intentsBreakdown,
                'sentiment_breakdown' => $sentimentBreakdown,
                'hourly_distribution' => $hourlyDistribution,
            ]
        );
    }
}
