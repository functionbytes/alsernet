<?php

namespace Modules\HelpdeskAiPrompts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionPanel;
use Modules\HelpdeskAiPrompts\Services\PromptMetrics;

class AiPromptIndexController extends Controller
{
    public function index(Request $request, PromptMetrics $metrics, ActionPanel $actions): View
    {
        $metricsByCase = $metrics->perCase(30);

        $cases = AiPromptCase::query()
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get()
            ->each(fn (AiPromptCase $case) => $case->setAttribute('metrics', $metricsByCase->get($case->key)));

        return view('helpdeskaiprompts::index', [
            'cases' => $cases,
            'knowledgeBlocks' => AiPromptBlock::query()->where('kind', 'knowledge')->orderBy('name')->get(),
            'baseBlocks' => AiPromptBlock::query()->where('kind', 'base')->orderBy('name')->get(),
            'stats' => $this->stats($cases, $metricsByCase),
            'actionsPanel' => $actions->forIndex(),
            'channels' => Inbox::CHANNEL_TYPES,
            'canManage' => (bool) $request->user()?->can('helpdesk.ai-prompts.manage'),
            'activeTab' => $request->query('tab', 'casos'),
        ]);
    }

    /**
     * @param  Collection<int, AiPromptCase>  $cases
     * @param  Collection<string, array<string, mixed>>  $metricsByCase
     * @return array<string, int|float>
     */
    private function stats($cases, $metricsByCase): array
    {
        $runs = (int) $metricsByCase->sum('runs');
        $escalations = (int) $metricsByCase->sum('escalations');
        $likes = (int) $metricsByCase->sum('likes');
        $dislikes = (int) $metricsByCase->sum('dislikes');

        return [
            'runs_30d' => $runs,
            'escalation_rate' => $runs > 0 ? round($escalations / $runs * 100, 1) : 0.0,
            'satisfaction' => ($likes + $dislikes) > 0 ? round($likes / ($likes + $dislikes) * 100, 1) : 0.0,
            'active_cases' => $cases->where('is_active', true)->count(),
        ];
    }
}
