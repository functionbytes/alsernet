<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Inbox;

abstract class AiPromptBlockFormRequest extends FormRequest
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
            'kind' => ['required', Rule::in(['base', 'knowledge'])],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'channel' => ['nullable', 'string', Rule::in(Inbox::CHANNEL_TYPES)],
            'locale' => ['nullable', 'string', 'max:5'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex' => __('helpdeskaiprompts::ai-prompts.validation_key_regex'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'channel' => $this->input('channel') ?: null,
            'locale' => $this->input('locale') ?: null,
        ]);
    }
}
