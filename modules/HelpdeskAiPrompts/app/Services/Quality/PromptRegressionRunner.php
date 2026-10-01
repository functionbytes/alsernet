<?php

namespace Modules\HelpdeskAiPrompts\Services\Quality;

use Illuminate\Support\Collection;
use Modules\HelpdeskAiPrompts\Jobs\RunPromptRegressionJob;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Models\AiRegressionReport;
use Modules\HelpdeskAiPrompts\Services\AiUsageCost;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogManager;
use Throwable;

/**
 * Re-ejecuta preguntas reales de un caso contra su versión actual (o un
 * borrador) con ChatFlowAgentService y las compara con la respuesta original.
 * Las trazas "test-regression-*" no cuentan en las métricas
 * (PromptRunRecorder ignora todo "test-*").
 */
class PromptRegressionRunner
{
    public function __construct(
        private readonly RegressionQuestionCollector $collector,
        private readonly RegressionJudge $judge,
        private readonly AiUsageCost $cost,
    ) {}

    /**
     * Crea el informe en cola y despacha el job; el límite se acota al máximo configurado.
     *
     * @param  array<string, mixed>|null  $draft  Borrador del caso (sin guardar) en lugar de la versión actual
     */
    public function queue(AiPromptCase $case, ?int $userId, ?int $limit = null, ?array $draft = null): AiRegressionReport
    {
        $report = AiRegressionReport::query()->create([
            'case_key' => $case->key,
            'case_version' => $case->version,
            'is_draft' => $draft !== null,
            'status' => AiRegressionReport::STATUS_QUEUED,
            'triggered_by' => $userId,
        ]);

        RunPromptRegressionJob::dispatch($report->id, $this->clampLimit($limit), $draft);

        return $report;
    }

    public function clampLimit(?int $limit): int
    {
        $max = max(1, (int) config('helpdeskaiprompts_quality.regression.max_questions'));
        $default = (int) config('helpdeskaiprompts_quality.regression.default_questions');

        return max(1, min($limit ?? $default, $max));
    }

    /**
     * @param  array<string, mixed>|null  $draft
     */
    public function execute(AiRegressionReport $report, int $limit, ?array $draft = null): AiRegressionReport
    {
        $case = AiPromptCase::query()->where('key', $report->case_key)->first();

        if ($case === null || ! class_exists(ChatFlowAgentService::class)) {
            return $this->fail($report, $case === null ? 'El caso ya no existe.' : 'El agente IA (ChatFlow) no está disponible.');
        }

        $report->update(['status' => AiRegressionReport::STATUS_RUNNING, 'started_at' => now()]);

        $questions = $this->collector->collect($this->withDraftQuestions($case, $draft), $this->clampLimit($limit));
        $caseData = $draft ?? $case->toArray();
        $results = [];
        $cost = 0.0;
        $truncated = false;
        $catalog = $this->resolveCatalog();
        $agent = app(ChatFlowAgentService::class);
        $maxCost = (float) config('helpdeskaiprompts_quality.regression.max_cost_eur');

        foreach ($questions as $question) {
            if ($cost >= $maxCost) {
                $truncated = true;
                break;
            }

            $result = $this->runQuestion($agent, $question, $caseData, $catalog);
            $cost += $result['cost_eur'];
            $results[] = $result;
        }

        $summary = $this->summarize($results, $truncated);

        $report->update([
            'status' => AiRegressionReport::STATUS_COMPLETED,
            'questions_total' => count($results),
            'regressions' => $summary['regressions'],
            'avg_score' => $summary['avg_score'],
            'cost_eur' => round($cost, 6),
            'summary' => $summary,
            'results' => $results,
            'finished_at' => now(),
        ]);

        return $report;
    }

    public function fail(AiRegressionReport $report, string $message): AiRegressionReport
    {
        $report->update([
            'status' => AiRegressionReport::STATUS_FAILED,
            'error' => mb_substr($message, 0, 1000),
            'finished_at' => now(),
        ]);

        return $report;
    }

    /**
     * @param  array<string, mixed>|null  $draft
     */
    private function withDraftQuestions(AiPromptCase $case, ?array $draft): AiPromptCase
    {
        if ($draft !== null && array_key_exists('test_questions', $draft)) {
            $case->test_questions = $draft['test_questions'];
        }

        return $case;
    }

