<?php

namespace Modules\HelpdeskAiPrompts\Services;

use Modules\HelpdeskAiPrompts\Models\AiPromptRun;

/**
 * Stores the token usage and estimated cost of an agent turn on its run.
 */
class PromptRunUsage
{
    public function __construct(private readonly AiUsageCost $cost) {}

    /**
     * @param  array{prompt_tokens?: int, completion_tokens?: int, model?: string|null, calls?: int}  $usage
     */
    public function attach(AiPromptRun $run, array $usage): void
    {
        $prompt = (int) ($usage['prompt_tokens'] ?? 0);
        $completion = (int) ($usage['completion_tokens'] ?? 0);
        $model = $usage['model'] ?? null;

        $run->update([
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'model' => $model,
            'cost_eur' => $this->cost->costEur($model, $prompt, $completion),
            'calls' => (int) ($usage['calls'] ?? 0),
        ]);
    }
}
