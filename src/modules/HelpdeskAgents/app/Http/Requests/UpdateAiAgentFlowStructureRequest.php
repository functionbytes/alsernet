<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiAgentFlowStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'nodes' => ['present', 'array'],
            'edges' => ['present', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'nodes.present' => 'Los nodos son obligatorios.',
            'nodes.array' => 'Los nodos deben ser un conjunto válido.',
            'edges.present' => 'Las conexiones son obligatorias.',
            'edges.array' => 'Las conexiones deben ser un conjunto válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nodes' => 'nodos',
            'edges' => 'conexiones',
        ];
    }
}
