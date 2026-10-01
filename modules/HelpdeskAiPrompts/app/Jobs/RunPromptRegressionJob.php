<?php

namespace Modules\HelpdeskAiPrompts\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskAiPrompts\Models\AiRegressionReport;
use Modules\HelpdeskAiPrompts\Services\Quality\PromptRegressionRunner;
use Throwable;

class RunPromptRegressionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Hasta 20 preguntas x (agente ~40 s + juez ~30 s) en el peor caso.
    public int $timeout = 1500;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>|null  $draft
     */
    public function __construct(
        public readonly int $reportId,
        public readonly int $limit,
        public readonly ?array $draft = null,
    ) {}

    public function handle(PromptRegressionRunner $runner): void
    {
        $report = AiRegressionReport::query()->find($this->reportId);

        if ($report === null || $report->isFinished()) {
            return;
        }

        $runner->execute($report, $this->limit, $this->draft);
    }

    public function failed(Throwable $exception): void
    {
        $report = AiRegressionReport::query()->find($this->reportId);

        if ($report !== null && ! $report->isFinished()) {
            app(PromptRegressionRunner::class)->fail($report, $exception->getMessage());
        }
    }
}
