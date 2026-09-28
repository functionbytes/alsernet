<?php

namespace Modules\HelpdeskAiPrompts\Services;

use Illuminate\Support\Collection;
use Modules\HelpdeskAiPrompts\Models\AiPromptRun;

/**
 * Aggregate quality metrics per case_key for the prompt library panel.
 */
class PromptMetrics
{
    /**
     * @return Collection<string, array{case_key: string, runs: int, escalations: int, escalation_rate: float, likes: int, dislikes: int, satisfaction: float, top_tools: array<int,string>}>
     */
    public function perCase(int $days = 30): Collection
    {
        return AiPromptRun::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->get()
            ->groupBy(fn (AiPromptRun $run) => $run->case_key ?? '(sin caso)')
            ->map(function (Collection $runs, string $caseKey) {
                $total = $runs->count();
                $escalations = $runs->where('action', 'escalate')->count();
                $likes = $runs->where('feedback', 1)->count();
                $dislikes = $runs->where('feedback', -1)->count();

                return [
                    'case_key' => $caseKey,
                    'runs' => $total,
                    'escalations' => $escalations,
                    'escalation_rate' => $total > 0 ? round($escalations / $total, 4) : 0.0,
                    'likes' => $likes,
                    'dislikes' => $dislikes,
                    'satisfaction' => ($likes + $dislikes) > 0 ? round($likes / ($likes + $dislikes), 4) : 0.0,
                    'top_tools' => $this->topTools($runs),
                ];
            });
    }

    /**
     * @param  Collection<int, AiPromptRun>  $runs
     * @return array<int, string>
     */
    private function topTools(Collection $runs, int $limit = 5): array
    {
        return $runs
            ->flatMap(fn (AiPromptRun $run) => $run->used_tools ?? [])
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->keys()
            ->values()
            ->all();
    }
}
