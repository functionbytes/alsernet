<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestAiAgentConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:openai,anthropic,gemini,local'],
            'api_key' => ['nullable', 'string'],
            'model' => ['required', 'string'],
            'base_url' => ['nullable', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider.required' => 'El proveedor es obligatorio.',
            'provider.in' => 'El proveedor seleccionado no es válido.',
            'model.required' => 'El modelo es obligatorio.',
            'base_url.url' => 'La URL base no tiene un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'provider' => 'proveedor',
            'api_key' => 'clave API',
            'model' => 'modelo',
            'base_url' => 'URL base',
        ];
    }
}
