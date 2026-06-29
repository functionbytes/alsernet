<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestAiSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'context' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'context.max' => 'El contexto no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'context' => 'contexto',
        ];
    }
}
