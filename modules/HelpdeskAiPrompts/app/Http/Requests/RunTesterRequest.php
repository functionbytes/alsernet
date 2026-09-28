<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Inbox;

class RunTesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdesk.ai-prompts.view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
            'channel' => ['nullable', 'string', Rule::in(Inbox::CHANNEL_TYPES)],
            'locale' => ['nullable', 'string', 'max:5'],
            'logged_in' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel' => $this->input('channel') ?: null,
            'logged_in' => $this->boolean('logged_in'),
        ]);
    }
}
