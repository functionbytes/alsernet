<?php

namespace Modules\HelpdeskAiPrompts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskAiPrompts\Http\Requests\RunRegressionRequest;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Models\AiRegressionReport;
use Modules\HelpdeskAiPrompts\Services\Quality\PromptRegressionRunner;
use Modules\HelpdeskAiPrompts\Services\Quality\QualityAlertEvaluator;

class QualityController extends Controller
{
    private const HISTORY_LIMIT = 20;

    /** Un informe en cola o en curso más antiguo que esto se considera atascado. */
    private const STUCK_AFTER_MINUTES = 30;

    public function index(Request $request, QualityAlertEvaluator $alerts): View
    {
        $cases = AiPromptCase::query()->orderByDesc('priority')->orderBy('name')->get();

        $reports = AiRegressionReport::query()
            ->select(['id', 'case_key', 'case_version', 'is_draft', 'status', 'questions_total', 'regressions', 'avg_score', 'cost_eur', 'error', 'created_at', 'finished_at'])
            ->latest('id')
            ->limit(self::HISTORY_LIMIT)
            ->get();

        return view('helpdeskaiprompts::quality.index', [
            'alerts' => $alerts->evaluate(),
            'cases' => $cases,
            'caseNames' => $cases->pluck('name', 'key'),
            'reports' => $reports,
            'lastReports' => $reports->groupBy('case_key')->map->first(),
            'thresholds' => (array) config('helpdeskaiprompts_quality.alerts'),
            'maxQuestions' => (int) config('helpdeskaiprompts_quality.regression.max_questions'),
            'canManage' => (bool) $request->user()?->can('helpdesk.ai-prompts.manage'),
        ]);
    }

    public function run(RunRegressionRequest $request, AiPromptCase $case, PromptRegressionRunner $runner): JsonResponse
    {
        $inFlight = AiRegressionReport::query()
            ->where('case_key', $case->key)
            ->whereIn('status', [AiRegressionReport::STATUS_QUEUED, AiRegressionReport::STATUS_RUNNING])
            ->where('created_at', '>=', now()->subMinutes(self::STUCK_AFTER_MINUTES))
            ->exists();

        if ($inFlight) {
            return response()->json(['message' => __('helpdeskaiprompts::quality.already_running')], 409);
        }

        $report = $runner->queue($case, $request->user()->id, $request->validated('questions'));

        return response()->json([
            'message' => __('helpdeskaiprompts::quality.queued'),
            'report_id' => $report->id,
            'status_url' => route('helpdesk-ai-prompts.quality.reports.show', $report),
        ], 202);
    }

    public function show(AiRegressionReport $report): JsonResponse
    {
        return response()->json([
            'id' => $report->id,
            'case_key' => $report->case_key,
            'case_version' => $report->case_version,
            'is_draft' => $report->is_draft,
            'status' => $report->status,
            'finished' => $report->isFinished(),
            'questions_total' => $report->questions_total,
            'regressions' => $report->regressions,
            'avg_score' => $report->avg_score,
            'cost_eur' => $report->cost_eur,
            'error' => $report->error,
            'summary' => $report->summary,
            'results' => $report->results ?? [],
            'created_at' => $report->created_at?->format('d/m/Y H:i'),
        ]);
    }
}
