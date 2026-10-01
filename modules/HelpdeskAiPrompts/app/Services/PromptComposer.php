<?php

namespace Modules\HelpdeskAiPrompts\Services;

use Illuminate\Support\Carbon;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;

/**
 * Builds the final system prompt for one turn: base prompt + matched case
 * (instructions, examples, knowledge) + optional flow instructions.
 */
class PromptComposer
{
    public function __construct(
        private readonly PromptLibrary $library,
        private readonly IntentRouter $router,
    ) {}

    /**
     * @param  array{channel?: ?string, locale?: ?string, page_url?: ?string, logged_in?: bool, now?: ?Carbon}  $ctx
     * @param  array<string,mixed>  $nodeData
     * @param  array<string,mixed>|null  $draftCase  Unsaved case (tester): takes priority over everything, routed_by = 'forced'
     * @return array{system: string, case_key: ?string, case_name: ?string, routed_by: string, allowed_tools: ?array, escalation: string, escalation_message: ?string, procedure_flow_id: ?int, procedure_input: array<string,mixed>, procedure_outputs: array<int,string>}
     */
    public function compose(string $question, array $ctx, array $nodeData = [], ?string $forcedCaseKey = null, ?array $draftCase = null): array
    {
        [$case, $routedBy] = $this->resolveCase($question, $ctx, $forcedCaseKey, $draftCase);

        $system = $this->library->base($ctx['channel'] ?? null, $ctx['locale'] ?? null)
            ?? trim((string) ($nodeData['instructions'] ?? ''));

        if ($case !== null) {
            $system .= $this->caseSection($case, $ctx);
        }

        if (($nodeData['append_instructions'] ?? false) === true) {
            $flowInstructions = trim((string) ($nodeData['instructions'] ?? ''));

            if ($flowInstructions !== '') {
                $system .= "\n\n## Instrucciones del flujo\n{$flowInstructions}";
            }
        }

        return [
            'system' => trim($system),
            'case_key' => $case !== null ? (string) $this->caseValue($case, 'key') : null,
            'case_name' => $case !== null ? (string) $this->caseValue($case, 'name') : null,
            'routed_by' => $routedBy,
            'allowed_tools' => $case !== null ? $this->caseValue($case, 'allowed_tools') : null,
            'escalation' => $case !== null ? (string) ($this->caseValue($case, 'escalation') ?? 'on_doubt') : 'on_doubt',
            'escalation_message' => $case !== null ? $this->caseValue($case, 'escalation_message') : null,
            'procedure_flow_id' => $case !== null && $this->caseValue($case, 'procedure_flow_id') ? (int) $this->caseValue($case, 'procedure_flow_id') : null,
            'procedure_input' => $case !== null ? (array) ($this->caseValue($case, 'procedure_input') ?? []) : [],
            'procedure_outputs' => $case !== null ? array_values((array) ($this->caseValue($case, 'procedure_outputs') ?? [])) : [],
        ];
    }

    /**
     * @param  array<string,mixed>|null  $draftCase
     * @return array{0: array<string,mixed>|AiPromptCase|null, 1: string}
     */
    private function resolveCase(string $question, array $ctx, ?string $forcedCaseKey, ?array $draftCase): array
    {
        if ($draftCase !== null) {
            return [$draftCase, 'forced'];
        }

        if ($forcedCaseKey !== null) {
            $case = $this->library->cases($ctx['channel'] ?? null)->firstWhere('key', $forcedCaseKey);

            return [$case, 'forced'];
        }

        $routed = $this->router->route($question, $ctx);

        return [$routed['case'], $routed['routed_by']];
    }

    /**
     * @param  array<string,mixed>|AiPromptCase  $case
     */
    private function caseSection(array|AiPromptCase $case, array $ctx): string
    {
        $section = "\n\n## Caso: {$this->caseValue($case, 'name')}\n{$this->caseValue($case, 'instructions')}";

        foreach ((array) $this->caseValue($case, 'examples', []) as $example) {
            $q = trim((string) ($example['question'] ?? ''));
            $a = trim((string) ($example['answer'] ?? ''));

            if ($q !== '' && $a !== '') {
                $section .= "\n\nEjemplo — Cliente: {$q} / Tú: {$a}";
            }
        }

        $knowledgeKeys = (array) ($this->caseValue($case, 'knowledge_keys') ?? []);
        $knowledge = $this->library->knowledge($knowledgeKeys, $ctx['channel'] ?? null, $ctx['locale'] ?? null);

        if ($knowledge !== []) {
            $section .= "\n\n## Información de la tienda\n".implode("\n\n", $knowledge);
        }

        return $section;
    }

    /**
     * @param  array<string,mixed>|AiPromptCase  $case
     */
    private function caseValue(array|AiPromptCase $case, string $key, mixed $default = null): mixed
    {
        return is_array($case) ? ($case[$key] ?? $default) : ($case->{$key} ?? $default);
    }
}
