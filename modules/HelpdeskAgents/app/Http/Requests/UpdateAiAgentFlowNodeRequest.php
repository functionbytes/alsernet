<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiAgentFlowNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
            'position' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'La etiqueta es obligatoria.',
            'label.max' => 'La etiqueta no puede superar los 255 caracteres.',
            'data.array' => 'Los datos deben ser un conjunto válido.',
            'position.required' => 'La posición es obligatoria.',
            'position.array' => 'La posición debe ser un conjunto válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'etiqueta',
            'data' => 'datos',
            'position' => 'posición',
        ];
    }
}
