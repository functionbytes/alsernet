<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskAiPrompts\Support\ToolCatalog;
use Modules\HelpdeskChatFlow\Models\ChatFlow;

/**
 * Shared rules/sanitizing for the case create/edit form and the "probar
 * borrador" (unsaved) endpoint: all three post the same field shape.
 */
abstract class AiPromptCaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdesk.ai-prompts.manage');
    }

    /**
     * @return array<string, mixed>
     */
    protected function baseRules(): array
    {
        return [
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['nullable', 'integer', 'between:-1000,1000'],
            'is_active' => ['nullable', 'boolean'],
            'instructions' => ['required', 'string', 'max:8000'],
            'allowed_tools' => ['nullable', 'array'],
            'allowed_tools.*' => [Rule::in(ToolCatalog::validKeys())],
            'knowledge_keys' => ['nullable', 'array'],
            'knowledge_keys.*' => ['string', 'max:64'],
            'escalation' => ['required', Rule::in(['never', 'on_doubt', 'always'])],
            'escalation_message' => ['nullable', 'string', 'max:500'],
            'examples' => ['nullable', 'array'],
            'examples.*.question' => ['nullable', 'string', 'max:500'],
            'examples.*.answer' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:64'],
            'filters' => ['nullable', 'array'],
            'filters.channels' => ['nullable', 'array'],
            'filters.channels.*' => [Rule::in(Inbox::CHANNEL_TYPES)],
            'filters.locales' => ['nullable', 'array'],
            'filters.locales.*' => ['string', 'max:5'],
            'filters.url_contains' => ['nullable', 'array'],
            'filters.url_contains.*' => ['string', 'max:255'],
            'filters.logged_in' => ['nullable', Rule::in(['any', 'yes', 'no'])],
            'filters.hours' => ['nullable', 'string', 'max:11', 'regex:/^\d{1,2}-\d{1,2}$/'],
            'test_questions' => ['nullable', 'array'],
            'test_questions.*.question' => ['required', 'string', 'max:500'],
            'test_questions.*.expect_tools' => ['nullable', 'array'],
            'test_questions.*.expect_tools.*' => [Rule::in(ToolCatalog::validKeys())],
            'test_questions.*.expect_escalate' => ['nullable', 'boolean'],
            'test_questions.*.must_contain' => ['nullable', 'array'],
            'test_questions.*.must_contain.*' => ['string', 'max:255'],
            'test_questions.*.must_not_contain' => ['nullable', 'array'],
            'test_questions.*.must_not_contain.*' => ['string', 'max:255'],
            'channel' => ['nullable', 'string', Rule::in(Inbox::CHANNEL_TYPES)],
            'procedure_flow_id' => ['nullable', 'integer', $this->procedureExistsRule()],
            'procedure_input' => ['nullable', 'array'],
            'procedure_input.*' => ['nullable', 'string', 'max:255'],
            'procedure_outputs' => ['nullable', 'array'],
            'procedure_outputs.*' => ['string', 'max:64', 'regex:/^[A-Za-z0-9_.]+$/'],
        ];
    }

    private function procedureExistsRule(): mixed
    {
        if (! class_exists(ChatFlow::class)) {
            return Rule::prohibitedIf(true);
        }

        return Rule::exists('helpdesk.helpdesk_chat_flows', 'id')
            ->where('trigger_type', 'procedure')
            ->where('status', 'active');
    }

    public function messages(): array
    {
        return [
            'key.regex' => __('helpdeskaiprompts::ai-prompts.validation_key_regex'),
            'filters.hours.regex' => __('helpdeskaiprompts::ai-prompts.validation_hours_regex'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'priority' => $this->input('priority') !== null && $this->input('priority') !== '' ? (int) $this->input('priority') : null,
            'examples' => $this->filteredPairs('examples', 'question', 'answer'),
            'keywords' => $this->filteredList('keywords'),
            'knowledge_keys' => $this->filteredList('knowledge_keys'),
            'allowed_tools' => $this->filteredList('allowed_tools'),
            'test_questions' => $this->filteredTestQuestions(),
            'channel' => $this->input('channel') ?: null,
            ...$this->procedureFields(),
        ]);
    }

    /**
     * El formulario envía el procedimiento como texto (una línea "variable=valor"
     * por entrada, salidas separadas por coma): se normaliza a la forma guardada.
     *
     * @return array<string, mixed>
     */
    private function procedureFields(): array
    {
        $fields = [
            'procedure_flow_id' => $this->input('procedure_flow_id') ?: null,
        ];

        if ($this->has('procedure_input_text')) {
            $fields['procedure_input'] = $this->parseInputLines((string) $this->input('procedure_input_text'));
        }

        if ($this->has('procedure_outputs_text')) {
            $fields['procedure_outputs'] = collect(preg_split('/[\s,]+/', (string) $this->input('procedure_outputs_text')) ?: [])
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return $fields;
    }

    /**
     * @return array<string, string>
     */
    private function parseInputLines(string $text): array
    {
        $input = [];

        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $name = trim($name);

            if ($name !== '') {
                $input[$name] = trim($value);
            }
        }

        return $input;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filteredPairs(string $field, string $keyA, string $keyB): array
    {
        return collect((array) $this->input($field, []))
            ->filter(fn ($row) => trim((string) ($row[$keyA] ?? '')) !== '' || trim((string) ($row[$keyB] ?? '')) !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function filteredList(string $field): array
    {
        return collect((array) $this->input($field, []))
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filteredTestQuestions(): array
    {
        return collect((array) $this->input('test_questions', []))
            ->filter(fn ($row) => trim((string) ($row['question'] ?? '')) !== '')
            ->map(function (array $row) {
                $row['expect_tools'] = collect((array) ($row['expect_tools'] ?? []))->filter()->values()->all();
                $row['must_contain'] = collect((array) ($row['must_contain'] ?? []))->map('trim')->filter()->values()->all();
                $row['must_not_contain'] = collect((array) ($row['must_not_contain'] ?? []))->map('trim')->filter()->values()->all();
                $row['expect_escalate'] = match ($row['expect_escalate'] ?? '') {
                    '1', 1, true, 'true' => true,
                    '0', 0, false, 'false' => false,
                    default => null,
                };

                return $row;
            })
            ->values()
            ->all();
    }
}
