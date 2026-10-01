<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestAiActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdesk.ai-prompts.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'verified' => ['nullable', 'boolean'],
            'customer_email' => [Rule::requiredIf(fn (): bool => $this->boolean('verified')), 'nullable', 'email', 'max:190'],
            'args' => ['nullable', 'array'],
            'args.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['verified' => $this->boolean('verified')]);
    }
}
