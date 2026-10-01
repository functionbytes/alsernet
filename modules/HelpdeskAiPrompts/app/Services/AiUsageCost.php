<?php

namespace Modules\HelpdeskAiPrompts\Services;

/**
 * Estimated OpenAI cost (EUR) from token usage. Prices come from
 * config('helpdeskaiprompts.pricing'); the result is an estimate, not billing.
 */
class AiUsageCost
{
    public function costEur(?string $model, int $promptTokens, int $completionTokens): float
    {
        $price = $this->priceFor($model);

        $usd = ($promptTokens * $price['input'] + $completionTokens * $price['output']) / 1_000_000;

        return round($usd * (float) config('helpdeskaiprompts.pricing.usd_to_eur', 0.92), 6);
    }

    /**
     * @return array{input: float, output: float}
     */
    private function priceFor(?string $model): array
    {
        $models = (array) config('helpdeskaiprompts.pricing.models', []);
        $model = strtolower((string) $model);

        $matches = array_filter(array_keys($models), fn (string $key) => $model !== '' && str_starts_with($model, $key));
        usort($matches, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        $key = $matches[0] ?? config('helpdeskaiprompts.pricing.fallback_model', 'gpt-4o-mini');

        return [
            'input' => (float) ($models[$key]['input'] ?? 0),
            'output' => (float) ($models[$key]['output'] ?? 0),
        ];
    }
}
