<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use Modules\HelpdeskAiPrompts\Models\AiPromptRun;
use Modules\HelpdeskAiPrompts\Services\PromptMetrics;

class PromptMetricsTest extends HelpdeskAiPromptsTestCase
{
    public function test_per_case_aggregates_runs_escalations_and_feedback(): void
    {
        AiPromptRun::query()->create(['trace_id' => 't1', 'case_key' => 'devoluciones', 'routed_by' => 'keyword', 'action' => 'respond', 'used_tools' => ['search_help'], 'feedback' => 1, 'created_at' => now()]);
        AiPromptRun::query()->create(['trace_id' => 't2', 'case_key' => 'devoluciones', 'routed_by' => 'keyword', 'action' => 'escalate', 'used_tools' => ['search_help'], 'feedback' => -1, 'created_at' => now()]);
        AiPromptRun::query()->create(['trace_id' => 't3', 'case_key' => 'devoluciones', 'routed_by' => 'llm', 'action' => 'respond', 'used_tools' => ['answer_customer'], 'created_at' => now()]);
        AiPromptRun::query()->create(['trace_id' => 't4', 'case_key' => 'pedido', 'routed_by' => 'keyword', 'action' => 'respond', 'used_tools' => ['lookup_order'], 'created_at' => now()]);

        $metrics = app(PromptMetrics::class)->perCase(30);

        $devoluciones = $metrics->get('devoluciones');
        $this->assertSame(3, $devoluciones['runs']);
        $this->assertSame(1, $devoluciones['escalations']);
        $this->assertEqualsWithDelta(0.3333, $devoluciones['escalation_rate'], 0.001);
        $this->assertSame(1, $devoluciones['likes']);
        $this->assertSame(1, $devoluciones['dislikes']);
        $this->assertSame(0.5, $devoluciones['satisfaction']);
        $this->assertContains('search_help', $devoluciones['top_tools']);

        $this->assertSame(1, $metrics->get('pedido')['runs']);
        $this->assertSame(0.0, $metrics->get('pedido')['satisfaction']);
    }

    public function test_per_case_excludes_runs_older_than_the_window(): void
    {
        AiPromptRun::query()->create(['trace_id' => 'old', 'case_key' => 'devoluciones', 'routed_by' => 'keyword', 'action' => 'respond', 'used_tools' => [], 'created_at' => now()->subDays(40)]);
        AiPromptRun::query()->create(['trace_id' => 'new', 'case_key' => 'devoluciones', 'routed_by' => 'keyword', 'action' => 'respond', 'used_tools' => [], 'created_at' => now()]);

        $metrics = app(PromptMetrics::class)->perCase(30);

        $this->assertSame(1, $metrics->get('devoluciones')['runs']);
    }
}