    /**
     * @param  array{question: string, source: string, original: array<string, mixed>|null}  $question
     * @param  array<string, mixed>  $caseData
     * @return array<string, mixed>
     */
    private function runQuestion(object $agent, array $question, array $caseData, ?object $catalog): array
    {
        try {
            $response = $agent->run(
                $question['question'],
                ['_trace_id' => 'test-regression-'.uniqid()],
                [
                    'use_prompt_library' => true,
                    '_draft_case' => $caseData,
                    'tool_products' => true,
                    'tool_cart' => false,
                    'tool_order_lookup' => true,
                ],
                'es',
                $catalog,
            );
        } catch (Throwable $e) {
            return $this->row($question, null, null, 0.0, mb_substr($e->getMessage(), 0, 300));
        }

        $usage = (array) ($response['usage'] ?? []);
        $cost = $this->cost->costEur($usage['model'] ?? null, (int) ($usage['prompt_tokens'] ?? 0), (int) ($usage['completion_tokens'] ?? 0));

        $new = [
            'answer' => (string) ($response['text'] ?? ''),
            'used_tools' => array_values((array) ($response['used_tools'] ?? [])),
            'action' => (string) ($response['action'] ?? ''),
        ];

        $judged = $question['original'] === null
            ? null
            : $this->judge->judge($question['question'], $question['original']['answer'], $new['answer']);

        return $this->row($question, $new, $judged, $cost + (float) ($judged['cost_eur'] ?? 0));
    }

    /**
     * @param  array{question: string, source: string, original: array<string, mixed>|null}  $question
     * @param  array{answer: string, used_tools: array<int,string>, action: string}|null  $new
     * @param  array{score: int, reason: string}|null  $judged
     * @return array<string, mixed>
     */
    private function row(array $question, ?array $new, ?array $judged, float $cost, ?string $error = null): array
    {
        $original = $question['original'];
        $score = $judged['score'] ?? null;

        $escalatedBefore = $original === null ? null : $original['action'] === 'escalate';
        $escalatedAfter = $new === null ? null : $new['action'] === 'escalate';

        $toolsBefore = $original['used_tools'] ?? [];
        $toolsAfter = $new['used_tools'] ?? [];

        return [
            'question' => $question['question'],
            'source' => $question['source'],
            'original' => $original === null ? null : [
                'answer' => $original['answer'],
                'used_tools' => $toolsBefore,
                'escalated' => $escalatedBefore,
                'length' => $original['length'],
            ],
            'new' => $new === null ? null : [
                'answer' => $new['answer'],
                'used_tools' => $toolsAfter,
                'escalated' => $escalatedAfter,
                'length' => mb_strlen($new['answer']),
            ],
            'tools_changed' => $original !== null && $new !== null && $this->sorted($toolsBefore) !== $this->sorted($toolsAfter),
            'escalation_changed' => $escalatedBefore !== null && $escalatedAfter !== null && $escalatedBefore !== $escalatedAfter,
            'score' => $score,
            'judge_reason' => $judged['reason'] ?? null,
            'verdict' => $this->verdict($original, $escalatedBefore, $escalatedAfter, $score, $error),
            'cost_eur' => round($cost, 6),
            'error' => $error,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $original
     */
    private function verdict(?array $original, ?bool $escalatedBefore, ?bool $escalatedAfter, ?int $score, ?string $error): string
    {
        return match (true) {
            $error !== null => 'error',
            $original === null => 'no_baseline',
            $escalatedBefore === false && $escalatedAfter === true => 'regression',
            $score !== null && $score <= (int) config('helpdeskaiprompts_quality.regression.regression_score') => 'regression',
            default => 'ok',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function summarize(array $results, bool $truncated): array
    {
        $rows = collect($results);
        $withBaseline = $rows->filter(fn (array $r) => $r['original'] !== null && $r['new'] !== null);
        $scores = $rows->pluck('score')->filter(fn ($s) => $s !== null);

        return [
            'regressions' => $rows->where('verdict', 'regression')->count(),
            'errors' => $rows->where('verdict', 'error')->count(),
            'real_questions' => $rows->where('source', 'real')->count(),
            'avg_score' => $scores->isEmpty() ? null : round($scores->avg(), 2),
            'tools_changed' => $rows->where('tools_changed', true)->count(),
            'escalations_before' => $withBaseline->filter(fn (array $r) => $r['original']['escalated'])->count(),
            'escalations_after' => $withBaseline->filter(fn (array $r) => $r['new']['escalated'])->count(),
            'avg_length_before' => $this->average($withBaseline, 'original'),
            'avg_length_after' => $this->average($withBaseline, 'new'),
            'judge_used' => $scores->isNotEmpty(),
            'truncated_by_cost' => $truncated,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function average(Collection $rows, string $side): ?int
    {
        return $rows->isEmpty() ? null : (int) round($rows->avg(fn (array $r) => $r[$side]['length']));
    }

    /**
     * @param  array<int, string>  $tools
     * @return array<int, string>
     */
    private function sorted(array $tools): array
    {
        sort($tools);

        return $tools;
    }

    private function resolveCatalog(): ?object
    {
        if (! class_exists(CatalogManager::class) || ! class_exists(Web::class)) {
            return null;
        }

        $web = Web::query()
            ->where('cms_type', 'prestashop')
            ->orWhere(fn ($q) => $q->whereNotNull('product_feed_url')->where('product_feed_url', '!=', ''))
            ->orderBy('id')
            ->first();

        return $web === null ? null : app(CatalogManager::class)->forWeb($web);
    }
}
