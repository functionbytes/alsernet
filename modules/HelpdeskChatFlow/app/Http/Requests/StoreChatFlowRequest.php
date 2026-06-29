<?php

namespace Modules\HelpdeskChatFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskChatFlow\Models\ChatFlow;

class StoreChatFlowRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['nodes', 'trigger_conditions'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $decoded = json_decode($this->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$field => $decoded]);
                }
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('chatflow.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'inbox_id' => ['nullable', 'exists:helpdesk_inboxes,id'],
            'trigger_type' => ['required', 'in:conversation_start,keyword,manual,no_agent'],
            'trigger_conditions' => ['nullable', 'array'],
            'trigger_conditions.keywords' => ['sometimes', 'array'],
            'trigger_conditions.keywords.*' => ['string', 'max:100'],
            'trigger_conditions.timeout_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'nodes' => ['required', 'array'],
            'nodes.*.type' => ['required', 'string', 'in:'.implode(',', ChatFlow::NODE_TYPES)],
            'status' => ['nullable', 'in:draft,active,archived'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del flow es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'trigger_type.required' => 'El tipo de activación es obligatorio.',
            'trigger_type.in' => 'El tipo de activación seleccionado no es válido.',
            'inbox_id.exists' => 'El inbox seleccionado no existe.',
            'nodes.*.type.in' => 'Uno de los nodos tiene un tipo no válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'inbox_id' => 'inbox',
            'trigger_type' => 'tipo de activación',
            'trigger_conditions' => 'condiciones de activación',
            'nodes' => 'nodos',
            'status' => 'estado',
            'priority' => 'prioridad',
        ];
    }
}
