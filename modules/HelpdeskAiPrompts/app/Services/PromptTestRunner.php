<?php

namespace Modules\HelpdeskAiPrompts\Services;

use Illuminate\Support\Str;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogManager;

/**
 * Runs a case's test_questions through the real ChatFlowAgentService (as a
 * draft, without saving) and checks the expectations declared on each one.
 */
class PromptTestRunner
{
    /**
     * @param  AiPromptCase|array<string,mixed>  $case
     * @return array<int, array{question: string, answer: string, used_tools: array<int,string>, action: string, passed: bool, failures: array<int,string>}>
     */
    public function run(AiPromptCase|array $case): array
    {
        if (! class_exists(ChatFlowAgentService::class)) {
            return [];
        }

        $draft = $case instanceof AiPromptCase ? $case->toArray() : $case;
        $catalog = $this->resolveCatalog();
        $agent = app(ChatFlowAgentService::class);

        $results = [];

        foreach ((array) ($draft['test_questions'] ?? []) as $testQuestion) {
            $question = trim((string) ($testQuestion['question'] ?? ''));

            if ($question === '') {
                continue;
            }

            $response = $agent->run(
                $question,
                ['_trace_id' => 'test-'.uniqid()],
                [
                    'use_prompt_library' => true,
                    '_draft_case' => $draft,
                    'tool_products' => true,
                    'tool_cart' => false,
                    'tool_order_lookup' => true,
                ],
                'es',
                $catalog,
            );

            $results[] = $this->evaluate($testQuestion, $response);
        }

        return $results;
    }

    /**
     * @param  array<string,mixed>  $expectations
     * @param  array<string,mixed>  $response
     * @return array{question: string, answer: string, used_tools: array<int,string>, action: string, passed: bool, failures: array<int,string>}
     */
    private function evaluate(array $expectations, array $response): array
    {
        $usedTools = (array) ($response['used_tools'] ?? []);
        $answer = (string) ($response['text'] ?? '');
        $action = (string) ($response['action'] ?? '');
        $failures = [];

        foreach ((array) ($expectations['expect_tools'] ?? []) as $tool) {
            if (! in_array($tool, $usedTools, true)) {
                $failures[] = "No se usó la herramienta esperada: {$tool}";
            }
        }

        if (($expectations['expect_escalate'] ?? null) !== null) {
            $expectEscalate = (bool) $expectations['expect_escalate'];
            $didEscalate = $action === 'escalate';

            if ($expectEscalate !== $didEscalate) {
                $failures[] = $expectEscalate
                    ? 'Se esperaba escalar a un agente y no ocurrió.'
                    : 'No se esperaba escalar a un agente y ocurrió.';
            }
        }

        foreach ((array) ($expectations['must_contain'] ?? []) as $needle) {
            if ($needle !== '' && ! Str::contains(Str::lower($answer), Str::lower((string) $needle))) {
                $failures[] = "La respuesta no contiene: {$needle}";
            }
        }

        foreach ((array) ($expectations['must_not_contain'] ?? []) as $needle) {
            if ($needle !== '' && Str::contains(Str::lower($answer), Str::lower((string) $needle))) {
                $failures[] = "La respuesta contiene algo prohibido: {$needle}";
            }
        }

        return [
            'question' => (string) ($expectations['question'] ?? ''),
            'answer' => $answer,
            'used_tools' => $usedTools,
            'action' => $action,
            'passed' => $failures === [],
            'failures' => $failures,
        ];
    }

    private function resolveCatalog(): ?object
    {
        if (! class_exists(CatalogManager::class) || ! class_exists(Web::class)) {
            return null;
        }

        $web = Web::query()
            ->where('cms_type', 'prestashop')
            ->orWhere(function ($q) {
                $q->whereNotNull('product_feed_url')->where('product_feed_url', '!=', '');
            })
            ->orderBy('id')
            ->first();

        if ($web === null) {
            return null;
        }

        return app(CatalogManager::class)->forWeb($web);
    }
}
