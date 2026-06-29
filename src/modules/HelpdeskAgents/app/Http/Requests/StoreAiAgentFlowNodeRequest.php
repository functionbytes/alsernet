<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiAgentFlowNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'node_id' => ['required', 'string'],
            'type' => ['required', 'in:input,prompt,condition,action,output'],
            'label' => ['required', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
            'position' => ['required', 'array'],
            'position.x' => ['required', 'numeric'],
            'position.y' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'node_id.required' => 'El ID del nodo es obligatorio.',
            'type.required' => 'El tipo de nodo es obligatorio.',
            'type.in' => 'El tipo de nodo seleccionado no es válido.',
            'label.required' => 'La etiqueta es obligatoria.',
            'label.max' => 'La etiqueta no puede superar los 255 caracteres.',
            'data.array' => 'Los datos deben ser un conjunto válido.',
            'position.required' => 'La posición es obligatoria.',
            'position.array' => 'La posición debe ser un conjunto válido.',
            'position.x.required' => 'La coordenada X es obligatoria.',
            'position.x.numeric' => 'La coordenada X debe ser un número.',
            'position.y.required' => 'La coordenada Y es obligatoria.',
            'position.y.numeric' => 'La coordenada Y debe ser un número.',
        ];
    }

    public function attributes(): array
    {
        return [
            'node_id' => 'ID del nodo',
            'type' => 'tipo de nodo',
            'label' => 'etiqueta',
            'data' => 'datos',
            'position' => 'posición',
            'position.x' => 'coordenada X',
            'position.y' => 'coordenada Y',
        ];
    }
}
