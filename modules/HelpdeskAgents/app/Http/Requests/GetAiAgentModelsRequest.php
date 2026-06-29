<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAiAgentModelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:openai,anthropic,gemini,local'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider.required' => 'El proveedor es obligatorio.',
            'provider.in' => 'El proveedor seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'provider' => 'proveedor',
        ];
    }
}
