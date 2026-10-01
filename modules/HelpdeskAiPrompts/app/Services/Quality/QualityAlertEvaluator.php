<?php

namespace Modules\HelpdeskAiPrompts\Services\Quality;

use Illuminate\Support\Collection;
use Modules\HelpdeskAiPrompts\Models\AiPromptRun;

/**
 * Mide cada caso en la ventana reciente (24 h por defecto) y devuelve las
 * alertas que superan los umbrales: escalada, 👎 y coste diario.
 */
class QualityAlertEvaluator
{
    public const TYPE_ESCALATION = 'escalation';

    public const TYPE_DISLIKES = 'dislikes';

    public const TYPE_COST = 'cost';

    /**
     * @return Collection<int, array{case_key: string, type: string, value: float, threshold: float, sample: int}>
     */
    public function evaluate(): Collection
    {
        $config = (array) config('helpdeskaiprompts_quality.alerts');
        $alerts = collect();

        foreach ($this->windowStats((int) $config['window_hours']) as $stats) {
            $alerts = $alerts->merge($this->alertsFor($stats, $config));
        }

        return $alerts->values();
    }

    /**
     * @param  array{case_key: string, runs: int, escalations: int, likes: int, dislikes: int, cost: float}  $stats
     * @param  array<string, mixed>  $config
     * @return array<int, array{case_key: string, type: string, value: float, threshold: float, sample: int}>
     */
    private function alertsFor(array $stats, array $config): array
    {
        $alerts = [];

        if ($stats['runs'] >= (int) $config['escalation_min_runs']) {
            $rate = round($stats['escalations'] / $stats['runs'] * 100, 1);

            if ($rate >= (float) $config['escalation_pct']) {
                $alerts[] = $this->alert($stats, self::TYPE_ESCALATION, $rate, (float) $config['escalation_pct'], $stats['runs']);
            }
        }

        $ratings = $stats['likes'] + $stats['dislikes'];

        if ($ratings >= (int) $config['dislike_min_ratings']) {
            $rate = round($stats['dislikes'] / $ratings * 100, 1);

            if ($rate >= (float) $config['dislike_pct']) {
                $alerts[] = $this->alert($stats, self::TYPE_DISLIKES, $rate, (float) $config['dislike_pct'], $ratings);
            }
        }

        if ($stats['cost'] >= (float) $config['cost_eur_day']) {
            $alerts[] = $this->alert($stats, self::TYPE_COST, round($stats['cost'], 2), (float) $config['cost_eur_day'], $stats['runs']);
        }

        return $alerts;
    }

    /**
     * @param  array{case_key: string}  $stats
     * @return array{case_key: string, type: string, value: float, threshold: float, sample: int}
     */
    private function alert(array $stats, string $type, float $value, float $threshold, int $sample): array
    {
        return [
            'case_key' => $stats['case_key'],
            'type' => $type,
            'value' => $value,
            'threshold' => $threshold,
            'sample' => $sample,
        ];
    }

    /**
     * @return Collection<int, array{case_key: string, runs: int, escalations: int, likes: int, dislikes: int, cost: float}>
     */
    private function windowStats(int $hours): Collection
    {
        return AiPromptRun::query()
            ->whereNotNull('case_key')
            ->where('created_at', '>=', now()->subHours($hours))
            ->selectRaw("case_key, count(*) as runs, sum(case when action = 'escalate' then 1 else 0 end) as escalations, sum(case when feedback = 1 then 1 else 0 end) as likes, sum(case when feedback = -1 then 1 else 0 end) as dislikes, sum(cost_eur) as cost")
            ->groupBy('case_key')
            ->get()
            ->map(fn ($row) => [
                'case_key' => (string) $row->case_key,
                'runs' => (int) $row->runs,
                'escalations' => (int) $row->escalations,
                'likes' => (int) $row->likes,
                'dislikes' => (int) $row->dislikes,
                'cost' => (float) $row->cost,
            ]);
    }
}
