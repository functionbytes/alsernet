<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Services\PromptTestRunner;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;

class PromptTestRunnerTest extends HelpdeskAiPromptsTestCase
{
    private function bindFakeAgent(): void
    {
        $fake = new class
        {
            public function run(string $question, array $context, array $data, string $locale, ?object $catalog = null): array
            {
                if (str_contains($question, 'roto')) {
                    return ['action' => 'escalate', 'text' => 'Te paso con un agente para ayudarte.', 'used_tools' => ['escalate_to_agent'], 'products' => []];
                }

                return [
                    'action' => 'respond',
                    'text' => 'Tienes 15 días naturales para devolver el producto.',
                    'used_tools' => ['search_help'],
                    'products' => [],
                ];
            }
        };

        $this->app->instance(ChatFlowAgentService::class, $fake);
    }

    public function test_run_evaluates_expectations_for_each_test_question(): void
    {
        $this->bindFakeAgent();

        $draft = [
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'instructions' => 'inst',
            'test_questions' => [
                ['question' => '¿Cómo devuelvo un producto?', 'expect_tools' => ['search_help'], 'expect_escalate' => false, 'must_contain' => ['15 días'], 'must_not_contain' => ['roto']],
                ['question' => 'Me ha llegado roto', 'expect_tools' => ['escalate_to_agent'], 'expect_escalate' => true, 'must_contain' => [], 'must_not_contain' => []],
            ],
        ];

        $results = app(PromptTestRunner::class)->run($draft);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]['passed']);
        $this->assertSame([], $results[0]['failures']);
        $this->assertTrue($results[1]['passed']);
        $this->assertSame('escalate', $results[1]['action']);
    }

    public function test_run_reports_failures_when_expectations_are_not_met(): void
    {
        $this->bindFakeAgent();

        $draft = [
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'instructions' => 'inst',
            'test_questions' => [
                ['question' => '¿Cómo devuelvo un producto?', 'expect_tools' => ['product_search'], 'expect_escalate' => true, 'must_contain' => ['30 días'], 'must_not_contain' => ['devolver']],
            ],
        ];

        $results = app(PromptTestRunner::class)->run($draft);

        $this->assertFalse($results[0]['passed']);
        $this->assertNotEmpty($results[0]['failures']);
        $this->assertCount(4, $results[0]['failures']); // tool, escalate, must_contain, must_not_contain
    }

    public function test_run_accepts_a_saved_case_model(): void
    {
        $this->bindFakeAgent();

        $case = AiPromptCase::query()->create([
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'description' => 'd', 'priority' => 0,
            'is_active' => true, 'instructions' => 'inst',
            'test_questions' => [
                ['question' => '¿Cómo devuelvo un producto?', 'expect_tools' => [], 'expect_escalate' => false, 'must_contain' => [], 'must_not_contain' => []],
            ],
        ]);

        $results = app(PromptTestRunner::class)->run($case);

        $this->assertCount(1, $results);
        $this->assertSame('¿Cómo devuelvo un producto?', $results[0]['question']);
    }

    public function test_run_skips_blank_questions(): void
    {
        $this->bindFakeAgent();

        $results = app(PromptTestRunner::class)->run([
            'key' => 'x', 'name' => 'X', 'instructions' => 'inst',
            'test_questions' => [['question' => '   ']],
        ]);

        $this->assertSame([], $results);
    }

    public function test_run_without_test_questions_returns_empty(): void
    {
        $this->bindFakeAgent();

        $results = app(PromptTestRunner::class)->run(['key' => 'x', 'name' => 'X', 'instructions' => 'inst']);

        $this->assertSame([], $results);
    }
}
