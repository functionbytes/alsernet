<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use Modules\HelpdeskAiPrompts\Models\AiPromptRun;
use Modules\HelpdeskAiPrompts\Services\AiUsageCost;
use Modules\HelpdeskAiPrompts\Services\PromptMetrics;
use Modules\HelpdeskAiPrompts\Services\PromptRunUsage;

class AiUsageCostTest extends HelpdeskAiPromptsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('helpdeskaiprompts.pricing', [
            'usd_to_eur' => 1.0,
            'models' => [
                'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
                'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            ],
            'fallback_model' => 'gpt-4o-mini',
        ]);
    }

    public function test_cost_is_computed_per_model(): void
    {
        $cost = app(AiUsageCost::class);

        $this->assertEqualsWithDelta(0.75, $cost->costEur('gpt-4o-mini', 1_000_000, 1_000_000), 0.000001);
        $this->assertEqualsWithDelta(12.5, $cost->costEur('gpt-4o', 1_000_000, 1_000_000), 0.000001);
    }

    public function test_versioned_model_uses_the_longest_matching_prefix(): void
    {
        $cost = app(AiUsageCost::class);

        $this->assertEqualsWithDelta(0.75, $cost->costEur('gpt-4o-mini-2024-07-18', 1_000_000, 1_000_000), 0.000001);
        $this->assertEqualsWithDelta(12.5, $cost->costEur('gpt-4o-2024-08-06', 1_000_000, 1_000_000), 0.000001);
    }

    public function test_unknown_model_falls_back_and_applies_the_exchange_rate(): void
    {
        config()->set('helpdeskaiprompts.pricing.usd_to_eur', 0.5);

        $this->assertEqualsWithDelta(0.375, app(AiUsageCost::class)->costEur('modelo-raro', 1_000_000, 1_000_000), 0.000001);
        $this->assertEqualsWithDelta(0.375, app(AiUsageCost::class)->costEur(null, 1_000_000, 1_000_000), 0.000001);
    }

    public function test_attach_records_usage_and_cost_on_the_run(): void
    {
        $run = AiPromptRun::query()->create(['trace_id' => 'u1', 'case_key' => 'pedido', 'routed_by' => 'keyword', 'action' => 'respond', 'used_tools' => [], 'created_at' => now()]);

        app(PromptRunUsage::class)->attach($run, ['prompt_tokens' => 2000, 'completion_tokens' => 500, 'model' => 'gpt-4o', 'calls' => 2]);

        $run = $run->fresh();
        $this->assertSame(2000, $run->prompt_tokens);
        $this->assertSame(500, $run->completion_tokens);
        $this->assertSame('gpt-4o', $run->model);
        $this->assertSame(2, $run->calls);
        $this->assertEqualsWithDelta(0.01, $run->cost_eur, 0.000001);
    }

    public function test_metrics_per_case_include_tokens_and_cost(): void
    {
        $usage = app(PromptRunUsage::class);

        foreach (['a' => 1000, 'b' => 3000] as $trace => $prompt) {
            $run = AiPromptRun::query()->create(['trace_id' => $trace, 'case_key' => 'pedido', 'routed_by' => 'keyword', 'action' => 'respond', 'used_tools' => [], 'created_at' => now()]);
            $usage->attach($run, ['prompt_tokens' => $prompt, 'completion_tokens' => 100, 'model' => 'gpt-4o-mini', 'calls' => 1]);
        }
        AiPromptRun::query()->create(['trace_id' => 'c', 'case_key' => 'devoluciones', 'routed_by' => 'keyword', 'action' => 'respond', 'used_tools' => [], 'created_at' => now()]);

        $metrics = app(PromptMetrics::class)->perCase(30);

        $this->assertSame(4200, $metrics->get('pedido')['tokens']);
        $this->assertEqualsWithDelta(0.00069, $metrics->get('pedido')['cost_eur'], 0.0001);
        $this->assertSame(0, $metrics->get('devoluciones')['tokens']);
        $this->assertSame(0.0, $metrics->get('devoluciones')['cost_eur']);
    }
}
